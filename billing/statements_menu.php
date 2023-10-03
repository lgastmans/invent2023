<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$qry_supplier = new Query("
		SELECT supplier_id, supplier_name
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");
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
		var oListSupplier = document.StatementsMenu.select_supplier;
		var oListOrder = document.StatementsMenu.select_order;
		var oCheckBoxTax = document.StatementsMenu.checkbox_tax;
		var oSelectFormat = document.getElementById('select_format');
		var oSelectPrice = document.getElementById('price');
		var oDay = document.StatementsMenu.select_days;
		
		if (oCheckBoxTax.checked)
			str_include_tax = 'Y';
		else
			str_include_tax = 'N';
		
		var str_dest = "statements_print.php?supplier_id="+
			oListSupplier.options[oListSupplier.options.selectedIndex].value+
			"&include_tax="+str_include_tax +
			"&order_by="+oListOrder.value+
			"&format="+oSelectFormat.value+
			"&price="+oSelectPrice.value+
			"&filter_day="+oDay.value;
			
		window.open(str_dest, "print_window");
	}

	function setSupplier() {
		var oListSupplier = document.StatementsMenu.select_supplier;
		var oCheckBoxTax = document.StatementsMenu.checkbox_tax;
		var oDay = document.StatementsMenu.select_days;

		if (oCheckBoxTax.checked)
			str_include_tax = 'Y';
		else
			str_include_tax = 'N';
		
		parent.frames["content"].frames["content"].document.location = "statements_content.php?supplier_id="+
			oListSupplier.value+
			"&include_tax="+str_include_tax+
			"&filter_day="+oDay.value;
	}

	function loadStatement() {
		var oListSupplier = document.StatementsMenu.select_supplier;
		var oListOrder = document.StatementsMenu.select_order;
		var oCheckBoxTax = document.StatementsMenu.checkbox_tax;
		var oSelectFormat = document.getElementById('select_format');
		var oSelectPrice = document.getElementById('price');
		var oDay = document.StatementsMenu.select_days;
		
		if (oCheckBoxTax.checked)
			str_include_tax = 'Y';
		else
			str_include_tax = 'N';
		
		parent.frames["content"].frames["content"].document.location = "statements_content.php?supplier_id="+
			oListSupplier.value+
			"&include_tax="+str_include_tax+
			"&order_by="+oListOrder.value+
			"&format="+oSelectFormat.value+
			"&price="+oSelectPrice.value+
			"&filter_day="+oDay.value;
	}
	
	function emailStatement() {
		var oListSupplier = document.StatementsMenu.select_supplier;
		var oListOrder = document.StatementsMenu.select_order;
		var oCheckBoxTax = document.StatementsMenu.checkbox_tax;
		var oSelectFormat = document.getElementById('select_format');
		var oSelectPrice = document.getElementById('price');
		var oDay = document.StatementsMenu.select_days;
		
		if (oCheckBoxTax.checked)
			str_include_tax = 'Y';
		else
			str_include_tax = 'N';
		
		parent.frames["content"].frames["content"].document.location = "statements_email.php?supplier_id="+
			oListSupplier.value+
			"&include_tax="+str_include_tax+
			"&order_by="+oListOrder.value+
			"&format="+oSelectFormat.value+
			"&price="+oSelectPrice.value+
			"&filter_day="+oDay.value;
	}

	function exportStatement() {
		var oListSupplier = document.StatementsMenu.select_supplier;
		var oListOrder = document.StatementsMenu.select_order;
		var oCheckBoxTax = document.StatementsMenu.checkbox_tax;
		var oSelectFormat = document.getElementById('select_format');
		var oSelectPrice = document.getElementById('price');
		var oDay = document.StatementsMenu.select_days;
		
		if (oCheckBoxTax.checked)
			str_include_tax = 'Y';
		else
			str_include_tax = 'N';
		
		parent.frames["content"].frames["content"].document.location = "statements_export.php?supplier_id="+
			oListSupplier.value+
			"&include_tax="+str_include_tax+
			"&order_by="+oListOrder.value+
			"&format="+oSelectFormat.value+
			"&price="+oSelectPrice.value+
			"&filter_day="+oDay.value;
		}

	function toggleEnabled() {
		var oSelectFormat = document.getElementById('select_format');
		var oCheckTax = document.getElementById('checkbox_tax');
		if (oSelectFormat.value == 'DATE_BILL')
			oCheckTax.disabled = false;
		else
			oCheckTax.disabled = true;
	}
	
</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="StatementsMenu">
<font class='normaltext'>
	Supplier :
	<select name='select_supplier' class='select_400'>
		<?
			for ($i=1; $i<=$qry_supplier->RowCount(); $i++) {
				if ($qry_supplier->FieldByName('supplier_id') == $_SESSION['global_current_supplier_id'])
					echo "<option value=".$qry_supplier->FieldByName('supplier_id')." selected>".$qry_supplier->FieldByName('supplier_name');
				else
					echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name');
				$qry_supplier->Next();
			}
		?>
	</select>
	&nbsp;Order By:
	<select name='select_order' class='select_100'>
		<option value='date'>Date</option>
		<option value='code'>Code</option>
	</select>
	&nbsp;
	Day :
	<select name="select_days" class='select_100'>
		<option value="ALL">All</option>
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
	<label><input type='checkbox' name='checkbox_tax' id='checkbox_tax' checked>include tax column</label>
	&nbsp;
	<a href="javascript:exportStatement()"><img src="../images/csv_export.png"></a>
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;

	<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadStatement()'>
	<input type='button' name='action' value='email' class='settings_button' onclick='javascript:emailStatement()'>
	<br>

	&nbsp;&nbsp;Format :
	<select name="select_format" id="select_format" class="select_400" onchange="javascript:toggleEnabled()">
		<option value="DATE_BILL">date - bill number</option>
		<option value="PRODUCT_CATEGORY">group by poduct/category</option>
	</select>
	&nbsp;Price:
	<select name="price" id="price" class='select_100'>
		<option value="SP">Selling</option>
		<option value="BP" selected>Buying</option>
	</select>
<!--	<label>
		<input type="checkbox" name="deduct_commission">deduct the commission from the price
	</label>-->
</font>
</form>

</body>
</html>