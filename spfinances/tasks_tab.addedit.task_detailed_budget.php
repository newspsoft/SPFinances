<?php /* SPFINANCES tasks_tab.addedit.task_detailed_budget.php, v 0.1.0 27.09.2013    */
/*
* Copyright (c) 2014 
*
* Description:	PHP function page of the SPFinances module.
*
* Author:		Stepan Poghosyan, <stepanpoghosyan@newspsoft.ru>
* WEB: http://newspsoft.ru/
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
*
*/
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}
// Get the global var
global $AppUI, $task_id, $task_project, $spbudget, $tab;

// Include the necessary classes
require_once $AppUI->getModuleClass('spfinances');

// Load the corresponding Budget
$spbudget = new SPCBudget();

// collect all budget Categorys for the Category list
$budgetcategory= array('0' => 'None');
//Frst collect all the frest level Categorys for the Category parent list
$qcat = new DBQuery;
$qcat->addTable('spbudget_typ','t');
$qcat->addQuery('t.id, t.title');
$qcat->addOrder(' position, id ');
$qcat->addWhere('t.parent_id = 0');
$rowsfc = $qcat->loadHashList();
foreach ($rowsfc as $k => $v)
{
	$budgetcategory[$k] = $v;
	$qcat->clear();
	$qcat->addTable('spbudget_typ', 't');
	$qcat->addQuery('t.id, t.title');
	$qcat->addWhere(' parent_id = '.$k);
	$qcat->addOrder( ' position, id ' );
	$rowsubs = $qcat->loadHashList();
	foreach ($rowsubs as $k1 => $v1)
	{
		$budgetcategory[$k1] = ' - '.$v1;
	}
}


						
if($spbudget->loadFromTask($task_id) != -1) { // If task is not dynamic
	?>
	<link href="./modules/spfinances/css/spfinances.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="./modules/spfinances/js/spfinances.js"></script>
	<form action="?m=tasks&amp;a=addedit&amp;task_project=<?php echo $task_project; ?>"
	  method="post" name="SPbudgetFrm">
	<input type="hidden" name="sub_form" value="5" />
	<input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
	<input type="hidden" name="dosql" value="do_task_aed" />

	<table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
	<tr>
		<td valign="top" align="center">
			
			<table cellspacing="0" cellpadding="3" border="1">
				<tr align="center">
					<td colspan="4"> 
						<?php echo $AppUI->_("Tax"); ?>: 	&#37;<input type="text" class="text" name="TVASP" id="TVASP" value="<?php echo ($task_id) ? $spbudget->Tax : mostCommonTax();?>" onchange="updateBudgetView();"/>
					</td>
				</tr>
				<tr align="center">
					<td colspan="4"> 
						<?php echo $AppUI->_("Amounts"); ?>: 	
						<label for="sptax0"><input type="radio" name="display_sptax" id="sptax0" value=0 " onchange="updateBudgetView();" <?php if($spbudget->display_tax == 0) echo "checked"; ?> /><?php echo $AppUI->_("without tax"); ?></label>
						<label for="sptax1"><input type="radio" name="display_sptax" id="sptax1" value=1  " onchange="updateBudgetView();" <?php if($spbudget->display_tax == 1) echo "checked"; ?> /><?php echo $AppUI->_("with tax"); ?></label>
					</td>
				</tr>
				<tr align="center">
					<td colspan="4">
						<label for="only_spfinancial"><input type="checkbox" class="checkbox" <?php if($spbudget->only_financial) echo 'checked="checked"';?> name="only_spfinancial" id="only_spfinancial" /><?php echo $AppUI->_('This task is only financial'); ?></label>
					</td>
				</tr>
				<tr align="right">
					<td><?php echo $AppUI->_('Investment'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="investment" id="investment" value="<?php echo $spbudget->get_investment();?>" onchange="javascript:updateBudgetView();"/>
					</td>
					<td><?php echo $AppUI->_('Category'); ?>: </td>
					<td class="hilite" width="150px">
						<?php echo arraySelect($budgetcategory, 'investment_id', 'id="investment_id" size="1" class="text" onChange="javascript:updateBudgetView();"', ($spbudget->investment_id) ? $spbudget->investment_id : 0); ?>
					</td>
				</tr>
				<tr align="right">
					<td><?php echo $AppUI->_('Profit'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="profit" id="profit" value="<?php echo $spbudget->get_profit();?>" onchange="javascript:updateBudgetView();"/>
					</td>
					<td><?php echo $AppUI->_('Category'); ?>: </td>
					<td class="hilite" width="150px">
						<?php echo arraySelect($budgetcategory, 'profit_id', 'id="profit_id" size="1" class="text" onChange="javascript:updateBudgetView();"', ($spbudget->profit_id) ? $spbudget->profit_id : 0); ?>
					</td>
				</tr>
				<tr align="center">
					<td colspan="4" class="hilite"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="totaledit" id="totaledit" value="<?php echo $spbudget->get_investment()+$spbudget->get_profit();?>" disabled="disabled"/>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
	</form>
	<script language="javascript" type="text/javascript">
	updateBudgetView();
	// Definition of local function that need PHP
	function checkAmountById(id){
		if(isNaN(parseFloat(document.getElementById(id).value.replace(/ /g,''))))
			document.getElementById(id).value = 0;
		var val = parseFloat(document.getElementById(id).value.replace(/ /g,''));
		if (id=='investment')
			if(val>0)
				val = -1*val; 

		if (id=='profit') 
			if(val<0)
				val = -1*val; 
		
		document.getElementById(id).value = val.toFixed(2);
	}

	function updateBudgetView(){
		// Set correct format
		checkAmountById("investment");
		checkAmountById("profit");
		
		// Compute sums
		var inv = parseFloat(document.getElementById("investment").value.replace(/ /g,''));
		var pro = parseFloat(document.getElementById("profit").value.replace(/ /g,''));
		var sum = inv+pro;
		document.getElementById("totaledit").value = addThousandsSep(sum.toFixed(2),' ');
		if(document.getElementsByName("task_target_budget")[0].value==0)
		{
			var taxval = document.getElementById("TVASP").value;
			var tax = document.getElementsByName("display_sptax");
			if(tax[1].checked) //with tax
				sum = sum/(1+taxval/100);
			
			document.getElementsByName("task_target_budget")[0].value = sum.toFixed(2);
		}
	}
	
	function checkSPBudget(form, id) { return true; }	
	function saveSPBudget(form, id) { return true; }
	
	subForm.push(new FormDefinition(<?php echo $tab; ?>, document.SPbudgetFrm, checkSPBudget, saveSPBudget));
	</script>
<?php
} else echo '<center>'.$AppUI->_('You can not set the budget of a dynamic task').'</center>';
?>
