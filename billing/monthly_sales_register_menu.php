<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account, can_bill_creditcard, can_bill_aurocard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
        $bool_transfer = false;
	$bool_creditcard = false;
	$bool_aurocard = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y')
		$bool_cash = true;
	if ($qry->FieldByName('can_bill_fs_account') == 'Y')
		$bool_fs = true;
	if ($qry->FieldByName('can_bill_pt_account') == 'Y')
		$bool_pt = true;
        if (CAN_BILL_TRANSFER_GOOD)
            $bool_transfer = true;
	if ($qry->FieldByName('can_bill_creditcard') == 'Y')
	    $bool_creditcard = true;
	if ($qry->FieldByName('can_bill_aurocard') == 'Y')
	    $bool_aurocard = true;
	$bool_upi = true;
	$bool_bank_transfer = true;

	// check whether the orders module is enabled
	// and include in list if it is
	$qry->Query("SELECT * FROM module WHERE module_id = 7 AND active='Y'");
	if ($qry->RowCount() > 0)
		$bool_orders = true;
?>

<script language="javascript">

	function mouseGoesOver(element, aSource)
	{
		element.src = aSource;
	}
	
	function mouseGoesOut(element, aSource)
	{
		element.src = aSource;
	}

	function printStatement() {
		oTextBoxType = document.MonthlySalesRegisterMenu.select_type;
		oSelectView = document.MonthlySalesRegisterMenu.select_view;
		var oSelectDay = document.MonthlySalesRegisterMenu.select_day;
		
		if (oSelectView.value == 'categories')
			str_dest = "monthly_sales_register_category_print.php?"+
				"selected_type=" +oTextBoxType.options[oTextBoxType.options.selectedIndex].value +
				"&selected_day="+oSelectDay.value;
		else
			str_dest = "monthly_sales_register_print.php?"+
				"selected_type=" +oTextBoxType.options[oTextBoxType.options.selectedIndex].value +
				"&selected_view="+oSelectView.value+
				"&selected_day="+oSelectDay.value;
		
		window.open(str_dest, "print_window");
	}

	function loadRegister() {
		var oTextBoxType = document.MonthlySalesRegisterMenu.select_type;
		var oSelectView = document.MonthlySalesRegisterMenu.select_view;
		var oSelectDay = document.MonthlySalesRegisterMenu.select_day;
		
		if (oSelectView.value == 'categories')
			str_url = "monthly_sales_register_category.php?"+
				"selected_type="+oTextBoxType.options[oTextBoxType.options.selectedIndex].value+
				"&selected_day="+oSelectDay.value+
				"&action=load";
		else {
			str_url = "monthly_sales_register.php?"+
				"selected_type="+oTextBoxType.options[oTextBoxType.options.selectedIndex].value+
				"&selected_view="+oSelectView.value+
				"&selected_day="+oSelectDay.value+
				"&action=load";
		}
		parent.frames["content"].document.location = str_url;
	}

</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>

<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>
<form name="MonthlySalesRegisterMenu">
	<font class='normaltext'>
	Day :
	<select name='select_day' class='select_100'>
	    <option value='ALL'>All
	    <?
		$int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
		for ($i=1; $i<=$int_num_days; $i++) {
		    echo "<option value='".$i."'>".$i;
		}
	    ?>
	</select>
	&nbsp;&nbsp;Type : 
	<select name="select_type" class='select_100'>
		<option value="ALL">All
		<? if ($bool_cash == true) { ?>
			<option value="<? echo BILL_CASH?>">Cash
		<? } ?>
		<? if ($bool_fs == true) { ?>
			<option value="<? echo BILL_ACCOUNT?>">Account
		<? } ?>
		<? if ($bool_pt == true) { ?>
			<option value="<? echo BILL_PT_ACCOUNT?>">PT Account
		<? } ?>
		<? if ($bool_transfer == true) { ?>
			<option value="<? echo BILL_TRANSFER_GOOD?>">Transfer of Goods
		<? } ?>
		<? if ($bool_creditcard == true) { ?>
			<option value="<? echo BILL_CREDIT_CARD?>">Credit Card
		<? } ?>
		<? if ($bool_orders == true) { ?>
			<option value="ORDERS">Orders
		<? } ?>
		<? if ($bool_aurocard == true) { ?>
			<option value="<? echo BILL_AUROCARD?>">Aurocard</option>
		<? } ?>
			<option value="<?php echo BILL_UPI;?>">UPI</option>
			<option value="<?php echo BILL_BANK_TRANSFER;?>">Bank Transfer</option>
	</select>
	&nbsp;
	View :
	<select name="select_view" class='select_100'>
		<option value="salestax">Salestax</option>
		<option value="totals">Totals</option>
		<option value="categories">Categories</option>
	</select>
	</font>
	&nbsp;
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;
	<input type='button' name='action' value='Load' class='settings_button' onclick='javascript:loadRegister()'>
</form>

</body>
</html>