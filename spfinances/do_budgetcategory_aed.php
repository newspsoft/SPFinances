<?php /* SPFINANCES $Id: do_budgetcategory_aed.php 20.09.2013 $ */
/*
 * Copyright (c) 2014
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
require_once 'spbugetcategory.class.php';
$del = isset($_POST['del']) ? $_POST['del'] : 0;
//$dept
$category = new SPCBudgetCategory();
if (($msg = $category->bind($_POST))) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('SPFINANCES SPCBudgetCategory');
if ($del) {
	//$dep
	$cat = new SPCBudgetCategory();
	$msg = $cat->load($category->id);
	if (($msg = $category->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		$AppUI->setMsg("deleted", UI_MSG_ALERT, true);
		$AppUI->redirect('m=spfinances&a=addeditcategory&id='.$cat->id);
	}
} else {
	if (($msg = $category->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$isNotNew = @$_POST['id'];
		$AppUI->setMsg($isNotNew ? 'updated' : 'inserted', UI_MSG_OK, true);
	}
$AppUI->redirect();
}
?>
