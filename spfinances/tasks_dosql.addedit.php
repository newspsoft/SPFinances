<?php /* SPFINANCES tasks_dosql.addedit.php, v 0.1.0 26.02.2014 */
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

// Set the pre and post save functions
global $pre_save, $post_save, $spbudget;

require_once $AppUI->getModuleClass('spfinances');

$pre_save[] = "spfinances_presave";
$post_save[] = "spfinances_postsave";

/**
 * presave functions are called before the session storage of tab data
 * is destroyed.  It can be used to save this data to be used later in
 * the postsave function.
 */
function spfinances_presave() {
	global $spbudget;
	$spbudget = new SPCBudget();
	$spbudget->Tax					= dPgetParam($_POST, 'TVASP');
	$spbudget->only_financial 		= (dPgetParam($_POST, 'only_spfinancial') == 'on' ? 1 : 0);
	$spbudget->display_tax			= dPgetParam($_POST, 'display_sptax');
	if($spbudget->display_tax){
		$spbudget->investment	=	number_format(dPgetParam($_POST, 'investment')/($spbudget->Tax/100 + 1),2,'.','');
		$spbudget->investment_id	=	number_format(dPgetParam($_POST, 'investment_id')/($spbudget->Tax/100 + 1),2,'.','');
		$spbudget->profit		=	number_format(dPgetParam($_POST, 'profit')/($spbudget->Tax/100 + 1),2,'.','');
		$spbudget->profit_id	=	number_format(dPgetParam($_POST, 'profit_id')/($spbudget->Tax/100 + 1),2,'.','');
	}else{
		$spbudget->investment	=	dPgetParam($_POST, 'investment');
		$spbudget->investment_id	=	dPgetParam($_POST, 'investment_id');
		$spbudget->profit		=	dPgetParam($_POST, 'profit');
		$spbudget->profit_id	=	dPgetParam($_POST, 'profit_id');
	}
}

/**
 * postsave functions are only called after a succesful save.  They are
 * used to perform database operations after the event.
 */
function spfinances_postsave()
{
	global $spbudget;
	global $obj;
  
	$spbudget->task_id = $obj->task_id;
	dprint(__FILE__, __LINE__, 5, "saving budget");
	$spbudget->store();
}
?>
