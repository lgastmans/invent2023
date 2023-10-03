<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    
    $int_cur_day = date('j');
 
?>

<script language="javascript">

    function mouseGoesOver(element, aSource) {
	element.src = aSource;
    }

    function mouseGoesOut(element, aSource) {
	element.src = aSource;
    }

    function setSelectedDay() {
	var oTextBoxDays = document.PurchaseSalesRegisterMenu.select_days;
	parent.frames["content"].document.location = "purchase_sales_register_content.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
    }
    

    function printStatement() {
	var oTextBoxDays = document.PurchaseSalesRegisterMenu.select_days;
	var str_dest = "purchase_sales_register_print.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
	window.open(str_dest, "print_window");
    }
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>
<form name="PurchaseSalesRegisterMenu">
  <font class='normaltext'>
  &nbsp;
    Day of the month : 
    <select name="select_days" onchange="javascript:setSelectedDay()" class='select_100'>
	<option value='ALL'>All
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
  oTextBoxDays = document.PurchaseSalesRegisterMenu.select_days;
  parent.frames["content"].document.location = "purchase_sales_register_content.php?selected_day="+oTextBoxDays.options[oTextBoxDays.options.selectedIndex].value;
</script>

</body>
</html>