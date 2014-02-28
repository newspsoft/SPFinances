<?php  /* SPFINANCES index.php, v 0.1.0 26.02.2014 */
/*
* Copyright (c) 2014 
*
* Author:		Stepan Poghosyan, <stepanpoghosyan@newspsoft.ru>
* WEB: http://newspsoft.ru/
* Description:
* 
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// Get the global var
global $dPconfig, $m;

// Check permissions for this module
if (!getPermission($m, 'view')) 
	$AppUI->redirect("m=public&a=access_denied");
	


$AppUI->savePlace();

$titleBlock = new CTitleBlock('SPFinances', 'helpdesk.png', $m, "$m.$a");
if ($canAuthor) {
	$titleBlock->addCrumb('?m=spfinances&amp;a=viewcategory', 'View budget category');
}
$titleBlock->show();

// Include the necessary classes
require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('departments');
require_once $AppUI->getModuleClass('spfinances');
//require_once $AppUI->getModuleClass('macroprojects');
require_once $AppUI->getModuleClass('contacts');

// Config
$company_prefix = 'c_';

// Set today
$today = new CDate();

// Get the params
$q = new DBQuery;
$q->addTable('users', 'u');
$q->addQuery('DISTINCT(user_contact)');
$q->addWhere('user_id = '.$AppUI->user_id);

$contact	= new CContact();
$contact->load($q->loadResult());
$company 	= dPgetParam($_POST, 'company_id', 0);//($contact->contact_department != 0) ? $contact->contact_department : $company_prefix.$contact->contact_company );	// Company/Department filter			Default: current user company

$currency 	= dPgetParam($_POST, 'currency', 0);										// 0: *1, 1: *1k, 2: *1M  				Default: *1
$display 	= dPgetParam($_POST, 'display', 0);											// 0: details, 1: subTotal, 2: total	Default: details
$tax 		= dPgetParam($_POST, 'tax', 0);												// 0: without, 1: with	 				Default: without
$hideNull = dPgetParam($_POST, 'hideNull', 0);									// 0:all, 1:Only valued projects and tasks	2:valued projects 3:Only projects with null budget
$project_status = dPgetParam($_POST, 'project_status', 0);      //
$project_type = dPgetParam($_POST, 'project_type', 0);

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');
//  echo $start_date->format(FMT_TIMESTAMP_DATE); 20140226 $year.'0101
$start_date = new CDate();
$start_date_default  = $today->getYear().'0101';
$end_date = new CDate(); 
$start_date->setDate(dPgetParam($_POST, 'tasks_start_date', $start_date_default),FMT_TIMESTAMP_DATE);
$end_date->setDate(dPgetParam($_POST, 'tasks_end_date', $today->format(FMT_TIMESTAMP_DATE)),FMT_TIMESTAMP_DATE);
								// (array)Months to display 				Default: this year
$toExpand	= dPgetParam($_POST, 'expandedList', null);									// List of <tr> to re-expand			Default: none(null)

// Extract company_id and department_id from company param
$company_id = substr(strrchr($company, $company_prefix), strlen($company_prefix));
if ($company_id == '') {
	$company_id 	= 	0;
	$department_id 	= 	$company;
	$department		= 	''.$department_id;
} else
	$department_id 	= 	0;

// Edit the values if necessary
if(dPgetParam($_POST, 'edit', 0))
	foreach($_POST as $vblname => $value) updateSPCBudgetValue($vblname, $value, $tax); // Check on $_POST value is made after

// collect all budget Categorys for the Category list
$budgetcategory= array('0' => '');
//Frst collect all the frest level Categorys for the Category parent list
$qcat = new DBQuery;
$qcat->addTable('spbudget_typ','t');
$qcat->addQuery('t.id, t.title');
$qcat->addOrder(' position, id ');
$qcat->addWhere('t.parent_id = 0');
$rowsfc = $qcat->loadHashList();
foreach ($rowsfc as $k => $v) 
{
	$vn='';
	$vn=strip_tags(trim($v));
	if(strlen( $vn)>23)
	{
		$vn=trim(substr($vn,0,23-1)).'...';
	}
	//echo '$vn->'.$vn.'</br>';
	$budgetcategory[$k] = $vn;
	$qcat->clear();
	$qcat->addTable('spbudget_typ', 't');
	$qcat->addQuery('t.id, t.title');
	$qcat->addWhere(' parent_id = '.$k);
	$qcat->addOrder( ' position, id ' );
	$rowsubs = $qcat->loadHashList();
	foreach ($rowsubs as $k1 => $v1) 
	{
		$v1n='';
		$v1n= strip_tags(trim($v1));
		if(strlen( $v1n)>23)
		{
			$v1n=trim(substr($v1n,0,23-1)).'...';
		}
		$budgetcategory[$k1] = ' - '.$v1n;
		//echo '$v1->'.' - '.$v1n.'</br>';
	}
}

?>
<link href="./modules/spfinances/css/jquery.treeTable.css" rel="stylesheet" type="text/css" />
<link href="./modules/spfinances/css/spfinances.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="./modules/spfinances/js/jquery.js"></script>
<script type="text/javascript" src="./modules/spfinances/js/jquery.ui.js"></script>
<script type="text/javascript" src="./modules/spfinances/js/jquery.treeTable.js"></script>
<script type="text/javascript" src="./modules/spfinances/js/spfinances.js"></script>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo DP_BASE_URL;?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$(".treeTable").treeTable({
			initialState: "collapsed"	 	// can be changed for "expanded"
		});
		<?php 
		// Re-expand <tr> wich were expanded on last page
		if(isset($_POST['expandedList'])) {
			$expand = explode(',',$toExpand);
			foreach ($expand as $id) {
				if ($id != '')
					echo '$("tr#'.$id.'").expand();';
			}
		} ?>
	});
</script>

<form id="mainFrm" name="mainFrm" action="?m=spfinances" method="post">
	<input type="hidden" name="edit" value="0" />
	<table class="tbl" cellspacing="0" cellpadding="4" border="0" width ="100%">
		<tbody>
			<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Start Date');?></td>
			<td nowrap="nowrap">	 <input type="hidden" name="tasks_start_date" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
				<input type="text" class="text" name="start_date" id="start_date" value="<?php echo $start_date->format($df);?>" class="text" disabled="disabled" />

				<a href="#" onclick="javascript:sppopCalendar('start_date', 'start_date');">
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
			</td>
			<td align="right" rowspan="2"  nowrap><?php echo $AppUI->_("Display").': '; ?></td>
				<td align="right" nowrap><label for="currency0"><input type="radio" name="currency" id="currency0" value="0" onChange="javascript:submitWithExpandList(this);" <?php if($currency == "0") echo 'checked'; ?> /><?php echo $dPconfig['currency_symbol']; ?></label></td>
				<td align="right" nowrap><label for="currency1"><input type="radio" name="currency" id="currency1" value="1" onChange="javascript:submitWithExpandList(this);" <?php if($currency == "1") echo 'checked'; ?> /><?php echo "k".$dPconfig['currency_symbol']; ?></label></td>
				<td align="right" nowrap><label for="currency2"><input type="radio" name="currency" id="currency2" value="2" onChange="javascript:submitWithExpandList(this);" <?php if($currency == "2") echo 'checked'; ?> /><?php echo "M".$dPconfig['currency_symbol']; ?></label></td>
				<td align="right" nowrap><?php echo $AppUI->_("Projects").': '; ?></td>
				<td align="left" nowrap>
					<select class="text" name="hideNull" id="hideNull" onChange="javascript:submitWithExpandList(this);">
						<option value="0" <?php echo ($hideNull==0)?'selected="selected"':''; ?>><?php echo $AppUI->_("All"); ?></option>
						<option value="1" <?php echo ($hideNull==1)?'selected="selected"':''; ?>><?php echo $AppUI->_("Only valued projects and tasks"); ?></option>
						<option value="2" <?php echo ($hideNull==2)?'selected="selected"':''; ?>><?php echo $AppUI->_("Only valued projects"); ?></option>
						<option value="3" <?php echo ($hideNull==3)?'selected="selected"':''; ?>><?php echo $AppUI->_("Only projects with null budget"); ?></option>
					</select>
				</td>
				<td align="right" nowrap><?php echo $AppUI->_("Amounts").': '; ?></td>	
				<td align="left" nowrap><label for="tax0"><input type="radio" name="tax" id="tax0" value="0" onChange="javascript:submitWithExpandList(this);" <?php if($tax == "0") echo 'checked'; ?> /><?php echo $AppUI->_("without tax"); ?></label></td>
				<td align="left" nowrap><label for="tax1"><input type="radio" name="tax" id="tax1" value="1" onChange="javascript:submitWithExpandList(this);" <?php if($tax == "1") echo 'checked'; ?> /><?php echo $AppUI->_("with tax"); ?></label></td>
				<td></td><td></td>
			</tr>
			<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Finish Date');?></td>
			<td nowrap="nowrap">	<input type="hidden" name="tasks_end_date" value="<?php echo $end_date ? $end_date->format(FMT_TIMESTAMP_DATE) : '';?>" />
				<input type="text" class="text" name="end_date" id="end_date" value="<?php echo $end_date ? $end_date->format($df) : '';?>" class="text" disabled="disabled" />

				<a href="#" onclick="javascript:sppopCalendar('end_date', 'end_date');">
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
			</td>
			<td align="left" nowrap><label for="display0"><input type="radio" name="display" id="display0" value="0" onClick="javascript:$('.subTotal').hide();$('.total').hide();$('.budget').show()" /><?php echo $AppUI->_('Detail'); ?></label></td>
				<td align="left" nowrap><label for="display1"><input type="radio" name="display" id="display1" value="1" onClick="javascript:$('.budget').hide();$('.total').hide();$('.subTotal').show()" /><?php echo $AppUI->_('Sub Total'); ?></label></td>
				<td align="left" nowrap><label for="display2"><input type="radio" name="display" id="display2" value="2" onClick="javascript:$('.budget').hide();$('.subTotal').hide();$('.total').show()" /><?php echo $AppUI->_('Total'); ?></label></td>
			
			<td align="right" nowrap><?php echo $AppUI->_('Company').'/'.$AppUI->_('Division').':'; ?></td>
				<td align="left" colspan="1" nowrap>
					<?php
					$obj_company 	= new CCompany();
					$companies 		= $obj_company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
					if (count($companies) == 0) { 
						$companies = array(0);
					}
					
					// get the list of permitted companies
					$companies = arrayMerge(array('0' => $AppUI->_('All')), $companies);
					
					//get list of all departments, filtered by the list of permitted companies.
					$q = new DBQuery();
					$q->addTable('companies', 'c');
					$q->addQuery('c.company_id, c.company_name, dep.*');
					$q->addJoin('departments', 'dep', 'c.company_id = dep.dept_company');
					$q->addOrder('c.company_name, dep.dept_parent, dep.dept_name');
					$obj_company->setAllowedSQL($AppUI->user_id, $q);
					$rows = $q->loadList();
					
					//display the select list
					$cBuffer = '<select name="company_id" onChange="javascript:submitWithExpandList(this);" class="text">';
					$cBuffer .= ('<option value="0" style="font-weight:bold;">' . $AppUI->_('All') 
								 . '</option>'."\n");
					$cBuffer .= ('<option value="-1" style="font-weight:bold;" '
								 .(($company == -1) ? 'selected="selected"' : '')
								 . '>' . $AppUI->_('None') 
								 . '</option>'."\n");
					$comp = '';
					foreach ($rows as $row) {
						if ($row['dept_parent'] == 0) {
							if ($comp != $row['company_id']) {
								$cBuffer .= ('<option value="' . $AppUI->___($company_prefix . $row['company_id']) 
											 . '" style="font-weight:bold;"' 
											 . (($company.'' == $AppUI->___($company_prefix . $row['company_id'])) ? 'selected="selected"' : '') 
											 . '>' . $AppUI->___($row['company_name']) . '</option>' . "\n");
								$comp = $row['company_id'];
							}
							
							if ($row['dept_parent'] != null) {
								showchilddept($row);
								findchilddept($rows, $row['dept_id']);
							}
						}
					}
					$cBuffer .= '</select>';
					echo $cBuffer; ?>
				</td>
				<?php 
				$ptypeTemp = dPgetSysVal('ProjectType');
				$pstatusTemp = dPgetSysVal('ProjectStatus');
				$ptype[0] = $AppUI->_('All');
				$pstatus[0] =$AppUI->_('All');
				$ptype = array_merge($ptype, $ptypeTemp);
				$pstatus = array_merge($pstatus,$pstatusTemp);
			?>
						
				<td align="right" colspan="2">
					<?php echo $AppUI->_('Project Type').' : ';?>
					<?php echo arraySelect($ptype, 'project_type', 'id="project_type" size="1" class="text" onChange="javascript:submitWithExpandList(this);"', $project_type, true);?> 
				</td>
				<td align="right" colspan="2">
					<?php echo $AppUI->_('Project Status').' : ';?> 
				</td>
				<td align="left" colspan="1">
					<?php echo arraySelect($pstatus, 'project_status', 'id="project_status" size="1" class="text" onChange="javascript:submitWithExpandList(this);"', $project_status, true); ?>		
				</td>
				
				</tr>
			<tr>
				<td align="left" rowspan="2" nowrap><input type="button" class="button" value="<?php echo $AppUI->_("Submit"); ?>" onclick="javascript:submitWithExpandList(this);" /></td>
			</tr>
			
			<tr>
				
					
			
				<td align="left" colspan="1">
					<a href="?m=spfinances&amp;a=export_excel&amp;projectStatus=<?php echo $project_status;?>&amp;projectType=<?php echo $project_type;?>&amp;departementId=<?php echo $departement_id;?>&amp;companyId=<?php echo $company_id;?>&amp;start_date1=<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>&amp;end_date1=<?php echo $end_date->format(FMT_TIMESTAMP_DATE);?>&amp;tax=<?php echo $tax;?>&amp;hideNull=<?php echo $hideNull;?>&amp;suppressHeaders=1">
					<?php echo $AppUI->_('Excel export');?></a>
				</td>
				<td></td><td></td>
			</tr>
		</tbody>
	</table>
<div style="text-align: center; padding: 5px; display: none;" id="errorDiv"><?php echo $AppUI->_("Performance of Internet Explorer may cause a very significant slowdown in the case of a large number of projects to load."); ?></div>
<div style="text-align: center; padding: 5px;">
	<input type="button" id="saveButton" value="<?php echo $AppUI->_('Save Changes');?>" onclick="javascript:submitWithExpandList(this);" />
</div>
<!-- Main table -->
<table class="treeTable" cellspacing="0" cellpadding="0" border="1">
	<tbody>
	<?php
		switch($currency) {
			case 0 : $mult = 1; $symbol = $dPconfig['currency_symbol']; break;
			case 1 : $mult = 0.001; $symbol = 'k'.$dPconfig['currency_symbol']; break;
			case 2 : $mult = 0.000001; $symbol = 'M'.$dPconfig['currency_symbol']; break;
			default :  $mult = 1; $symbol = $dPconfig['currency_symbol'];
		}
			
		$total_investment = 0;
		$total_profit = 0;
		//target budget task
		$total_plannig_investment = 0; // if target budget task < 0 this investment else profit
		$total_plannig_profit = 0;
		
		// Load all projects
		$projects = SPloadProjects($company_id, $department_id, $project_type, $project_status);
//	$count=0;
		foreach($projects as $project){
//count++;
			if(getPermission('projects', 'view', $project->project_id)) { // Check permission
				$tasks = SPloadTasksID($project->project_id, $start_date->format(FMT_TIMESTAMP_DATE),$end_date->format(FMT_TIMESTAMP_DATE)); // Load only corresponding tasks
				if ($tasks != null) { // Check if project have tasks before display it
					$proj =	new SPCProjectBudget($project->project_id,$tax,$start_date->format(FMT_TIMESTAMP_DATE),$end_date->format(FMT_TIMESTAMP_DATE));	
					
					foreach($tasks as $task) {//if($hideNull !=1  || $task->task_dynamic == 1){//|| !$budget->isNull()
						$proj->AddTask(intval($task));
						}
						echo '<tr id="p_'.$project->project_id.'" rel="p_'.$project->project_id.'">';
						echo '<td class="tdDesc" style="background-color:#'.$project->project_color_identifier.';">'
								.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" />'
								.'<a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=projects&a=view&project_id='.$project->project_id.'" style="color:' . bestColor($project->project_color_identifier) . ';">'
								.$project->project_name.'</a>'
								.'</td>'
								.'<td class="tdContentProject budget" rel="">'.$proj->get_project_total_planned_investment($mult,$symbol).'</td>'
								.'<td class="tdContentProject budget" rel="">'.$proj->get_project_total_planned_profit($mult,$symbol).'</td>'
								.'<td class="tdContentProject budget" rel="">'.$proj->get_project_total_investment($mult,$symbol).'</td>'
								.'<td class="tdContentProject budget" rel=""></td>'
								.'<td class="tdContentProject budget" rel="">'.$proj->get_project_total_profit($mult,$symbol).'</td>'
								.'<td class="tdContentProject budget" rel=""></td>'
								."</tr>\n";
						
						$total_investment += $proj->get_project_total_investment();
						$total_profit += $proj->get_project_total_profit();
						//target budget task
						$total_plannig_investment += $proj->get_project_total_planned_investment(); // if target budget task < 0 this investment else profit
						$total_plannig_profit += $proj->get_project_total_planned_profit();
						
						
						
					foreach($proj->tasksBudget as $tsbuget) {	//Fill table for project
						//if($hideNull !=1  || $task1->task_dynamic == 1){//|| !$budget->isNull()
							//$tsbuget = $proj->tasksBudget[$task1->task_id];
													
							if($tsbuget->countofChild == 0) {
								// If the task don't have children, we can edit the budget ...
								$parent = null;
								if ($tsbuget->get_task_parent_id() == $tsbuget->task_id) {
									$parent = 'child-of-p_'.$project->project_id ;
								} else {
									$parent = 'child-of-p_'.$project->project_id.'_t_'.$tsbuget->get_task_parent_id();
								}
								$id = 'p_'.$project->project_id.'_t_'.$tsbuget->task_id;
								$trtask = 	'<tr id="'.$id.'" class="'.$parent.'">';
								
								$tdname = '<td class="tdDesc">';
									
								if ($tsbuget->ts->task_dynamic == 1)
									$tdname .=  '<img src="./modules/finances/images/dyna.gif" width="10px" height:"10px" /><a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$tsbuget->task_id.'">'.$tsbuget->ts->task_name.'</a>'.$tsbuget->tsstartdate->format($df).'</td>';
								else
									$tdname .=  '<a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$tsbuget->task_id.'">'.$tsbuget->ts->task_name.' ('.$tsbuget->bg->Tax.$AppUI->_("%").')</a>'.$tsbuget->tsstartdate->format($df).'</td>';

								$editable = '';
								if(getPermission('tasks', 'edit', $tsbuget->task_id)) $editable = 'editable';
								$sttr = '<td class="tdContentTask '.$editable.' budget '.$parent.'_planned_investment" rel="_plannedinvestment" val="">'.$tsbuget->get_task_planned_investment($mult, $symbol).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_planned_profit" rel="_plannedprofit" val="">'.$tsbuget->get_task_planned_profit($mult, $symbol).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_investment" rel="_investment" val="">'.$tsbuget->get_task_investment($mult, $symbol).'</td>'
										.'<td class="tdContentTaskCategory '.$editable.' budget Category '.$parent.'_Categoryinvestment" rel="_Categoryinvestment" val="">'.arraySelect($budgetcategory,'CategorySelect', ' id= "'.$id.'_Categoryinvestment" size="1" class="text" onChange="javascript:submitWithExpandList(this);"',($tsbuget->bg->investment_id) ? $tsbuget->bg->investment_id : 0).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_profit" rel="_profit" val="">'.$tsbuget->get_task_profit($mult, $symbol).'</td>'
										.'<td class="tdContentTaskCategory '.$editable.' budget Category '.$parent.'_Categoryprofit" rel="_Categoryprofit" val="">'.arraySelect($budgetcategory,'CategorySelect', ' id= "'.$id.'_Categoryprofit" size="1" class="text" onChange="javascript:submitWithExpandList(this);"',($tsbuget->bg->profit_id) ? $tsbuget->bg->profit_id : 0).'</td><tr/>'
										."\n";

								$st = $trtask.$tdname.$sttr;
								echo $st;
								
							} else { //totall
								// ... otherwise it need to be computed (by JS)
								$parent = null;
								if ($tsbuget->get_task_parent_id() == $tsbuget->task_id) {
									$parent = 'child-of-p_'.$project->project_id ;
								} else {
									$parent = 'child-of-p_'.$project->project_id.'_t_'.$tsbuget->get_task_parent_id();
								}
								$id = 'p_'.$project->project_id.'_t_'.$tsbuget->task_id;
								$trtask = 	'<tr id="'.$id.'" class="'.$parent.'">';
								
								$tdname = '<td class="tdDesc">';
									
								if ($tsbuget->ts->task_dynamic == 1)
									$tdname .=  '<img src="./modules/finances/images/dyna.gif" width="10px" height:"10px" /><a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$tsbuget->task_id.'"> Totall of -> '.$tsbuget->ts->task_name.'</a>'.$tsbuget->tsstartdate->format($df).'</td>';
								else
									$tdname .=  '<a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$tsbuget->task_id.'"> Totall of -> '.$tsbuget->ts->task_name.' ('.$tsbuget->bg->Tax.$AppUI->_("%").')</a>'.$tsbuget->tsstartdate->format($df).'</td>';
								$editable = '';
								$sttr = '<td class="tdContentTask '.$editable.' budget '.$parent.'_planned_investment" rel="_plannedinvestment" val="">'.$tsbuget->get_task_total_planned_investment($mult, $symbol).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_planned_profit" rel="_plannedprofit" val="">'.$tsbuget->get_task_total_planned_profit($mult, $symbol).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_investment" rel="_investment" val="">'.$tsbuget->get_task_total_investment($mult, $symbol).'</td>'
										.'<td class="tdContentTaskCategory '.$editable.' budget Category '.$parent.'_Categoryinvestment" rel="_" val="">'.''.'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_profit" rel="_profit" val="">'.$tsbuget->get_task_total_profit($mult,$symbol).'</td>'
										.'<td class="tdContentTaskCategory '.$editable.' budget Category '.$parent.'_Categoryprofit" rel="_Categoryprofit" val="">'.''.'</td><tr/>'
										."\n";

								$st = $trtask.$tdname.$sttr;
								echo $st;
							}
							if($tsbuget->countofChild != 0 AND !$tsbuget->onlyTotal)
							{//Add editable copy totall
								$parent = 'child-of-p_'.$project->project_id.'_t_'.$tsbuget->task_id;
								$id = 'p_'.$project->project_id.'_t_'.$tsbuget->task_id.'_ed';
								$trtask = 	'<tr id="'.$id.'" class="'.$parent.'">';
								$tdname = '<td class="tdDesc">';
									
								if ($tsbuget->ts->task_dynamic == 1)
									$tdname .=  '<img src="./modules/finances/images/dyna.gif" width="10px" height:"10px" /><a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$tsbuget->task_id.'"> Dynamic-> '.$tsbuget->ts->task_name.'</a>'.$tsbuget->tsstartdate->format($df).'</td>';
								else
									$tdname .=  '<a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$tsbuget->task_id.'"> Dynamic-> '.$tsbuget->ts->task_name.' ('.$tsbuget->bg->Tax.$AppUI->_("%").')</a>'.$tsbuget->tsstartdate->format($df).'</td>';
								
								$editable = '';
								if(getPermission('tasks', 'edit', $tsbuget->task_id)) $editable = 'editable';
								$sttr = '<td class="tdContentTask '.$editable.' budget '.$parent.'_planned_investment" rel="_plannedinvestment" val="">'.$tsbuget->get_task_planned_investment($mult, $symbol).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_planned_profit" rel="_plannedprofit" val="">'.$tsbuget->get_task_planned_profit($mult, $symbol).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_investment" rel="_investment" val="">'.$tsbuget->get_task_investment($mult, $symbol).'</td>'
										.'<td class="tdContentTaskCategory '.$editable.' budget Category '.$parent.'_Categoryinvestment" rel="_Categoryinvestment" val="">'.arraySelect($budgetcategory,'CategorySelect', ' id= "'.$id.'_Categoryinvestment" size="1" class="text" onChange="javascript:submitWithExpandList(this);"',($tsbuget->bg->investment_id) ? $tsbuget->bg->investment_id : 0).'</td>'
										.'<td class="tdContentTask '.$editable.' budget '.$parent.'_profit" rel="_profit" val="">'.$tsbuget->get_task_profit($mult, $symbol).'</td>'
										.'<td class="tdContentTaskCategory '.$editable.' budget Category '.$parent.'_Categoryprofit" rel="_Categoryprofit" val="">'.arraySelect($budgetcategory,'CategorySelect', ' id= "'.$id.'_Categoryprofit" size="1" class="text" onChange="javascript:submitWithExpandList(this);"',($tsbuget->bg->profit_id) ? $tsbuget->bg->profit_id : 0).'</td><tr/>'
										."\n";

								$st = $trtask.$tdname.$sttr;
								echo $st;
							}
						

						//}//if($hideNull !=1  || $task->task_dynamic == 1){//|| !$budget->isNull()
					}//foreach($tasks as $task)
				}//if project ($tasks != null) {
			  }//if(getPermission('projects', 'view', $project->project_id))
			}//foreach($projects as $project)
							
	
	$total_actual = ($total_investment+$total_profit);
	$total_plannig = $total_plannig_investment+$total_plannig_profit;
	switch($currency) {
		case 0 : $mult = 1; $symbol = $dPconfig['currency_symbol']; break;
		case 1 : $mult = 0.001; $symbol = 'k'.$dPconfig['currency_symbol']; break;
		case 2 : $mult = 0.000001; $symbol = 'M'.$dPconfig['currency_symbol']; break;
		default :  $mult = 1; $symbol = $dPconfig['currency_symbol'];
	}
	?>
	</tbody>
	<thead>
		<tr>
			<th class="tdDesc" rowspan="2">
				<?php echo $AppUI->_('Project / Task(Tax)');?></td>
			</th>
			<th colspan="2">
				<?php echo $AppUI->_('PLAN');?></td>
			</th>
			<th colspan="4">
				<?php echo $AppUI->_('ACTUAL');?></td>
			</th>
		</tr>
		<tr>
			<th><?php echo $AppUI->_('Investment');?></th>
			<th><?php echo $AppUI->_('Profit');?></th>
			<th><?php echo $AppUI->_('Investment');?></th>
			<th><?php echo $AppUI->_('Category');?></th>
			<th><?php echo $AppUI->_('Profit');?></th>
			<th><?php echo $AppUI->_('Category');?></th>
		</tr>
		<tr class="tdTotal">
			<td class="tdDesc"><b>TOTAL</b></td>
			<td class="tdContentTask budget" rel="plannig_investment"><b><?php echo number_format($total_plannig_investment*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="plannig_profit"><b><?php echo number_format($total_plannig_profit*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_investment"><b><?php echo number_format($total_investment*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" ><b></b></td>
			<td class="tdContentTask budget" rel="_profit"><b><?php echo number_format($total_profit*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget"><b></b></td>
		</tr>
	</thead>
	<tfoot>
		<tr class="tdTotal">
			<td class="tdDesc" rowspan=2><b>TOTAL</b></td>
			<td class="tdContentTask budget" rel="plannig_investment"><b><?php echo number_format($total_plannig_investment*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="plannig_profit"><b><?php echo number_format($total_plannig_profit*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_investment"><b><?php echo number_format($total_investment*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" ><b></b></td>
			<td class="tdContentTask budget" rel="_profit"><b><?php echo number_format($total_profit*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget"><b></b></td>
		</tr>
		<tr class="tdTotal">
			<td colspan=2 class="tdContentTask" rel="total_plannig"><b><?php echo number_format($total_plannig*$mult,2,'.',' ').$symbol; ?></b></td>
			<td colspan=4 class="tdContentTask" rel="total_actual"><b><?php echo number_format($total_actual*$mult,2,'.',' ').$symbol; ?></b></td>
		</tr>
		
	</tfoot>
</table>

</form>
<script type="text/javascript">
// Definition of function that need PHP
function getCurrency(){
	var radios = document.getElementsByName("currency");
	if(radios[2].checked)
		return "M<?php echo $dPconfig['currency_symbol']; ?>";
	if(radios[1].checked)
		return "k<?php echo $dPconfig['currency_symbol']; ?>";
	return "<?php echo $dPconfig['currency_symbol']; ?>";
}

function tdClick(){
	$("#saveButton").show();
	if (!$(this).hasClass("Category")){
	$(this).addClass("edit");
	$(this).html('<?php echo $dPconfig['currency_symbol']; ?><input name="'+$(this).parent().attr('id')+$(this).attr('rel')+'" style="width: 85%;" type="text" value="'+ $(this).attr('val')+'"/>');
	$(this).children().focus();}
}


<?php 
// Set the precedent display
switch($display) {
	case "0": echo '$("#display0").click(); '; break;
	case "1": echo '$("#display1").click(); '; break;
	case "2": echo '$("#display2").click(); '; break; 
	default:  echo '$("#display0").click(); ';
}
?>
// Block Internet Explorer
if (window.navigator.appName == 'Microsoft Internet Explorer')
{
	$('#errorDiv').show();
}
// Run the js scripts to complete the table values
//completeTd();
//$(".treeTable").contents().each(function(i){ alert($(this).text())});
// Allow dynamic edition and display correct icons 
$("tr:not(.parent) > .editable:not(.edit)").live("click", tdClick).live("mouseenter", tdEnter).live('mouseleave', tdLeave);
//$(".editable:not(.edit)").live("click", tdClick).live("mouseenter", tdEnter).live('mouseleave', tdLeave);
$("td.tdDesc > a").hover(function() {$(this).append($("<img src='./images/icons/posticon.gif' />"));}, function() {$(this).find("img:last").remove();});

<?php 
// Remove lines according filter
switch($hideNull) {
	case "0": break;
	//case "1": echo '$("td.tdContentProject[rel=\'_tt\']").each(function(i) { if (!(parseFloat($(this).text()) > 0)) $(this).parent().remove();}); '; break;
	//case "2": echo '$("td.tdContentProject[rel=\'_tt\']").each(function(i) { if (!(parseFloat($(this).text()) > 0)) $(this).parent().remove();}); '; break;
	//case "3": echo '$("td.tdContentProject[rel=\'_tt\']").each(function(i) { 
	//						if (parseFloat($(this).text()) > 0) {
	//							$("tr[class^=\'child-of-"+$(this).parent().attr(\'id\')+"\']").each(function (j){
	//								$(this).remove();
	//							});
	//							$(this).parent().remove();
	//						}
	//					}); $(".tdTotal").remove();'; break;
	default:  break;
}
?>
var spcalendarField = '';
var spcalWin = null;

function sppopCalendar(field) {
//due to a bug in Firefox (where window.open, when in a function, does not properly unescape a url)
// we CANNOT do a window open with &amp; separating the parameters
//this bug does not occur if the window open occurs in an onclick event
//this bug does NOT occur in Internet explorer
spcalendarField = field;
idate = eval('document.mainFrm.tasks_' + field + '.value');

window.open('index.php?m=public&a=calendar&dialog=1&callback=spsetCalendar&date=' + idate, 'spcalwin', 'width=280, height=250, scrollbars=no, status=no');
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function spsetCalendar(idate, fdate) {
	fld_date = eval('document.mainFrm.tasks_' + spcalendarField);
	fld_fdate = eval('document.mainFrm.' + spcalendarField);
	fld_date.value = idate;
	fld_fdate.value = fdate;

	// set end date automatically with start date if start date is after end date
	if (spcalendarField == 'start_date') {
		if (document.mainFrm.tasks_end_date.value < idate) {
			document.mainFrm.tasks_end_date.value = idate;
			document.mainFrm.end_date.value = fdate;
		}
	}
	if (spcalendarField == 'end_date') {
		if (document.mainFrm.tasks_start_date.value > idate) {
			document.mainFrm.tasks_start_date.value = idate;
			document.mainFrm.start_date.value = fdate;
		}
	}
}
</script>