<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
        $int_cur_day = date('j');

	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account, can_bill_aurocard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_aurocard = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y')
		$bool_cash = true;
	if ($qry->FieldByName('can_bill_fs_account') == 'Y')
		$bool_fs = true;
	if ($qry->FieldByName('can_bill_pt_account') == 'Y')
		$bool_pt = true;
	if ($qry->FieldByName('can_bill_aurocard') == 'Y')
		$bool_aurocard = true;

        $qry_settings = new Query("
            SELECT bill_closing_time
            FROM user_settings
        ");
?>

<script language="javascript">

    function mouseGoesOver(element, aSource) {
	element.src = aSource;
    }

    function mouseGoesOut(element, aSource) {
	element.src = aSource;
    }

    function setSelectedDay() {
	var oTextBoxDays = document.DailySalesMenu.select_days;
	var oListBoxTime = document.DailySalesMenu.select_time;
	parent.frames["content"].document.location = "daily_sales.php?closing_time="+oListBoxTime.options[oListBoxTime.options.selectedIndex].value+"&selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
    }
    
    function setClosingTime() {
	var oTextBoxDays = document.DailySalesMenu.select_days;
	var oListBoxTime = document.DailySalesMenu.select_time;
	parent.frames["content"].document.location = "daily_sales.php?closing_time="+oListBoxTime.options[oListBoxTime.options.selectedIndex].value+"&selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
    }

    function printStatement() {
	var oTextBoxDays = document.DailySalesMenu.select_days;
	var oListBoxTime = document.DailySalesMenu.select_time;
	var str_dest = "daily_sales_print.php?closing_time="+oListBoxTime.options[oListBoxTime.options.selectedIndex].value+"&selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
	window.open(str_dest, "print_window");
    }
    
</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>
<form name="DailySalesMenu">
  <font class='normaltext'>
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
	Lunch closing time :
	<select name="select_time" onchange="javascript:setClosingTime()" class='select_100'>
            <option value="11:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '11:00:00') echo "selected"; ?>>11:00 AM
            <option value="11:30:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '11:30:00') echo "selected"; ?>>11:30 AM
            <option value="12:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '12:00:00') echo "selected"; ?>>12:00 PM
            <option value="12:30:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '12:30:00') echo "selected"; ?>>12:30 PM
            <option value="13:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '13:00:00') echo "selected"; ?>>1:00 PM
            <option value="13:30:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '13:30:00') echo "selected"; ?>>1:30 PM
            <option value="13:55:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '13:55:00') echo "selected"; ?>>1:55 PM
            <option value="14:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '14:00:00') echo "selected"; ?>>2:00 PM
	</select>
  </font>
    &nbsp;
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
</form>

<script language="javascript">
  var oTextBoxDays = document.DailySalesMenu.select_days;
  var oListBoxTime = document.DailySalesMenu.select_time;
  parent.frames["content"].document.location = "daily_sales.php?closing_time="+oListBoxTime.options[oListBoxTime.options.selectedIndex].value+"&selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
</script>

</body>
</html>