<?
	require_once("../include/const.inc.php");
	require_once("include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("module.inc.php");
	require_once('db_params.php');
	require_once("db_funcs.inc.php");	
	
	/*
		get the list of years and months
	*/
	$arr_years = getTableYears();
	$int_cur_day = date('j');
	
	$_SESSION["int_stock_selected"] = 9;
	
	$qry_types = new Query("
		SELECT *
		FROM stock_transfer_type
		ORDER BY transfer_type_description
	");
	
	$arr_months = getFSMonths();
?>

<script language="javascript">

	function mouseGoesOver(element, aSource) {
		element.src = aSource;
	}
	
	function mouseGoesOut(element, aSource) {
		element.src = aSource;
	}
	
	function setText(evt, aField) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	
		if (charCode == 13 || charCode == 3 || charCode == 9) {
		aField.select();
		apply_settings('screen');
		}
		return true;
	}
    
	function apply_settings(aDestination) {
	
		var oTextCode = document.stock_registry_month.product_code;
		var oSelectFrom = document.stock_registry_month.select_from;
		var oSelectTo = document.stock_registry_month.select_to;
	
		if (aDestination == 'screen') {
			str_url = "stock_registry_month_frameset.php?"+
				"product_code="+oTextCode.value+
				"&filter_from="+oSelectFrom.value+
				"&filter_to="+oSelectTo.value;
			
			parent.frames["stock_registry_month"].document.location = str_url;
		}
		else {
			str_url = "stock_registry_print.php?"+
				"product_code="+oTextCode.value+
				"&filter_from="+oSelectFrom.value+
				"&filter_to="+oSelectTo.value;
			
			window.open(str_url, "print_window");
		}
	}
	
	function printStatement() {
		apply_settings('printer');
	}
	
</script>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="stock_registry_month" onsubmit="return false">

	<font class='normaltext'>
	
		Code:
		<input type="text" name="product_code" value="" onkeypress="return setText(event, this)"  class='input_100'>
		&nbsp;
		
		From :
		<select name="select_from" onchange="javascript:apply_settings('screen')" class='select_200'>
		<?
			foreach ($arr_months as $key=>$value) {
				echo "<option value=$key>$value";
			}
		?>
		</select>
		
		To :
		<select name="select_to" onchange="javascript:apply_settings('screen')" class='select_200'>
		<?
			foreach ($arr_months as $key=>$value) {
				echo "<option value=$key>$value";
			}
		?>
		</select>
		
		&nbsp;
		
	</font>
	&nbsp;
	
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
</form>

</body>
</html>