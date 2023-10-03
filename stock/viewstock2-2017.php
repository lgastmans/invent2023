<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	$str_message = '';
	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
	 
			$can_save = true;
			
			if (IsSet($_POST['id'])) {
				//=====
				// edit
				//-----
				$int_id = $_POST["id"];
				
				if ($can_save) {
					$str_use_batch_price = 'N';
					if (IsSet($_POST['use_batch_price']))
						$str_use_batch_price = 'Y';
						
					$str_query ="
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET 
							stock_minimum = ".$_POST['minimum_quantity'].",
							buying_price = ".$_POST['buying_price'].",
							sale_price = ".$_POST['selling_price'].",
							use_batch_price = '".$str_use_batch_price."'
						WHERE product_id=".$int_id."
							AND storeroom_id=".$_SESSION['int_current_storeroom'];
					$qry_save = new Query($str_query);
					
					if ($qry_save->b_error == true) {
						$str_message = "error updating: ".mysql_error();
					}
					else {
						$_GET["id"]=null;
/*						echo "<script language='javascript'>\n";
						echo "document.getElementById('id').value='';\n";
						echo "window.opener.document.location=window.opener.document.location.href;";
						echo "window.close();";
						echo "</script>\n";*/
					}
				}
			}
		}
	}
	
	$flt_buying_price = 0;
	$flt_selling_price = 0;
	//$flt_point_value = 0;
	$str_use_batch_price = 'Y';
	$flt_minimum_quantity = 0;
	$code = '';
	$description = '';
	
	$int_id = -1;
	if (IsSet($_GET["id"])) {
		$int_id = $_GET['id'];
		
		$qry = new Query("
			SELECT ssp.*, sp.product_code, sp.product_description
			FROM ".Monthalize("stock_storeroom_product")." ssp
			INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
			WHERE ssp.product_id = $int_id 
				AND ssp.storeroom_id = ".$_SESSION['int_current_storeroom']
		);
		if ($qry->RowCount() == 0) {
			$str_message = "ERROR: Product not found.";
		}
		
		$code = $qry->FieldByName('product_code');
		$description = $qry->FieldByName('product_description');
		$flt_buying_price = $qry->FieldByName('buying_price');
		$flt_selling_price = $qry->FieldByName('sale_price');
		//$flt_point_value = $qry->FieldByName('point_price');
		$str_use_batch_price = $qry->FieldByName('use_batch_price');
		$flt_minimum_quantity = $qry->FieldByName('stock_minimum');
	}
	
?>

<body>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
	<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>

	<script language="javascript">
	
		function CloseWindow() {
			window.opener.document.location=window.opener.document.location.href;
			window.close();
		}
		
		function save_data() {
			document.product_edit.submit();
		}
	
		function getDescription(code) {
			var handleSuccess = function(o){
				//alert('success ' + o.responseText);
				res = o.responseText.split('|');
				
				oId = document.getElementById('id');
				oCode = document.getElementById('code');
				oDescription = document.getElementById('description');
				oBPrice = document.getElementById('buying_price');
				oSPrice = document.getElementById('selling_price');
				//oPValue = document.getElementById('point_value');
				oUseBPrice = document.getElementById('use_batch_price');
				oMinQty = document.getElementById('minimum_quantity');
				
				oId.value = res[15];
				oDescription.innerHTML = res[0];

				oBPrice.value = res[1];
				oSPrice.value = res[2];


				oBPrice.focus();
				
			}
			var handleFailure = function(o){
				alert("Hum, something went wrong!!!");
			}
			var callback = {
				success:handleSuccess,
				failure: handleFailure
			};
			var sUrl="transfers/productDetails.php?live=1&product_code="+code.value;
			var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
		}
	
		function focusNext(aField, focusElem, evt) {
			evt = (evt) ? evt : event;
			var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
			
			oCode = document.getElementById('code');
			oDescription = document.getElementById('description');
			oBPrice = document.getElementById('buying_price');
			oSPrice = document.getElementById('selling_price');
			//oPValue = document.getElementById('point_value');
			oUseBPrice = document.getElementById('use_batch_price');
			oMinQty = document.getElementById('minimum_quantity');
			oButtonSave = document.getElementById('button_save');
			
			if (charCode == 113) { // F2 Save
				oButtonSave.click();
			}
			else if (charCode == 13 || charCode == 3) {
				if (focusElem == 'buying_price') {
					oBPrice.select();
				}
			} 
			else if (charCode == 27) {
				clear_values();
				oCode.select();
			}	
			else if (charCode == 8) {
				if (focusElem == 'buying_price') {
					oBPrice.select();
				}
			}
			
			return true;
		}
		
		function clear_values() {
			oCode = document.getElementById('code');
			oDescription = document.getElementById('description');
			oBPrice = document.getElementById('buying_price');
			oSPrice = document.getElementById('selling_price');
			//oPValue = document.getElementById('point_value');
			oUseBPrice = document.getElementById('use_batch_price');
			oMinQty = document.getElementById('minimum_quantity');
			
			oCode.value = '';
			oDescription.innerHTML = '';
			oBPrice.value = '0';
			oSPrice.value = '0';
			//oPValue = '0';
			oUseBPrice.checked = false;
			oMinQty.value = '0';
			
		}
	</script>

</head>
<body id='body_bgcolor' leftmargin=10 topmargin=10>

<form name="product_edit" method="POST" onsubmit="return false">

<? if ($str_message != '') echo "<font color=\"red\">".$str_message."</font>";?>

	<table width="100%" height="30" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td class='normaltext' align="right" width="120">Code</td>
			<td><input type="text" id="code" name="code" class='input_200' value="<? echo $code;?>" autocomplete="OFF" onblur="javascript:getDescription(this)" onkeypress="focusNext(this, 'buying_price', event)"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td class='normaltext' >
				<div id="description"><? echo $description; ?></div>
			</td>
		</tr>
		<tr>
			<td class='normaltext' align="right" width="120">Buying Price</td>
			<td><input type="text" id="buying_price" name="buying_price" class='input_200' value="<? echo $flt_buying_price;?>" autocomplete="OFF" onkeypress="focusNext(this, 'selling_price', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right" width="120">Selling Price</td>
			<td><input type="text" id="selling_price" name="selling_price" class='input_200' value="<? echo $flt_selling_price;?>" autocomplete="OFF" onkeypress="focusNext(this, 'use_batch_price', event)"></td>
		</tr>
<!-- 		<tr>
			<td class='normaltext' align="right">Point Value</td>
			<td><input type="text" id="point_value" name="point_value" class='input_200' value="<? echo $flt_point_value;?>" autocomplete="OFF" onkeypress="focusNext(this, 'use_batch_price', event)"></td>
		</tr>
 -->		<tr>
			<td class='normaltext' align="right"></td>
			<td><input type="checkbox" id="use_batch_price" name="use_batch_price" <? if ($str_use_batch_price == 'Y') echo "checked";?>  onkeypress="focusNext(this, 'minimum_quantity', event)"><font class='normaltext'>Use batch price</font></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Minimum quantity</td>
			<td><input type="text" id="minimum_quantity" name="minimum_quantity" class='input_200' value="<? echo $flt_minimum_quantity;?>" autocomplete="OFF" onkeypress="focusNext(this, 'button_save', event)"></td>
		</tr>
		<tr>
			<td align="right">
				<input type="button" id="button_save" name="Save" value="Save" class='settings_button' onclick="save_data()">
			</td>
			<td>
				<input type="button" name="Close" value="Close" class='settings_button' onclick="CloseWindow()">
			</td>
		</tr>
	</table>
	
	<input type="hidden" id="id" name="id" value="<?echo $int_id;?>">
	<input type="hidden" name="action" value="save">
</form>


<script src="../include/js/jquery-3.2.1.min.js"></script>

<script language="javascript">
	document.product_edit.code.select();
	document.product_edit.code.focus();

</script>

</body>
</html>