<?php /* SPFINANCES spbugetcategory.class.php, v 0.1.0 20.09.2013 */
/*
* Copyright (c) 2014 
*
* Description:	PHP function page of the SPFinances module.
* 
* Author:		Stepan Poghosyan, <stepanpoghosyan@newspsoft.ru>
* WEB: http://newspsoft.ru/
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

class SPCBudgetCategory extends CDpObject {
	var $id = NULL;
	var $parent_id = 0;
	var $investment = 0;//In futere Use
	var $title = '';
	var $position = 0;
		
	
	function SPCBudgetCategory() {
		$this->CDpObject('spbudget_typ', 'id'); //
	}
	
	function get_ParentCategory_Name($parid){
		$fid=$this->parent_id;
		if($parid)
			$fid=intval($parid);
		$q = new DBQuery;
		$q->addTable('spbudget_typ','t');
		$q->addQuery('t.title');
		$q->addWhere('t.id = '.$fid);
		return $q->loadResult();
	}
	
	function check() {
		
		// TODO MORE
		if ($this->id && $this->id == $this->parent_id) {
			return "cannot make myself my own parent (" . $this->id . "=" . $this->parent_id . ")";
		}
		if (empty($this->title)) {
			return 'Category name cannot be blank';
		}
		return NULL; // object is ok
	}
	
	function bind($hash) {
		if (!is_array($hash)) {
			return get_class($this)."::bind failed";
		} else {
			bindHashToObject($hash, $this);
			return NULL;
		}
	}

	// overload canDelete
	function canDelete(&$msg, $oid=null) {
		//echo 'canDelete - > AddTask()  '.$this->id.'</br>';
		global $AppUI;
		// format [label => 'Label', name => 'table name', idfield => 'field', joinfield => 'field']
	//	$tables[] = array('label' => 'spfinances', 'name' => 'spbudget', 'idfield' => 'budget_id', 'joinfield' => 'investment_id');
	//	$tables[] = array('label' => 'spfinances', 'name' => 'spbudget', 'idfield' => 'budget_id', 'joinfield' => 'profit_id');
		// call the parent class method to assign the oid
		//return CDpObject::canDelete($msg, $oid, $tables);
		//$msg = $AppUI->_('noDeletePermission');
		//return false;
		$k = $this->id;
		if ($oid) {
			$k = intval($oid);
		}
		$q = new DBQuery();
		$q->addTable('spbudget_typ');
		$q->addQuery('COUNT(*)');
		$q->addWhere('parent_id = '.$k);
		$count=$q->loadResult();
		if($count != 0){
			$msg = $AppUI->_('CategoryHaveChild').'  Count='.$count;
			return false;
		}
		$q->clear();
		$q->addTable('spbudget');
		$q->addQuery('COUNT(*)');
		$q->addWhere('profit_id = '.$k);
		$q->addWhere('investment_id = '.$k);
		$count=$q->loadResult();
		if($count != 0){
			$msg = $AppUI->_('HaveBudgetforCategory').'  Count='.$count;
			return false;
		}
		return true;
	}
	
}
