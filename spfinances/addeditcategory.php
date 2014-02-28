<?php  /* SPFINANCES addeditcategory.php, v 0.1.0 20.09.2013 */
/*
* Copyright (c) 2014 
*
* Author:		Stepan Poghosyan, <stepanpoghosyan@newspsoft.ru>
* WEB http://newspsoft.ru/
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

$id = intval(dPgetParam($_GET, 'id', 0));

// check permissions for this module
// If the category exists we need edit permission,
// If it is a new category we need add permission on the module.
if ($id) {
  $canEdit = getPermission($m, 'edit');
} else {
  $canEdit = getPermission($m, 'add');
}

if (!$canEdit) {
	$AppUI->redirect('m=public&a=access_denied');
}

$canDelete = getPermission($m, 'delete');

$del=0;
if ($id<=0)
	$canDelete = 0;

// Include the necessary classes
require_once $AppUI->getModuleClass('spfinances');
require_once 'spbugetcategory.class.php';

$msg = '';

$row = new SPCBudgetCategory();
if (!$row->load($id) && $id > 0) {
	$AppUI->setMsg('Buget Category');
	$AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
	$AppUI->redirect();
}

// collect all the frest level Categorys for the Category parent list
$q = new DBQuery;
$q->addTable('spbudget_typ','t');
$q->addQuery('t.id, t.title');
$q->addOrder('id');
$q->addWhere('t.parent_id = 0');
$parents = $q->loadHashList();

// get the list of permitted companies
$parents = arrayMerge(array('0' => $AppUI->_('None')), $parents);
$canDeleterow = false;
if($canDelete)
	$canDeleterow = $row->canDelete($msg, $id);
// echo 'canDelete - > '.$canDeleterow.'</br>';
// setup the title block
$ttl = $id > 0 ? 'Edit Category' : 'Add Category';
$titleBlock = new CTitleBlock( $ttl, 'helpdesk.png', $m, "$m.$a");
$titleBlock->addCrumb('?m=spfinances&amp;a=viewcategory', 'View budget category');

if ($canDelete && $id) 
	$titleBlock->addCrumbDelete('delete Category', $canDelete, $msg);
	
$titleBlock->show();




?>

<script language="javascript" type="text/javascript">
function submitIt() {
	var form = document.changeBudgetCategory;
	if (form.title.value.length < 3) {
		alert("<?php echo $AppUI->_('CategoryValidName', UI_OUTPUT_JS); ?>");
		form.title.focus();
	} else {
		form.submit();
	}
}

function delIt() {
	<?php
			if ($canDeleterow) {
	
			 
			if ($userDeleteProtect) {
			?>
				alert("<?php echo $AppUI->_('CategoryDeleteUserError', UI_OUTPUT_JS);?>");
			<?php
			} else {
			?>
				var form = document.changeBudgetCategory;
				if (confirm("<?php echo $AppUI->_('CategoryDelete', UI_OUTPUT_JS);?>")) {
					form.del.value = "<?php echo id;?>";
					form.submit();
				}
			<?php
			} 
			}else {?>
				alert("<?php echo $AppUI->_('CategoryDeleteError '.$msg , UI_OUTPUT_JS);?>");
				<?php
}
			?>
}
</script>
<form name="changeBudgetCategory" action="?m=spfinances" method="post">
<input type="hidden" name="dosql" value="do_budgetcategory_aed" />
<input type="hidden" name="del" value="0" />
<input type="hidden" name="id" value="<?php echo $id;?>" />

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std" summary="contact information">
<tr>
<td colspan="2">
<table border="0" cellpadding="1" cellspacing="1" summary="contact name">
<tr>
<td align="right"><?php echo $AppUI->_('Category Name');?>:</td>
			<td>
				<input type="text" class="text" size="25" name="title" value="<?php echo dPformSafe(@$row->title);?>" maxlength="50" />
			</td>
		</tr>
		<tr>
		<td align="right"><?php echo $AppUI->_('Parent Category'); ?>:</td>
		<td>
	<?php
		echo arraySelect($parents, 'parent_id', 'size="1" class="text"', 
                 ((@$row->parent_id) ? $row->parent_id : 0));
	?>
		</td>
	</tr>
		<tr>
			<td align="right" width="100"><label for="investment"><?php echo $AppUI->_('Investment Or profit');?>:</label> </td>
			<td>
				<input type="checkbox" value="1" name="investment" id="investment" <?php echo (@$row->investment ? 'checked="checked"' : '');?> />
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td>
		<input type="button" value="<?php echo $AppUI->_('back');?>" class="button" onclick="javascript:window.location='./index.php?m=spfinances';" />
	</td>
	<td align="right">
		<input type="button" value="<?php echo $AppUI->_('submit');?>" class="button" onclick="javascript:submitIt()" />
	</td>
</tr>
</table>
</form>
