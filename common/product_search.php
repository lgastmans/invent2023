<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");

	$int_supplier_id = 0;
	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	
	$category_id = 0;
	if (IsSet($_GET['search'])) {
		if (!empty($_GET["category_id"])) {
			$category_id=$_GET["category_id"];
			
			if ($category_id == "_ALL") {
				if ($int_supplier_id > 0)
					$str_products="
						SELECT product_id, product_code, product_description
						FROM stock_product
						WHERE (deleted = 'N')
							AND (product_description LIKE '%".$_GET['search_for']."%')
							AND ((supplier_id = ".$int_supplier_id.")
							OR (supplier2_id = ".$int_supplier_id.")
							OR (supplier3_id = ".$int_supplier_id."))
						ORDER BY product_description";
				else
					$str_products="
						SELECT product_id, product_code, product_description
						FROM stock_product
						WHERE (deleted = 'N')
							AND (product_description LIKE '%".$_GET['search_for']."%')
						ORDER BY product_description";
			}
			else {
				if ($int_supplier_id > 0)
					$str_products="
						SELECT product_id, product_code, product_description
						FROM stock_product
						WHERE (category_id=".$_GET["category_id"].")
							AND (deleted = 'N')
							AND ((supplier_id = ".$int_supplier_id.") OR
							(supplier2_id = ".$int_supplier_id.") OR
							(supplier3_id = ".$int_supplier_id."))
						ORDER BY product_description";
				else
					$str_products="
						SELECT product_id, product_code, product_description
						FROM stock_product
						WHERE (category_id=".$_GET["category_id"].")
							AND (deleted = 'N')
						ORDER BY product_description";
			}
			$qry_products = new Query($str_products);
		}
	}

	$qry_categories="SELECT category_id, category_description
		FROM stock_category
		WHERE (parent_category_id=0)
		ORDER BY category_description";
	$qry_categories = new Query($qry_categories);

	if (!IsSet($_GET["fieldname"]))
		$fieldname = 'none';
	else
		$fieldname = $_GET["fieldname"];

	if (!IsSet($_GET["formname"]))
		$formname = 'none';
	else
		$formname = $_GET["formname"];
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />

	<script language="javascript">
		
		function focusNext(aField, focusElem, evt) {
			evt = (evt) ? evt : event;
			var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
			
			if (charCode == 13 || charCode == 3) {
				var oSelectCategory = document.product_search.selCategory;
				var oSearch = document.product_search.strSearch;
				var str_url = "../common/product_search.php?"+
					"category_id="+oSelectCategory.options[oSelectCategory.selectedIndex].value+
					"&formname=<?echo $formname?>"+
					"&fieldname=<?echo $fieldname?>"+
					"&search=Y"+
					"&search_for="+oSearch.value;
				document.location = str_url;
			}
			
			return true;
		}
		
		function selectItem(aFormname, aFieldname) {
			oSelectItem = document.product_search.productList;
			if (oSelectItem.selectedIndex != -1) {
				
				var el = window.opener.document.getElementById('product_code')
				if (el) {				
					el.value = oSelectItem.options[oSelectItem.selectedIndex].value;
				}
				else if (window.opener && !window.opener.closed) {
					oTextBoxField = eval('window.opener.document.'+aFormname+'.'+aFieldname);
					oTextBoxField.value = oSelectItem.options[oSelectItem.selectedIndex].value;
					oTextBoxField.select();
					oTextBoxField.focus();
				}
				window.close();
			}
			else
				alert('Please select an item');
		}

	</script>
</head>
<body id='body_bgcolor'>

<form name="product_search" method="POST" action="" onsubmit="return false">

	<table width='100%' cellpadding='0' cellspacing='0'>
		<tr>
			<td class="normaltext" align='right'>Search for :&nbsp;</td>
			<td>
				<input type="text" name="strSearch" value="" class="input_200" onkeypress="return focusNext(this, 'list_rcvd_day', event)">
			</td>
		</tr>
		<tr>
			<td class="normaltext" align='right'>In category :&nbsp;</td>
			<td>
				<select name="selCategory" class="select_200">
					<option value="_ALL" <?if ($category_id==0) echo "selected=\"selected\"";?> >all
					<?
						for ($i=0;$i<$qry_categories->RowCount();$i++) {
							if ($qry_categories->FieldByName('category_id')==$category_id) {
								echo "<option value=\"".$qry_categories->FieldByName('category_id')."\" selected=\"selected\">".
									$qry_categories->FieldByName('category_description');
							}
							else {
								echo "<option value=\"".$qry_categories->FieldByName('category_id')."\">".
									$qry_categories->FieldByName('category_description');
							}
							$qry_categories->Next();
						}
					?>
				</select>
			</td>
		</tr>
	</table>

	<br><br><font class="normaltext">
	Double click an item to select it and exit</font>
	<br>

	<select name="productList" size="30" class='select_list_350' ondblclick="javascript:selectItem(<?echo "'".$formname."', '".$fieldname."'";?>)">
	<?
		if (IsSet($qry_products)) {
			for ($i=0;$i<$qry_products->RowCount();$i++) {
				echo "<option value=\"".$qry_products->FieldByName('product_code')."\">".
					StuffWithBlank($qry_products->FieldByName('product_code'), 10)." - ".
					$qry_products->FieldByName('product_description');
				$qry_products->Next();
			}
		}
	?>
	</select>

	<br><br>

	<input type="button" name="action" value="OK" class="settings_button" onclick="javascript:selectItem(<?echo "'".$formname."', '".$fieldname."'";?>)">
	<input type="button" name="action" value="Cancel" class="settings_button" onclick="window.close()">

</form>
<script language="javascript">
	document.product_search.strSearch.focus();
</script>
</body>
</html>