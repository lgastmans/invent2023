<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$qry_supplier = new Query("
		SELECT supplier_id, supplier_name, supplier_phone
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

	function setText(evt, aField) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	    
		if (charCode == 13 || charCode == 3 || charCode == 9) {
			aField.select();
			setSupplier();
		}
		return true;
	}
    
	function setSupplier() {
		var oListSupplier = document.supplier_currentstock_menu.select_supplier;
		var oCheckTax = document.supplier_currentstock_menu.chk_tax;
		var oCheckValue = document.supplier_currentstock_menu.chk_value;
		var oListOrder = document.supplier_currentstock_menu.select_order;
		var oListStock = document.supplier_currentstock_menu.select_stock;
		var oCheckFilter = document.supplier_currentstock_menu.checkbox_filter;
		var oSelectFilter = document.supplier_currentstock_menu.select_filter;
		var oTextFilter = document.supplier_currentstock_menu.text_filter;
		var oCheckBPrice = document.supplier_currentstock_menu.chk_bprice;
		
		if (oCheckTax.checked)
			str_tax = 'Y';
		else
			str_tax = 'N';

		if (oCheckValue.checked)
			str_value = 'Y';
		else
			str_value = 'N';
			
		if (oCheckFilter.checked)
			str_filter = 'Y';
		else
			str_filter = 'N';
			
		if (oCheckBPrice.checked)
			str_bprice = 'Y';
		else
			str_bprice = 'N';
			
		var str_dest = "supplier_currentstock.php?supplier_id="+oListSupplier.options[oListSupplier.options.selectedIndex].value+
			"&include_tax="+str_tax+
			"&include_value="+str_value+
			"&order_by="+oListOrder.value+
			"&display_stock="+oListStock.value+
			"&is_filtered="+str_filter+
			"&filter_field="+oSelectFilter.value+
			"&filter_text="+oTextFilter.value+
			"&include_bprice="+str_bprice;
			
		var str_info = "supplier_currentstock_info.php?supplier_id="+oListSupplier.options[oListSupplier.options.selectedIndex].value+
			"&include_tax="+str_tax+
			"&include_value="+str_value+
			"&display_stock="+oListStock.value+
			"&is_filtered="+str_filter+
			"&filter_field="+oSelectFilter.value+
			"&filter_text="+oTextFilter.value+
			"&include_bprice="+str_bprice;
//alert(str_dest);
		parent.frames["content"].document.location = str_dest;
		parent.frames["info"].document.location = str_info;
	}

	function printStatement() {
		var oListSupplier = document.supplier_currentstock_menu.select_supplier;
		var oCheckTax = document.supplier_currentstock_menu.chk_tax;
		var oCheckValue = document.supplier_currentstock_menu.chk_value;
		var oListOrder = document.supplier_currentstock_menu.select_order;
		var oListStock = document.supplier_currentstock_menu.select_stock;
		var oCheckFilter = document.supplier_currentstock_menu.checkbox_filter;
		var oSelectFilter = document.supplier_currentstock_menu.select_filter;
		var oTextFilter = document.supplier_currentstock_menu.text_filter;
		var oCheckBPrice = document.supplier_currentstock_menu.chk_bprice;

		if (oCheckTax.checked)
			str_tax = 'Y';
		else
			str_tax = 'N';

		if (oCheckValue.checked)
			str_value = 'Y';
		else
			str_value = 'N';

		if (oCheckFilter.checked)
			str_filter = 'Y';
		else
			str_filter = 'N';
			
		if (oCheckBPrice.checked)
			str_bprice = 'Y';
		else
			str_bprice = 'N';

		var str_dest = "supplier_currentstock_print.php?supplier_id="+oListSupplier.options[oListSupplier.options.selectedIndex].value+
			"&include_tax="+str_tax+
			"&include_value="+str_value+
			"&order_by="+oListOrder.value+
			"&display_stock="+oListStock.value+
			"&is_filtered="+str_filter+
			"&filter_field="+oSelectFilter.value+
			"&filter_text="+oTextFilter.value+
			"&include_bprice="+str_bprice;
		
		myWin = window.open(str_dest, "print_window");
	}

	function exportStatement() {
		var oListSupplier = document.supplier_currentstock_menu.select_supplier;
		var oCheckTax = document.supplier_currentstock_menu.chk_tax;
		var oCheckValue = document.supplier_currentstock_menu.chk_value;
		var oListOrder = document.supplier_currentstock_menu.select_order;
		var oListStock = document.supplier_currentstock_menu.select_stock;
		var oCheckFilter = document.supplier_currentstock_menu.checkbox_filter;
		var oSelectFilter = document.supplier_currentstock_menu.select_filter;
		var oTextFilter = document.supplier_currentstock_menu.text_filter;
		var oCheckBPrice = document.supplier_currentstock_menu.chk_bprice;

		if (oCheckTax.checked)
			str_tax = 'Y';
		else
			str_tax = 'N';

		if (oCheckValue.checked)
			str_value = 'Y';
		else
			str_value = 'N';

		if (oCheckFilter.checked)
			str_filter = 'Y';
		else
			str_filter = 'N';
			
		if (oCheckBPrice.checked)
			str_bprice = 'Y';
		else
			str_bprice = 'N';

		var str_dest = "supplier_currentstock_export.php?supplier_id="+oListSupplier.options[oListSupplier.options.selectedIndex].value+
			"&include_tax="+str_tax+
			"&include_value="+str_value+
			"&order_by="+oListOrder.value+
			"&display_stock="+oListStock.value+
			"&is_filtered="+str_filter+
			"&filter_field="+oSelectFilter.value+
			"&filter_text="+oTextFilter.value+
			"&include_bprice="+str_bprice;
		
		myWin = window.open(str_dest, "export_window");
	}	
</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="supplier_currentstock_menu" onsubmit="return false">
  <font class='normaltext'>
	Supplier : 
	<select name="select_supplier" class='select_400'>
		<?
			for ($i=1; $i<=$qry_supplier->RowCount(); $i++) {
				$str_phone = '';
				if ($qry_supplier->FieldByName('supplier_phone') != '')
					$str_phone = " (".$qry_supplier->FieldByName('supplier_phone').")";
				if ($qry_supplier->FieldByName('supplier_id') == $_SESSION['global_current_supplier_id'])
				    echo "<option value=".$qry_supplier->FieldByName('supplier_id')." selected>".$qry_supplier->FieldByName('supplier_name').$str_phone;
				else
				    echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name').$str_phone;
				$qry_supplier->Next();
			}
		?>
		<option value="__ALL">All
	</select>
	&nbsp;
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;
	<a href="javascript:exportStatement()"><img src="../images/csv_export.png" border="0"></a>
	&nbsp;
	Order by:
	<select name="select_order" class='select_100'>
		<option value="product_code">Code
		<option value="product_description">Description
	</select>
	&nbsp;
	Show:
	<select name='select_stock' class='select_100'>
		<option value='All'>All
		<option value='Below Minimum'>Below Minimum
		<option value='Zero'>Zero
		<option value='Non-zero'>Non-zero
	</select>
	&nbsp;
	<input type='button' name='action' value='load' class='settings_button' onclick='javascript:setSupplier()'>
	
	<br>
	<input type='checkbox' name='checkbox_filter'> Filter:
	<select name='select_filter' class='select_100'>
		<option value='code'>Code</option>
		<!-- <option value='price'>S Price</option> -->
		<!-- <option value='bprice'>B Price</option> -->
		<option value='description'>Description</option>
	</select>
	<input type='text' name='text_filter' class='input_100' value='' onkeypress="return setText(event, this)">
	
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<label><input type="checkbox" name="chk_tax" checked>&nbsp;include tax columns</label>
	&nbsp;
	<label><input type="checkbox" name="chk_value" checked>&nbsp;include value columns</label>
	&nbsp;
	<label><input type="checkbox" name="chk_bprice" checked>&nbsp;include buying price</label>
	<br>
	<font style='font-family:Verdana,sans-serif;font-size:10px;font-weight:bold;'>Values in brackets indicate the global adjusted stock for that product</font>
</form>

</body>
</html>