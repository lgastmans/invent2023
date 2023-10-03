<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />

	<script language="javascript">

		function loadCategory(aFormname, aFieldname) {
			oSelectCategory = document.product_search.selCategory;
			document.location="../common/product_search.php?category_id="+oSelectCategory.options[oSelectCategory.selectedIndex].value+"&formname="+aFormname+"&fieldname="+aFieldname;
		}

		function loadList() {
			var oSelectItem = document.product_search.productList;

			arrListItems	= new Array(oSelectItem.options.length);
			arrListValues	= new Array(oSelectItem.options.length);

			for (i=0; i<oSelectItem.options.length; i++) {
				arrListItems[i] 	= oSelectItem.options[i].text;
				arrListValues[i]	= oSelectItem.options[i].value;
			}
		}

		function focusNext(aField, focusElem, evt) {
			evt = (evt) ? evt : event;
			var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
			
			if (charCode == 13 || charCode == 3) {
				alert('enter was pressed');
			}
			
			return true;
		}

		function searchFor() {
			var oSearch = document.product_search.strSearch;
			var oSelectItem = document.product_search.productList;
			var reFind = new RegExp(oSearch.value, "i");

			//clear the list
			oSelectItem.options.length = 0;

			//populate the list with matching items
			intFound = 0;
			for (i=0; i < arrListItems.length; i++) {
				searchIn = arrListItems[i];

				intPos = searchIn.search(reFind);
				if (intPos > 0) {
					intFound = intFound +1;
					oSelectItem.options.length = intFound-1;
					oSelectItem.options[intFound-1] = new Option(arrListItems[i],arrListValues[i]);
				}
			}
			if (intFound == 0)
				oSelectItem.options[0] = new Option('no matches found', 0);

			return true;
		}

		function selectItem(aFormname, aFieldname) {
			oSelectItem = document.product_search.productList;
			if (oSelectItem.selectedIndex != -1) {
				if (window.opener && !window.opener.closed) {
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
<body id='body_bgcolor' onload="javascript:loadList()">

<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");

	$int_supplier_id = 0;
	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];

	if (!empty($_GET["category_id"])) {
		$category_id=$_GET["category_id"];

		if ($category_id == "_ALL") {
			if ($int_supplier_id > 0)
				$str_products="SELECT product_id, product_code, product_description
					FROM stock_product
					WHERE (deleted = 'N')
						AND ((supplier_id = ".$int_supplier_id.")
						OR (supplier2_id = ".$int_supplier_id.")
						OR (supplier3_id = ".$int_supplier_id."))
					ORDER BY product_description";
			else
				$str_products="SELECT product_id, product_code, product_description
					FROM stock_product
					WHERE (deleted = 'N')
					ORDER BY product_description";
		}
		else {
			if ($int_supplier_id > 0)
				$str_products="SELECT product_id, product_code, product_description
					FROM stock_product
					WHERE (category_id=".$_GET["category_id"].")
						AND (deleted = 'N')
						AND ((supplier_id = ".$int_supplier_id.") OR
						(supplier2_id = ".$int_supplier_id.") OR
						(supplier3_id = ".$int_supplier_id."))
					ORDER BY product_description";
			else
				$str_products="SELECT product_id, product_code, product_description
					FROM stock_product
					WHERE (category_id=".$_GET["category_id"].")
						AND (deleted = 'N')
					ORDER BY product_description";
		}
		$qry_products = new Query($str_products);
	}
	else {
		if ($int_supplier_id > 0)
			$str_products="SELECT product_id, product_code, product_description
				FROM stock_product
				WHERE (deleted = 'N')
					AND ((supplier_id = ".$int_supplier_id.") OR
					(supplier2_id = ".$int_supplier_id.") OR
					(supplier3_id = ".$int_supplier_id."))
				ORDER BY product_description";
		else
			$str_products="SELECT product_id, product_code, product_description
				FROM stock_product
				WHERE (deleted = 'N')
				ORDER BY product_description";

		$qry_products = new Query($str_products);
		$category_id=0;
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
<form name="product_search" method="POST" action="" onsubmit="return false">

	<font class="normaltext">Search for :</font>
	<input type="text" name="strSearch" value="" class="input_200" onkeypress="return focusNext(this, 'list_rcvd_day', event)"><br><br>
	<font class="normaltext">In category :</font>
	<select name="selCategory" class="select_200" onchange="javascript:loadCategory('<?echo $formname."','".$fieldname?>')">
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

	<br><br><font class="normaltext">
	Double click an item to select it and exit</font>
	<br>

	<select name="productList" size="20" class='select_list_350' ondblclick="javascript:selectItem(<?echo "'".$formname."', '".$fieldname."'";?>)">
	<?
		for ($i=0;$i<$qry_products->RowCount();$i++) {
			echo "<option value=\"".$qry_products->FieldByName('product_code')."\">".
				StuffWithBlank($qry_products->FieldByName('product_code'), 10)." - ".
				$qry_products->FieldByName('product_description');
			$qry_products->Next();
		}
	?>
	</select>

	<br><br>

	<input type="submit" name="action" value="OK" class="settings_button" onclick="javascript:selectItem(<?echo "'".$formname."', '".$fieldname."'";?>)">
	<input type="submit" name="action" value="Cancel" class="settings_button" onclick="window.close()">

</form>
<script language="javascript">
document.product_search.strSearch.focus();
</script>
</body>
</html>