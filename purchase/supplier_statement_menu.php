<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$_SESSION["int_purchase_menu_selected"] = 7;
	
	$int_access_level = (getModuleAccessLevel('Purchase'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$qry_supplier = new Query("
		SELECT supplier_id, supplier_name
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");
?>

<script language='javascript'>

	function mouseGoesOver(element, aSource) {
		element.src = aSource;
	}
	
	function mouseGoesOut(element, aSource)	{
		element.src = aSource;
	}
	
	function loadStatement() {
		var oSelectSupplier = document.supplier_statement_menu.select_supplier;
		
		str_url = 'supplier_statement_content.php?'+
		    'supplier_id='+oSelectSupplier.value;
		
		parent.frames['supplier_statement_content'].document.location = str_url;
	}
	
	function printStatement() {
		var oSelectSupplier = document.supplier_statement_menu.select_supplier;
		
		str_url = 'supplier_statement_print.php?'+
		    'supplier_id='+oSelectSupplier.value;
		
		window.open(str_url);
	}
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="supplier_statement_menu" onsubmit="return false">

    <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
		<td width='95px' align='right' class='normaltext'>Supplier</td>
		<td>
		    <select name="select_supplier" class='select_400'>
			    <option value='ALL'>All
			    <?
				    for ($i=1; $i<=$qry_supplier->RowCount(); $i++) {
					    echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name');
					    $qry_supplier->Next();
				    }
			    ?>
		    </select>
		</td>
		<td width='60px' align='center'>
			<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
		</td>
		<td>
			<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadStatement()'>
		</td>
	</tr>
    </table>
	
</form>
</body>
</html>