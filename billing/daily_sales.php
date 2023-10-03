<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("bill_funcs.inc.php");
	
	if (IsSet($_GET["selected_day"]))
		$int_cur_day = $_GET["selected_day"];
	else
		$int_cur_day = date('j');
	
	if (IsSet($_GET["closing_time"]))
		$str_closing_time = $_GET["closing_time"];
	else
		$str_closing_time = "12:00:00";
		
	$_SESSION["int_bills_menu_selected"] = 7;

	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account, can_bill_creditcard, can_bill_aurocard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_cc = false;
	$bool_auro = false;
	$bool_upi = true;
	$int_cols = 1;
	if ($qry->FieldByName('can_bill_cash') == 'Y') {
		$bool_cash = true;
		$int_cols++;
	}
	if ($qry->FieldByName('can_bill_fs_account') == 'Y') {
		$bool_fs = true;
		$int_cols++;
	}
	if ($qry->FieldByName('can_bill_pt_account') == 'Y') {
		$bool_pt = true;
		$int_cols++;
	}
	if ($qry->FieldByName('can_bill_creditcard') == 'Y') {
		$bool_cc = true;
		$int_cols++;
	}
	if ($qry->FieldByName('can_bill_aurocard') == 'Y') {
		$bool_auro = true;
		$int_cols++;
	}
	/*
		UPI is true, add one column
	*/
		$int_cols++; 

	$int_cols++;
	

//	function get_item_totals($str_include_tax, $str_time_constrained, $is_morning, $day_of_month, $supplier_type, $int_bill_type) {

	$flt_cash_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_CASH);
	$flt_cash_morning_consignment = get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_CASH);
	$flt_cash_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_CASH);
	$flt_cash_evening_consignment = get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_CASH);
	$flt_cash_morning_total =	$flt_cash_morning_consignment + $flt_cash_morning_direct;
	$flt_cash_evening_total = 	$flt_cash_evening_consignment + $flt_cash_evening_direct;

	$flt_fs_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_ACCOUNT);
	$flt_fs_morning_consignment = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_ACCOUNT);
	$flt_fs_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_ACCOUNT);
	$flt_fs_evening_consignment = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_ACCOUNT);
	$flt_fs_morning_total = 	$flt_fs_morning_direct + $flt_fs_morning_consignment;
	$flt_fs_evening_total = 	$flt_fs_evening_direct + $flt_fs_evening_consignment;

	$flt_pt_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_PT_ACCOUNT);
	$flt_pt_morning_consignment = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_PT_ACCOUNT);
	$flt_pt_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_PT_ACCOUNT);
	$flt_pt_evening_consignment = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_PT_ACCOUNT);
	$flt_pt_morning_total = 	$flt_pt_morning_consignment + $flt_pt_morning_direct;
	$flt_pt_evening_total = 	$flt_pt_evening_consignment + $flt_pt_evening_direct;
	
	$flt_cc_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_CREDIT_CARD);
	$flt_cc_morning_consignment = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_CREDIT_CARD);
	$flt_cc_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_CREDIT_CARD);
	$flt_cc_evening_consignment = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_CREDIT_CARD);
	$flt_cc_morning_total = 	$flt_cc_morning_consignment + $flt_cc_morning_direct;
	$flt_cc_evening_total = 	$flt_cc_evening_consignment + $flt_cc_evening_direct;
	
	$flt_auro_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_AUROCARD);
	$flt_auro_morning_consignment = get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_AUROCARD);
	$flt_auro_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_AUROCARD);
	$flt_auro_evening_consignment = get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_AUROCARD);
	$flt_auro_morning_total =	$flt_auro_morning_consignment + $flt_auro_morning_direct;
	$flt_auro_evening_total = 	$flt_auro_evening_consignment + $flt_auro_evening_direct;


	$flt_upi_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_UPI);
	$flt_upi_morning_consignment = get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_UPI);
	$flt_upi_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_UPI);
	$flt_upi_evening_consignment = get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_UPI);
	$flt_upi_morning_total =	$flt_upi_morning_consignment + $flt_upi_morning_direct;
	$flt_upi_evening_total = 	$flt_upi_evening_consignment + $flt_upi_evening_direct;


	$flt_morning_direct_total = 	$flt_cash_morning_direct + $flt_fs_morning_direct + $flt_pt_morning_direct + $flt_cc_morning_direct + $flt_auro_morning_direct + $flt_upi_morning_direct;
	$flt_morning_consignment_total = $flt_cash_morning_consignment + $flt_fs_morning_consignment + $flt_pt_morning_consignment + $flt_cc_morning_consignment + $flt_auro_morning_consignment + $flt_upi_morning_consignment;
	$flt_evening_direct_total = 	$flt_cash_evening_direct + $flt_fs_evening_direct + $flt_pt_evening_direct + $flt_cc_evening_direct + $flt_auro_evening_direct + $flt_upi_evening_direct;
	$flt_evening_consignment_total = $flt_cash_evening_consignment + $flt_fs_evening_consignment + $flt_pt_evening_consignment + $flt_cc_evening_consignment + $flt_auro_evening_consignment + $flt_upi_evening_consignment;
	
	$flt_sales_promotion =		get_sales_promotion($int_cur_day);

?>

<html>
<body>
<form name="DailySales" method="GET">
  <font style="font-family:Verdana,sans-serif;">
  
	<table border=1 cellpadding=7 cellspacing=0>
		<tr>
			<td>&nbsp;</td>
			<? if ($bool_cash == true) { ?> <td>Cash</td> <? } ?>
			<? if ($bool_fs == true) { ?> <td>Financial Service</td> <? } ?>
			<? if ($bool_pt == true) { ?> <td>Pour Tous</td> <? } ?>
			<? if ($bool_cc == true) { ?> <td>Credit Card</td> <? } ?>
			<? if ($bool_auro == true) { ?> <td>Aurocard</td> <? } ?>
			<? if ($bool_upi == true) { ?> <td>UPI</td> <? } ?>
			<td>Total</td>
		</tr>
	    
		<tr>
			<td colspan=<?echo $int_cols?>><b>Morning Sales</b></td>
		</tr>
		<tr>
			<td align='right'>Direct</td>
			<? if ($bool_cash == true) { ?> <td align="right">
				<? echo number_format($flt_cash_morning_direct, 2, '.', ',');
			?></td> <? } ?>
			<? if ($bool_fs == true) { ?> <td align="right">
				<? echo number_format($flt_fs_morning_direct, 2, '.', ',');
			?></td> <? } ?>
			<? if ($bool_pt == true) { ?> <td align="right">
				<? echo number_format($flt_pt_morning_direct, 2, '.', ',');
			?></td> <? } ?>
			<? if ($bool_cc == true) { ?> <td align="right">
				<? echo number_format($flt_cc_morning_direct, 2, '.', ',');
			?></td> <? } ?>
			<? if ($bool_auro == true) { ?> <td align="right">
				<? echo number_format($flt_auro_morning_direct, 2, '.', ',');
			?></td> <? } ?>
			<? if ($bool_upi == true) { ?> <td align="right">
				<? echo number_format($flt_upi_morning_direct, 2, '.', ',');
			?></td> <? } ?>
			<td align='right'><? echo number_format($flt_morning_direct_total, 2, '.', ',');?></td>
		</tr>
		<tr>
			<td align='right'>Consignment</td>
			<? if ($bool_cash == true) { ?> <td align="right">
				<? echo number_format($flt_cash_morning_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_fs == true) { ?> <td align="right">
				<? echo number_format($flt_fs_morning_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_pt == true) { ?> <td align="right">
				<? echo number_format($flt_pt_morning_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_cc == true) { ?> <td align="right">
				<? echo number_format($flt_cc_morning_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_auro == true) { ?> <td align="right">
				<? echo number_format($flt_auro_morning_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_upi == true) { ?> <td align="right">
				<? echo number_format($flt_upi_morning_consignment, 2, '.', '');
			?></td> <? } ?>
			<td align='right'><? echo number_format($flt_morning_consignment_total, 2, '.', ',');?></td>
		</tr>
    
		<tr>
			<td align='right'>&nbsp;</td>
			<? if ($bool_cash == true) { ?>
				<td align='right'><?echo number_format($flt_cash_morning_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_fs == true) { ?>
				<td align='right'><?echo number_format($flt_fs_morning_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_pt == true) { ?>
				<td align='right'><?echo number_format($flt_pt_morning_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_cc == true) { ?>
				<td align='right'><?echo number_format($flt_cc_morning_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_auro == true) { ?>
				<td align='right'><?echo number_format($flt_auro_morning_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_upi == true) { ?>
				<td align='right'><?echo number_format($flt_upi_morning_total, 2, '.', '')?></td>
			<? } ?>
			<td align='right'>&nbsp;</td>
		</tr>
    
		<tr>
			<td colspan=<?echo $int_cols?>><b>Evening Sales</b></td>
		</tr>
		<tr>
			<td align='right'>Direct</td>
			<? if ($bool_cash == true) { ?> <td align="right">
				<? echo number_format($flt_cash_evening_direct, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_fs == true) { ?> <td align="right">
				<? echo number_format($flt_fs_evening_direct, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_pt == true) { ?> <td align="right">
				<? echo number_format($flt_pt_evening_direct, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_cc == true) { ?> <td align="right">
				<? echo number_format($flt_cc_evening_direct, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_auro == true) { ?> <td align="right">
				<? echo number_format($flt_auro_evening_direct, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_upi == true) { ?> <td align="right">
				<? echo number_format($flt_upi_evening_direct, 2, '.', '');
			?></td> <? } ?>
			<td align='right'><? echo number_format($flt_evening_direct_total, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td align='right'>Consignment</td>
			<? if ($bool_cash == true) { ?> <td align="right">
				<? echo number_format($flt_cash_evening_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_fs == true) { ?> <td align="right">
				<? echo number_format($flt_fs_evening_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_pt == true) { ?> <td align="right">
				<? echo number_format($flt_pt_evening_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_cc == true) { ?> <td align="right">
				<? echo number_format($flt_cc_evening_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_auro == true) { ?> <td align="right">
				<? echo number_format($flt_auro_evening_consignment, 2, '.', '');
			?></td> <? } ?>
			<? if ($bool_upi == true) { ?> <td align="right">
				<? echo number_format($flt_upi_evening_consignment, 2, '.', '');
			?></td> <? } ?>
			<td align='right'><? echo number_format($flt_evening_consignment_total, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td align='right'>&nbsp;</td>
			<? if ($bool_cash == true) { ?>
				<td align='right'><?echo number_format($flt_cash_evening_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_fs == true) { ?>
				<td align='right'><?echo number_format($flt_fs_evening_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_pt == true) { ?>
				<td align='right'><?echo number_format($flt_pt_evening_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_cc == true) { ?>
				<td align='right'><?echo number_format($flt_cc_evening_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_auro == true) { ?>
				<td align='right'><?echo number_format($flt_auro_evening_total, 2, '.', '')?></td>
			<? } ?>
			<? if ($bool_upi == true) { ?>
				<td align='right'><?echo number_format($flt_upi_evening_total, 2, '.', '')?></td>
			<? } ?>
			<td align='right'>&nbsp;</td>
		</tr>
    
		<tr>
			<td colspan=<?echo $int_cols-1?>><b>Sales Promotion</b></td>
			<td align="right">
				<? echo number_format($flt_sales_promotion, 2, '.', '');?>
			</td>
		</tr>
	</table>
	
	</font>
</form>
</body>
</html>