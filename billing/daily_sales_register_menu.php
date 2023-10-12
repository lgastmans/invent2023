<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	$int_cur_day = date('j');
  
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
	if (CAN_BILL_TRANSFER_GOOD === 1)
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

    function mouseGoesOver(element, aSource) {
	element.src = aSource;
    }

    function mouseGoesOut(element, aSource) {
	element.src = aSource;
    }

    function setSelectedDay() {
	var oTextBoxDays = document.DailySalesRegisterMenu.select_days;
	var oTextBoxType = document.DailySalesRegisterMenu.select_type;
	parent.frames["content"].document.location = "daily_sales_register.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value+"&selected_type="+oTextBoxType.options[oTextBoxType.options.selectedIndex].value;
    }
    
    function setSelectedType() {
	var oTextBoxDays = document.DailySalesRegisterMenu.select_days;
        var oTextBoxType = document.DailySalesRegisterMenu.select_type;
	parent.frames["content"].document.location = "daily_sales_register.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value+"&selected_type="+oTextBoxType.options[oTextBoxType.options.selectedIndex].value;
    }

    function printStatement() {
	var oTextBoxDays = document.DailySalesRegisterMenu.select_days;
	var oTextBoxType = document.DailySalesRegisterMenu.select_type;
	var str_dest = "daily_sales_register_print.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value+"&selected_type="+oTextBoxType.options[oTextBoxType.options.selectedIndex].value;
	window.open(str_dest, "print_window");
    }
</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>
<form name="DailySalesRegisterMenu">
  <font class='normaltext'>
  Type : 
  <select name="select_type" onchange="javascript:setSelectedType()" class='select_100'>
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
	Day of the month : 
	<select name="select_days" onchange="javascript:setSelectedDay()" class='select_100'>
		<?
			$int_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
			for ($i=1; $i<=$int_days; $i++) {
			 if ($i == $int_cur_day)
				echo "<option value=".$i." selected=\"selected\">".$i;
			 else
				echo "<option value=".$i.">".$i;
			}
		?>
	</select>
  </font>
  &nbsp;
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
</form>

<script language="javascript">
  oTextBoxDays = document.DailySalesRegisterMenu.select_days;
  oTextBoxType = document.DailySalesRegisterMenu.select_type;
  parent.frames["content"].document.location = "daily_sales_register.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value+"&selected_type="+oTextBoxType.options[oTextBoxType.options.selectedIndex].value;
</script>

</body>
</html>