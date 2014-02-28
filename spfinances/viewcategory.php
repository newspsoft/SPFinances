<?php  /* SPFINANCES viewcategory.php, v 0.1.0 20.09.2013 */
/*
* Copyright (c) 2014 
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
// Get the global var
global $dPconfig, $m;

// Check permissions for this module
if (!getPermission($m, 'view')) 
	$AppUI->redirect("m=public&a=access_denied");
	
$canAdd = getPermission($m, 'add');

$AppUI->savePlace();

$titleBlock = new CTitleBlock('SPFinances view budget category', 'helpdesk.png', $m, "$m.$a");
if ($canAuthor) {
	$titleBlock->addCrumb('?m=spfinances', 'View FINANCES');
}
	if ($canAdd) {
		$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new category')
				. '">'), '', '<form action="?m=spfinances&amp;a=addeditcategory" method="post">',
				'</form>');
}
$titleBlock->show();

// Include the necessary classes
require_once $AppUI->getModuleClass('spfinances');

$obj = new SPCBudgetCategory();
// retrieve list of records
$q  = new DBQuery;
$q->addTable('spbudget_typ', 't');
$q->addQuery('*');
$q->addWhere(' parent_id = 0 ');
$q->addOrder( ' position, id ' );
$rows = $q->loadList();

$none = true;
$s .= '<tr><th>'.$AppUI->_('Category Name').'</th>'
      .'<th>'.$AppUI->_('Sub Category Name').'</th>'
	  .'<th>'.$AppUI->_('Parent').'</th>'
	  .'<th>'.$AppUI->_('Is Investment').'</th>'
	  .'<th>'.$AppUI->_('Position').'</th>'
	  .'<th>'.$AppUI->_('Edit').'</th>'
	  .'</tr>';
foreach ($rows as $row) {
	$none = false;
	$s .= '<tr><td>'.dPformSafe($row['title']).'</td>';
	//dPformSafe($row['id'])
	$s .= '<td></td>';
	$s .= '<td></td>';
	$s .= '<td></td>';
	$s .= '<td></td>';
	$s .= ('<td><a href="?m=spfinances&amp;a=addeditcategory&amp;id=' . dPformSafe($row['id']) . '">' 
		       . $AppUI->_('Edit'). '</a></td>');
	$s .= '</tr>';
	
	$q->clear();
	$q->addTable('spbudget_typ', 't');
	$q->addQuery('*');
	$q->addWhere(' parent_id = '.$row[id]);
	$q->addOrder( ' position, id ' );
	$rowsubs = $q->loadList();
	foreach ($rowsubs as $rows1) {
		$s .= '<tr><td></td>';
		//dPformSafe($row['id'])
		$s .= '<td>'.dPformSafe($rows1['title']).'</td>';
		$s .= '<td>'.dPformSafe($obj->get_ParentCategory_Name($rows1[parent_id])).'</td>';
		$s .= '<td></td>';
		$s .= '<td></td>';
		$s .= ('<td><a href="?m=spfinances&amp;a=addeditcategory&amp;id=' . dPformSafe($rows1['id']) . '">'
				. $AppUI->_('Edit'). '</a></td>');
		$s .= '</tr>';
	}
}

if ($none) {
	$s .= '<tr><td>'.$AppUI->_('No budget category available').'<br />'.$AppUI->getMsg().'</td></tr>';
}

echo ('<table cellpadding="2" cellspacing="1" border="0" width="100%" class="tbl">'
		. $s . '</table>');