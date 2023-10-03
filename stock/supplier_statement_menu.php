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

	function loadData() {
		var oListSupplier = document.supplier_statement_menu.select_supplier;
		var oTextBoxCode = document.supplier_statement_menu.product_code;
		var oListPrice = document.supplier_statement_menu.select_price;
		parent.frames["content"].frames["header"].document.location = "supplier_statement_header.php?display_price="+oListPrice.value;
		parent.frames["content"].frames["content"].document.location = "supplier_statement_content.php?supplier_id="+oListSupplier.options[oListSupplier.options.selectedIndex].value+"&product_code="+oTextBoxCode.value+"&display_price="+oListPrice.value;
	}

	function printStatement() {
		var oListSupplier = document.supplier_statement_menu.select_supplier;
		var oTextBoxCode = document.supplier_statement_menu.product_code;
		var oListPrice = document.supplier_statement_menu.select_price;

		var str_dest = "supplier_statement_print.php?supplier_id="+oListSupplier.options[oListSupplier.options.selectedIndex].value+"&product_code="+oTextBoxCode.value+"&display_price="+oListPrice.value;
		myWin = window.open(str_dest, "print_window");
	}

</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="supplier_statement_menu" onsubmit="return false">

  <font class='normaltext'>
	
	Supplier : 
	<select name="select_supplier" id="supplier" class='select_400'>
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
	&nbsp;

	<select name="stmnt" id="stmnt" class="select_100">
		<option value="ALL">All</option>
		<option value="Direct">Direct</option>
		<option value="Purchase">Purchase</option>
	</select>
	&nbsp;

	Code :
	<input type="text" name="product_code" id="code" class='input_100'>
	&nbsp;

	Price :
	<select name="select_price" id="price" class='select_200'>
		<option value="B">Buying Price
		<option value="S">Selling Price
	</select>
	&nbsp;

	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;

	<input type='button' id="load" name='action' value='load' class='settings_button' > <!-- onclick='javascript:loadData()'> -->
	
	<br>

	<font style="font-size:10px;font-weight:bold">Note: Quantities returned to supplier are marked in red.</font>

</form>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>

   <script>

		$(document).ready(function(){

			$(" #load ").click(function(){

				var supplier = $(" #supplier ").val();
				var stmnt = $(" #stmnt ").val();
				var code = $(" #code ").val();
				var price = $(" #price ").val();

				//console.log('click ' + stmnt + ':' + supplier  + ':' + code + ':' + price);

				if (stmnt=='Purchase') {

					parent.frames["content"].frames["header"].document.location = "../blank_grey.htm";//"supplier_statement_header.php?display_price="+price;
					
					parent.frames["content"].frames["content"].document.location = "supplier_statement_purchase.php?supplier_id="+supplier+"&product_code="+code+"&display_price="+price;

				}

				else {

					parent.frames["content"].frames["header"].document.location = "supplier_statement_header.php?display_price="+price;

					parent.frames["content"].frames["content"].document.location = "supplier_statement_content.php?supplier_id="+supplier+"&product_code="+code+"&display_price="+price + "&stmnt="+stmnt;
				}

				
				

			});

		});

	</script>


</body>
</html>