<?php /* SPFINANCES tasks_tab.view.task_detailed_budget.php, v 0.1.0 27.09.2013  */
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
global $AppUI, $spbudget, $task_id; //$budget-> $spbudget

// Include the necessary classes
require_once $AppUI->getModuleClass('spfinances');

// Load the corresponding Budget
$spbudget = new SPCBudget();
$spbudget->loadFromTask($task_id);
?>
<script type="text/javascript" src="./modules/spfinances/js/spfinances.js"></script>
<table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
<tr>
	<td valign="top" align="center">
		<?php echo $AppUI->_("Display"); ?>: 
		<label for="currency0"><input type="radio" name="currency" id="currency0" value=0 onChange="javascript:updateBudgetView()" <?php if($spbudget->bestCurrency() == 0) echo "checked"; ?>/><?php echo $dPconfig['currency_symbol']; ?></label>
		<label for="currency1"><input type="radio" name="currency" id="currency1" value=1 onChange="javascript:updateBudgetView()" <?php if($spbudget->bestCurrency() == 1) echo "checked"; ?>/><?php echo "k".$dPconfig['currency_symbol']; ?></label>
		<label for="currency2"><input type="radio" name="currency" id="currency2" value=2 onChange="javascript:updateBudgetView()" <?php if($spbudget->bestCurrency() == 2) echo "checked"; ?>/><?php echo "M".$dPconfig['currency_symbol']; ?></label>
		<br/><?php echo $AppUI->_("Amounts"); ?>: 	
		<label for="tax0"><input type="radio" name="tax" id="tax0" value=0 onChange="javascript:updateBudgetView()" <?php if($spbudget->display_tax == 0) echo "checked"; ?> /><?php echo $AppUI->_("without tax"); ?></label>
		<label for="tax1"><input type="radio" name="tax" id="tax1" value=1 onChange="javascript:updateBudgetView()" <?php if($spbudget->display_tax == 1) echo "checked"; ?> /><?php echo $AppUI->_("with tax"); ?></label>
		<input type="hidden" id="hinvestment" value="<?php echo $spbudget->investment;?>"/>
		<input type="hidden" id="hinvestment_id" value="<?php echo $spbudget->investment_id;?>"/>
		<input type="hidden" id="hprofit" value="<?php echo $spbudget->profit;?>"/>
		<input type="hidden" id="hprofit_id" value="<?php echo $spbudget->profit_id;?>"/>
		<table cellspacing="0" cellpadding="3" border="1">
			<tr align="center">
				<td colspan="4"><?php echo $AppUI->_("Tax"); ?>: <?php echo $spbudget->Tax;?>&#37;</td>
			</tr>
			<tr align="center">
				<td colspan="4">
					<input type="checkbox" class="checkbox" <?php if($spbudget->only_financial) echo 'checked="checked"';?> name="only_financial" id="only_financial" disabled="disabled"/><?php echo $AppUI->_('This task is only financial'); ?>
				</td>
			</tr>
			<tr align="right">
				<td><?php echo $AppUI->_('Investment'); ?>: </td>
				<td class="hilite" width="150px" id="investment"><?php echo $spbudget->get_investment().$dPconfig['currency_symbol'];?></td>
				<td><?php echo $AppUI->_('Category'); ?>: </td>
				<td class="hilite" width="150px" id="Categoryinvestment"><?php echo $spbudget->get_Investmentcategory_Name(); ?></td>
			</tr>
			<tr align="right">
				<td><?php echo $AppUI->_('Profit'); ?>: </td>
				<td class="hilite" width="150px" id="profit"><?php echo $spbudget->get_profit().$dPconfig['currency_symbol']; ?></td>
				<td><?php echo $AppUI->_('Category'); ?>: </td>
				<td class="hilite" width="150px" id="Categoryprofit"><?php echo $spbudget->get_Profitcategory_Name(); ?></td>
			</tr>
			<tr align="center">
				<td colspan="4" class="hilite" id="total"><?php echo $spbudget->get_investment()+$spbudget->get_profit().$dPconfig['currency_symbol']; ?></td>
			</tr>
		</table>
	</td>
</tr>
</table>
<script language="javascript" type="text/javascript">
// Definition of function that need PHP
function getBudgetCurrency(){
	var radios = document.getElementsByName("currency");
	if(radios[2].checked)
		return "M<?php echo $dPconfig['currency_symbol']; ?>";
	if(radios[1].checked)
		return "k<?php echo $dPconfig['currency_symbol']; ?>";
	return "<?php echo $dPconfig['currency_symbol']; ?>";
}
function checkAmountId(id){
	var radios = document.getElementsByName("currency");
	if(isNaN(parseFloat(document.getElementById("h"+id).value.replace(/ /g,''))))
		document.getElementById(id).innerHTML = 0;
	var val = parseFloat(document.getElementById("h"+id).value.replace(/ /g,''))
	var tax = document.getElementsByName("tax");
	if(tax[1].checked)
		val *= <?php echo str_replace(",",".",1+$spbudget->Tax/100); ?>;
	var radios = document.getElementsByName("currency");
	if(radios[2].checked)
		document.getElementById(id).innerHTML = addThousandsSep((parseFloat(val)/1000000).toFixed(2),' ')+getBudgetCurrency();
	else if(radios[1].checked)
		document.getElementById(id).innerHTML = addThousandsSep((parseFloat(val)/1000).toFixed(2),' ')+getBudgetCurrency();
	else document.getElementById(id).innerHTML = addThousandsSep(parseFloat(val).toFixed(2),' ')+getBudgetCurrency();
}
function updateBudgetView(){
	// Set correct format
	checkAmountId("investment");
	checkAmountId("profit");
	
	// Compute sums
	var inv = parseFloat(document.getElementById("investment").innerHTML.replace(/ /g,''));
	var pro = parseFloat(document.getElementById("profit").innerHTML.replace(/ /g,''));
	
	var sum = inv+pro;
	document.getElementById("total").innerHTML = addThousandsSep(sum.toFixed(2),' ')+getBudgetCurrency();
}

// Launch first update
updateBudgetView();
</script>

