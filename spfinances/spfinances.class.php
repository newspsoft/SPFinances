<?php /* SPFINANCES spfinances.class.php, v 0.1.0 26.02.2014 */
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
* 	Creation
*
*/

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once 'spbugetcategory.class.php';

class SPCBudget extends CDpObject {
	var $budget_id = NULL;
	var $task_id = NULL;
	var $Tax = "0.00";
	var $only_financial = 0;
	var $display_tax = 0;
	
	var $investment = "0.00";
	var $profit = "0.00";
	
	var $investment_id = 0;
	var $profit_id = 0;
	
	
	
	function SPCBudget() {
		$this->CDpObject('spbudget', 'budget_id'); //from budget to spbudget
	}

	function loadFromTask($task_id) {
		//if(SPcountChildren($task_id) != 0) return -1;
		$q = new DBQuery;
		$q->addTable('spbudget');
		$q->addQuery('budget_id');
		$q->addWhere('task_id = ' . $task_id );
		$sql = $q->prepare();
		$q->clear();
		$budget_id = db_loadResult($sql);
		if($budget_id == null) {
			$this->Tax = SPmostCommonTax();
			return 0;
		}
		else $this->load($budget_id);
		return 1;
	}
	
	function store() {
		//if(SPcountChildren($this->task_id) != 0) return false;
		if($this->task_id <= 0) return false;
		$q = new DBQuery;
		$q->addTable('spbudget');
		$q->addQuery('budget_id');
		$q->addWhere('task_id = ' . $this->task_id );
		$sql = $q->prepare();
		$q->clear();
		$budget_id = db_loadResult($sql);
		if($budget_id == null) {
			$q->addTable('spbudget');
			$q->addInsert('task_id', $this->task_id);
			db_exec($q->prepare());
			$budget_id = db_insert_id();
		}
		$q = new DBQuery;
		$q->addTable('spbudget', 'b');
		$q->addUpdate('Tax',$this->Tax);
		$q->addUpdate('only_financial',$this->only_financial);
		$q->addUpdate('display_tax',$this->display_tax);
		$q->addUpdate('investment',$this->investment);
		$q->addUpdate('profit',$this->profit);
		$q->addUpdate('investment_id',$this->investment_id);
		$q->addUpdate('profit_id',$this->profit_id);
		$q->addWhere('task_id = '.$this->task_id);
		$sql = $q->prepare();
		$q->clear();
		db_exec($sql);
	}
	
	function get_investment($mult = 1, $symbol = "", $sep = "") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->investment*$tax*$mult,2,'.',$sep).$symbol; }
	function get_profit($mult = 1, $symbol = "", $sep = "") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->profit*$tax*$mult,2,'.',$sep).$symbol; }
	
	
	/* Return the Profit category Name of SPCBudget
	 */
	function get_Profitcategory_Name(){
		if(profit_id)
		{
			$q = new DBQuery;
			$q->addTable('spbudget_typ','t');
			$q->addQuery('t.title');
			$q->addWhere(' t.id = '.intval($this->profit_id));
			return $q->loadResult();
		}
		return '';
	}
	
	/* Return the Investment category Name of SPCBudget
	 */
	function get_Investmentcategory_Name(){
		if(investment_id)
		{
			$q = new DBQuery;
			$q->addTable('spbudget_typ','t');
			$q->addQuery('t.title');
			$q->addWhere('t.id = '.intval($this->investment_id));
			return $q->loadResult();
		}
		return ''; 
	}
	
	/* Return the best currency display mode for this budget
	*/ 
	function bestCurrency() {
		$ret = 2;
		if($this->investment != 0){
			if($this->investment <=1000000) $ret=1;
			if($this->investment <=1000) return 0;
		}
		if($this->profit != 0){
			if($this->profit <=1000000) $ret=1;
			if($this->profit <=1000) return 0;
		}
		return $ret;
	}
	
	function addTax($amount){
		return $amount + $amount * $this->Tax/100;
	}
	
	function isNull(){
		if ($this->investment + $this->profit > 0) return false;
		else return true;
	}
}

/////////////////////////////// FUNCTION ///////////////////////////

/* Update one value in data base
** @param	string	var
** @param	float	val
** @param	bool	tax
*  investment_id ? and profit_id ?
*/ 
function updateSPCBudgetValue($var, $val, $tax) {
	//echo '$var - >  '.$var.'</br>';
	//echo '$val - >  ';print_r($val); echo '</br>';
	global $AppUI;
	if($val == null) $val = "0.00";
	if(!is_numeric($val)) return -1;	
	$tab = explode('_',$var);
	if(count($tab) < 5) return -1;
	$project_id = $tab[1];
	$task_id = $tab[3];
	$type = $tab[4];
	if($type=='ed')
		$type = $tab[5];
	if (!getPermission('tasks', 'edit', $task_id)) {
		$AppUI->redirect("m=public&a=access_denied");
		return -1;
	}
//	echo 'task_id - >  '.$task_id.'</br>'; //from element id + rel
//	echo 'type - >  '.$type.'</br>';
//	echo 'val - >  '.$val.'</br>';
	$q = new DBQuery();
	$q->addTable('spbudget');
	$q->addQuery('COUNT(*)');
	$q->addWhere('task_id = '.$task_id);
	
	if($q->loadResult() == 0){
		$q->clear();
		$q->addTable('spbudget');
		$q->addInsert('task_id', $task_id);
		$q->addInsert('Tax', SPmostCommonTax());
		db_exec($q->prepare());
	}
	if($tax){//width tax
		$q->clear();
		$q->addTable('spbudget');
		$q->addQuery('Tax');
		$q->addWhere('task_id = '.$task_id);
		$val = number_format($val/($q->loadResult()/100 + 1),2,'.','');
	}
	$updatetask_target_budget = 0;
	
	switch($type){
		case "investment": 
			if($val>0)
				$val=-1*$val;
			$q->clear();
			$q->addTable('spbudget');
			$q->addUpdate('investment', $val);
			$q->addWhere('task_id = '.$task_id);
			$q->exec();
			$updatetask_target_budget = 1;
			break;
		case "profit": 
			if($val<0)
				$val=-1*$val;
			$q->clear();
			$q->addTable('spbudget');
			$q->addWhere('task_id = '.$task_id);
			$q->addUpdate('profit', $val);
			$q->exec();
			$updatetask_target_budget = 1;
			break;
		case "Categoryinvestment":
			if($val<0)
				return -1;
			$q->clear();
			$q->addTable('spbudget');
			$q->addWhere('task_id = '.$task_id);
			$q->addUpdate('investment_id', $val);
			return $q->exec();
			
		case "Categoryprofit":
			if($val<0)
				return -1;
			$q->clear();
			$q->addTable('spbudget');
			$q->addWhere('task_id = '.$task_id);
			$q->addUpdate('profit_id', $val);
			return $q->exec();
		case "plannedinvestment":
			if($val>0)
				$val=-1*$val;
			$q->clear();
			$q->addTable('tasks', 't');
			$q->addUpdate('task_target_budget',$val);
			$q->addWhere('task_id = '.$task_id);
			return $q->exec();
		case "plannedprofit":
			if($val<0)
				$val=-1*$val;
			$q->clear();
			$q->addTable('tasks', 't');
			$q->addUpdate('task_target_budget',$val);
			$q->addWhere('task_id = '.$task_id);
			return $q->exec();
		default: return -1;
	}
	
	if($updatetask_target_budget and $val <> 0 ) 
	{
		$q->clear();
		$q->addTable('tasks', 't');
		$q->addQuery('task_target_budget');
		$q->addWhere('task_id = '.$task_id);
		if($q->loadResult() == 0)
		{
			$q->clear();
			$q->addTable('tasks', 't');
			$q->addUpdate('task_target_budget',$val);
			$q->addWhere('task_id = '.$task_id);
			return $q->exec();
			
			//$sql = $q->prepare();
			//$q->clear();
			//db_exec($sql);
		}
	}

	 return 1;
}

/* Loads the various projects according to filters.
** @param	int		company_id
** @param	int		department_id
** @param	int		project_type
** @param	int		project_status
** @return	CProject[]	projects
*/ 

function SPloadProjects($company_id, $department_id, $project_type = 0, $project_status = 0) {
	$q = new DBQuery;
	$q->addTable('projects', 'p');
	$q->addQuery('DISTINCT(p.project_id)');
	if ($company_id)
		$q->addWhere('(project_company = '.$company_id.' OR project_company_internal = '.$company_id.')');
	elseif ($department_id == -1){
		$q->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
		$q->addWhere('p.project_id NOT IN (SELECT pd.project_id FROM '.dPgetConfig('dbprefix', '').'project_departments pd)');
	}elseif ($department_id){
		$q->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
		$q->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
		$q->addWhere('(pd.department_id = '.$department_id.' OR d.dept_parent = '.$department_id.')');
	}
	if($project_type){
		$q->addWhere('p.project_type = ' . ($project_type - 1));
	}
	if($project_status){
		$q->addWhere('p.project_status = ' . ($project_status - 1));
	}else{
		$q->addWhere('p.project_status <= 4');    // All non Archived/Finished/Model projects
	}
	$q->addOrder('p.project_name ASC');
	$projectsId = 	$q->loadColumn();
	$projects 	= 	Array();
	foreach($projectsId as $key => $row)
	{
		$projects[$key] = new CProject();
		$projects[$key]->load($row);
	}
	return $projects;
}

/* Loads the various tasks ID of this project and corresponding to the tasks_start_date - tasks_end_date.
** @param	int			project_id
** @param	FMT_TIMESTAMP_DATE		$tasks_start_date - 20140226
** @param	FMT_TIMESTAMP_DATE		$tasks_end_date - 20140226
** @return	CTask[]	
*/ 

function SPloadTasksID($project_id, $tasks_start_date,$tasks_end_date) {
	//echo '$tasks_start_date - >  '.$tasks_start_date.'</br>';
	//echo '$tasks_end_date - >  '.$tasks_end_date.'</br>';
	$q = new DBQuery;
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(task_id)');
	$q->addWhere('task_project = '.$project_id);
	$q->addWhere('task_parent = t.task_id');
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');
	$ids = $q->loadColumn(); 				// here you have all top level task's ids task_parent = t.task_id
	
	$add_ids = Array(); //// here you have all top level task's ids if hehave Children in $tasks_start_date,$tasks_end_date
	foreach($ids as $id){
		if(SPhaveRangedChildren($id, $tasks_start_date,$tasks_end_date))
			$add_ids[] = $id;
	}
	
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(task_id)');
	$q->addWhere('task_project = '.$project_id);
	$q->addWhere('task_parent = t.task_id');
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');

		$where = '( false ';
		foreach($add_ids as $add_id) {
			$where .= ' OR task_id='.$add_id;
		}
	
		$where .= ' OR ( task_start_date >= '.$tasks_start_date.' AND task_start_date <= '.$tasks_end_date.' )';

		$where .= ')';
		$q->addWhere($where);

	$ids = $q->loadColumn(); 				// here you have all top level task's ids from $tasks_start_date,$tasks_end_date
	
	$tasks_ids = Array();

	foreach ($ids as $id) {				 	// add the id to the list and all is childs too
		$tasks_ids[] = $id;
		$child_ids = SPgetAllChildrenIds($id, $tasks_start_date, $tasks_end_date);
		foreach ($child_ids as $c_id)
			$tasks_ids[] = $c_id;
	}
	
	return $tasks_ids;
	//$tasks = Array();
	//foreach($tasks_ids as $key => $row) { 	// create the CTask array from the ids
	//	$tasks[$key] = new CTask();
	//	$tasks[$key]->load($row);
	//}
	
	//return $tasks;
}

/* Return true if the corresponding task have at least one child which have start_date corresponding to years
** @param 	int		task_id
** @param	FMT_TIMESTAMP_DATE		$tasks_start_date
** @param	FMT_TIMESTAMP_DATE		$tasks_end_date
** @return 	boolean
*/

function SPhaveRangedChildren($task_id, $tasks_start_date,$tasks_end_date){
	if(SPcountChildren($task_id, $tasks_start_date,$tasks_end_date))
		return true;
	else {
		$children = SPgetChildrenIds($task_id);
		foreach($children as $child){
			if(SPhaveRangedChildren($child, $tasks_start_date,$tasks_end_date))
				return true;
		}
	}
	return false;
}

/* Return the most common value of Tax in database
** @return	float	Tax
*/ 

function SPmostCommonTax() {
	$q = new DBQuery;
	$q->addTable('spbudget', 'b');
	$q->addQuery('Tax, COUNT(Tax)');
	$q->addWhere('Tax != 0.00');
	$q->addGroup('Tax');
	$q->addOrder('COUNT(Tax) DESC LIMIT 0,1');
	return $q->loadResult();
}

/* Return the number of children of this task
** @param	int		task_id
** @param	FMT_TIMESTAMP_DATE		$tasks_start_date
** @param	FMT_TIMESTAMP_DATE		$tasks_end_date
** @return	int		children number
*/ 

function SPcountChildren($task_id, $tasks_start_date=null,$tasks_end_date=null){
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('COUNT(DISTINCT(t.task_id))');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	if ($tasks_start_date != null && $tasks_end_date != null ) {
		$where = '( false ';

			$where .= ' OR ( task_start_date >= '.$tasks_start_date.' AND task_start_date <= '.$tasks_end_date.' )';

		$where .= ')';
		$q->addWhere($where);
	}
	return $q->loadResult(); 
}

/* Return the children ids of this task
** @param	int		task_id
** @return	int[]	children id
*/ 

function SPgetChildrenIds($task_id){
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	return $q->loadColumn(); 
}


/* Recursive function that return an array of all children's ID of a task
** @param	int			task_id
** @param	FMT_TIMESTAMP_DATE		$tasks_start_date - 20140226
** @param	FMT_TIMESTAMP_DATE		$tasks_end_date - 20140226
** @return	int[]		child_ids
*/ 

function SPgetAllChildrenIds($task_id, $tasks_start_date=null,$tasks_end_date=null) {
	$q = new DBQuery();
	$add_ids = Array();
	if ($tasks_start_date != null && $tasks_end_date != null) {
		$q->addTable('tasks', 't');
		$q->addQuery('DISTINCT(task_id)');
		$q->addWhere('task_parent != t.task_id');
		$q->addWhere('task_parent = '.$task_id);
		$q->addWhere('task_status >= 0');
		$q->addOrder('task_start_date , task_end_date, task_id');
		$ids = $q->loadColumn();
		foreach($ids as $id){
			if(SPhaveRangedChildren($id, $tasks_start_date,$tasks_end_date))
				$add_ids[] = $id;
		}
	}


	$q->clear();
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');
	if ($tasks_start_date !=null  && $tasks_end_date != null) {
		$where = '( false ';
		foreach($add_ids as $add_id) {
			$where .= ' OR task_id='.$add_id;
		}
		
		$where .= ' OR ( task_start_date >= '.$tasks_start_date.' AND task_start_date <= '.$tasks_end_date.' )';
		
		$where .= ')';
		$q->addWhere($where);
	}
	$child_ids = $q->loadColumn();
	if ($child_ids == null) {
		return Array();
	} else {
		foreach ($child_ids as $id) {
			$ret[] = $id;
			$c = SPgetAllChildrenIds($id, $tasks_start_date,$tasks_end_date);
			if ($c != Array()) {
				foreach ($c as $i)
					$ret[] = $i;
			}
		}
		return $ret;
	}
}


/////////////////////////////// END FUNCTION ///////////////////////////

class SPCtasktBudget  {
	var $task_id = NULL;
	var $ts =  NULL;
	
	//task_start_date >= '.$tasks_start_date.' AND task_start_date <= '.$tasks_end_date
	var $onlyTotal = TRUE;
	var $countofChild = 0;
	var $bg = NULL;
	var $total_task_investment = 0;
	var $total_task_profit = 0;
	//planned
	var $total_task_planned_investment = 0;
	var $total_task_planned_profit = 0;
	
	var  $displeytasktax = 0;
	var  $tsstartdate = NULL;
	
	function SPCtasktBudget($task_id, $displeytax,$start_date,$end_date) {
		$this->task_id=intval($task_id);
		$this->displeytasktax = $displeytax;
		$this->ts = new CTask();
		$this->ts->load($this->task_id);
		$this->tsstartdate = new CDate($this->ts->task_start_date);
	//	echo 'task_id - >  '.$this->task_id.'</br>';
	//	echo 'task_start_date - >  '.$this->tsstartdate->format(FMT_TIMESTAMP_DATE).' $start_date-> '.$start_date.' $end_date-> '.$end_date.'</br>';
		if($start_date != NULL AND $end_date != NULL)
		{
			if($this->tsstartdate->format(FMT_TIMESTAMP_DATE) >= $start_date AND $this->tsstartdate->format(FMT_TIMESTAMP_DATE) <= $end_date)
			{
				$this->countofChild = SPcountChildren(intval($this->task_id));
				$this->onlyTotal = FALSE;
				$this->bg =  new SPCBudget();
				$this->bg->loadFromTask($this->task_id);
				$this->total_task_investment = $this->get_task_investment(1);
				$this->total_task_profit = $this->get_task_profit(1);
				$this->total_task_planned_investment = $this->get_task_planned_investment(1);
				$this->total_task_planned_profit = $this->get_task_planned_profit(1);
			}else {
				$this->onlyTotal = TRUE;
				$this->countofChild = SPcountChildren(intval($this->task_id));
			}
		}else {//ese if($start_date != NULL AND $end_date != NULL)
			$this->countofChild = SPcountChildren(intval($this->task_id));
			$this->onlyTotal = FALSE;
			$this->bg =  new SPCBudget();
			$this->bg->loadFromTask($this->task_id);
			$this->total_task_investment = $this->get_task_investment(1);
			$this->total_task_profit = $this->get_task_profit(1);
			$this->total_task_planned_investment = $this->get_task_planned_investment(1);
			$this->total_task_planned_profit = $this->get_task_planned_profit(1);
		}
	//	echo 'onlyTotal - >  '.$this->onlyTotal.'</br>';
	//echo 'task_id - >  '.$this->task_id.'</br>';
	//echo 'ts.task_parent - >  '.$this->ts->task_parent.'</br>';
	//	echo 'SPCBudget ID - >  '.$this->bg->budget_id.'</br>';
	//	echo 'profit - >  '.$this->total_task_profit.'</br>';
	//	echo 'investment - >  '.$this->total_task_investment.'</br>';
	}
	
	function get_task_parent_id()
	{
		return intval($this->ts->task_parent);
	}
	
	function get_task_planned_investment($mult = 1, $symbol = "", $sep = "")
	{
		if($this->onlyTotal)
			return SPCtasktBudget::get_formated_val(0, $mult, $this->displeytasktax, $symbol, $sep);
		$val = $this->ts->task_target_budget;
		if($val > 0)//it is profit
			$val = 0;
		return SPCtasktBudget::get_formated_val($val, $mult, $this->displeytasktax, $symbol, $sep);
	}
	function get_task_planned_profit($mult = 1, $symbol = "", $sep = "")
	{
		if($this->onlyTotal)
			return SPCtasktBudget::get_formated_val(0, $mult, $this->displeytasktax, $symbol, $sep);
		$val = $this->ts->task_target_budget;
		if($val < 0) //it is Investment
			$val = 0;
		return SPCtasktBudget::get_formated_val($val, $mult, $this->displeytasktax, $symbol, $sep);
	}
	function get_task_investment($mult = 1, $symbol = "", $sep = "")
	{
		if($this->onlyTotal)
			return SPCtasktBudget::get_formated_val(0, $mult, $this->displeytasktax, $symbol, $sep);
		return SPCtasktBudget::get_formated_val($this->bg->investment, $mult, $this->displeytasktax, $symbol, $sep);
	}
	function get_task_profit($mult = 1, $symbol = "", $sep = "")
	{
		if($this->onlyTotal)
			return SPCtasktBudget::get_formated_val(0, $mult, $this->displeytasktax, $symbol, $sep);
		return SPCtasktBudget::get_formated_val($this->bg->profit, $mult, $this->displeytasktax, $symbol, $sep);
	}
	function get_task_total_investment($mult = 1, $symbol = "", $sep = "")
	{
		return SPCtasktBudget::get_formated_val($this->total_task_investment, $mult, 0, $symbol, $sep);
	}
	function get_task_total_profit($mult = 1, $symbol = "", $sep = "")
	{
		return SPCtasktBudget::get_formated_val($this->total_task_profit, $mult, 0, $symbol, $sep);
	}
	function get_task_total_planned_profit($mult = 1, $symbol = "", $sep = "")
	{
		return SPCtasktBudget::get_formated_val($this->total_task_planned_profit, $mult, 0, $symbol, $sep);
	}
	function get_task_total_planned_investment($mult = 1, $symbol = "", $sep = "")
	{
		return SPCtasktBudget::get_formated_val($this->total_task_planned_investment, $mult, 0, $symbol, $sep);
	}
	
	private function get_formated_val($val, $mult = 1, $displaytax =  0, $symbol = "", $sep = "")
	{
		$tax =  1; if($this->displeytasktax) $tax += ($this->bg->Tax/100);
		//echo 'tax->'.$tax.'<br>';
		//echo '$displaytax->'.$this->displeytasktax.'<br>';
		return number_format($val*$tax*$mult,2,'.',$sep).$symbol;
	}
	
}

class SPCProjectBudget  {
	
	var $project_id = NULL;
	protected $displeytax = 0;
	//task_start_date >= '.$tasks_start_date.' AND task_start_date <= '.$tasks_end_date
	var $budget_start_date =  NULL;
	var $budget_end_date = NULL;
	
	var $tasksBudget =  Array(); // SPCtasktBudget
	
	var $total_project_investment = 0;
	var $total_project_profit = 0;
	//planned
	var $total_project_planned_investment = 0;
	var $total_project_planned_profit = 0;
	
	function SPCProjectBudget($pojectid, $dipleytax=0, $start_date, $end_date) {	
		$this->project_id = $pojectid;
		$this->displeytax  = $dipleytax;
		$this->budget_start_date = $start_date;
		$this->budget_end_date = $end_date;
	}
	
	//function get_task_parent_id($taskid)
	//{
	//	return $this->tasksBudget[$taskid]->ts->task_parent;
	//}
	
	function get_project_total_investment($mult = 1, $symbol = "", $sep = "")
	{
		return SPCProjectBudget::get_proj_formated_val($this->total_project_investment, $mult, $symbol, $sep);
	}
	function get_project_total_profit($mult = 1, $symbol = "", $sep = "")
	{
		return SPCProjectBudget::get_proj_formated_val($this->total_project_profit, $mult, $symbol, $sep);
	}
	function get_project_total_planned_profit($mult = 1, $symbol = "", $sep = "")
	{
		return SPCProjectBudget::get_proj_formated_val($this->total_project_planned_profit, $mult,  $symbol, $sep);
	}
	function get_project_total_planned_investment($mult = 1,  $symbol = "", $sep = "")
	{
		return SPCProjectBudget::get_proj_formated_val($this->total_project_planned_investment, $mult, $symbol, $sep);
	}
	
	private function get_proj_formated_val($val, $mult = 1, $symbol = "", $sep = "")
	{
		return number_format($val*$mult,2,'.',$sep).$symbol;
	}
	
	function Sumparent($task_id,$parent_id,$planned_investment,$planned_profit,$investment,$profit) {
		if(array_key_exists ( $parent_id , $this->tasksBudget ))
		{//1,$this->displeytax
			$this->tasksBudget[$parent_id]->total_task_planned_investment += $planned_investment;
			$this->tasksBudget[$parent_id]->total_task_planned_profit += $planned_profit;
			$this->tasksBudget[$parent_id]->total_task_investment += $investment;
			$this->tasksBudget[$parent_id]->total_task_profit += $profit;
			
			if($this->tasksBudget[$parent_id]->ts->task_parent != $parent_id)
			{
					
				$this->Sumparent($parent_id, $this->tasksBudget[$parent_id]->ts->task_parent,$planned_investment,$planned_profit,$investment,$profit);
			}
		}
		else {
			echo 'SPCProjectBudget - > Sumparent() array_key_NOT_exists '.$task_id.'</br>';
			$this->AddTask($parent_id);
			$this->Sumparent($task_id,$parent_id,$planned_investment,$planned_profit,$investment,$profit);
			
		}
	}
	
	function AddTask($task_id) {
		$task_id=intval($task_id);
		if ($this->tasksBudget !=NULL)
		if(array_key_exists ( $task_id , $this->tasksBudget ))
		{
			echo 'SPCProjectBudget - > AddTask() array_key_exists '.$task_id.'</br>';
			return false;
		}
		$this->tasksBudget[$task_id] = new SPCtasktBudget($task_id, $this->displeytax,$this->budget_start_date,$this->budget_end_date);
		$planned_investment = $this->tasksBudget[$task_id]->get_task_planned_investment(1);
		$planned_profit = $this->tasksBudget[$task_id]->get_task_planned_profit(1);
		$investment = $this->tasksBudget[$task_id]->get_task_investment(1);
		$profit = $this->tasksBudget[$task_id]->get_task_profit(1);
		//echo 'task_id Add - >  '.$task_id;
		//echo '$planned_profit-> '.$planned_profit;
		//Sum project totalls ($mult = 1, $displaytax =  0, $symbol = "", $sep = "")
		$this->total_project_planned_investment += $planned_investment;
		$this->total_project_planned_profit += $planned_profit;
		$this->total_project_investment += $investment;
		$this->total_project_profit += $profit;
	//	echo '$total_project_planned_profit-> '.$this->total_project_planned_profit.'</br>';
	//	echo 'task_id Add - >  '.$task_id.'</br>';
	//	echo 'SPCBudget ID - >  '.$this->tasksBudget[$task_id]->bg->budget_id.'</br>';
	//	echo '$total_project_profit - >  '.$this->total_project_profit.'</br>';
	//	echo 'investment - >  '.$this->total_project_investment.'</br>';
		if($this->tasksBudget[$task_id]->ts->task_parent != $task_id)
		{
			
				$this->Sumparent($task_id, $this->tasksBudget[$task_id]->ts->task_parent,$planned_investment,$planned_profit,$investment,$profit);
		}
		return true;
	}
	
}
