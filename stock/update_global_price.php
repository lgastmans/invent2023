<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}

?>

<html>
<head>
	<title>Update Global Price</title>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	
	<script language='javascript'>

		var can_save = false;

		function CloseWindow() {
			if (window.opener)
				window.opener.document.location=window.opener.document.location.href;
			window.close();
		}

		function createRequest() {
			try {
				var requester = new XMLHttpRequest();
			}
			catch (error) {
				try {
					requester = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch (error) {
					return false;
				}
			}
			return requester;
		}
		
		var requester = createRequest();
		
		function getDescription(strProductCode) {
			var strPassValue = '';
			if (strProductCode.value == '')
				strPassValue = 'nil'
			else {
				requester.onreadystatechange = stateHandler;
				strPassValue = strProductCode.value;
				requester.open("GET", "product_details.php?live=1&product_code="+strPassValue);
				requester.send(null);
			}
		}

		function stateHandler() {
			if (requester.readyState == 4) {
				if (requester.status == 200)  {
					var oTextCode = document.update_global_price.code;
					var oTextDescription = document.getElementById('description');
					var oTextBoxStock = document.getElementById('current_stock');
					var oTextMargin = document.getElementById('margin');
					var oTextBPrice = document.update_global_price.buying_price;
					var oTextSPrice = document.update_global_price.selling_price;
					
					str_retval = requester.responseText;
					
					if (str_retval == '__NOT_FOUND') {
					  can_save = false;
						oTextCode.value = "";
						oTextDescription.innerHTML = '';
						oTextMargin.innerHTML = '';
						oTextBoxStock.innerHTML = '';
					}
					else {
						arr_details = str_retval.split('|');
					
						if (arr_details[10] == "__NOT_AVAILABLE") {
							can_save = false;
								oTextDescription.innerHTML = '';
								oTextBoxStock.innerHTML = '';
								oTextMargin.innerHTML = '';
								alert('This product cannot be received.\n It has been disabled');
								oTextCode.focus();
						}
						else {
							can_save = true;
							
							oTextDescription.innerHTML = arr_details[0];
							oTextBoxStock.innerHTML = arr_details[4];
							oTextMargin.innerHTML = arr_details[3];
							oTextBPrice.value = arr_details[1];
							oTextSPrice.value = arr_details[2];
						}
					}
				}
				else {
					alert("failed to get description... please try again.");
				}
				requester = null;
				requester = createRequest();
			}
		}


		function focusNext(aField, focusElem, evt) {
			evt = (evt) ? evt : event;
			var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
			
			var oTextBoxCode = document.update_global_price.code;
			var oTextBoxBPrice = document.update_global_price.buying_price;
			var oTextBoxSPrice = document.update_global_price.selling_price;
			var oButtonSave = document.update_global_price.Save;
			
			if (charCode == 113) { // F2 Save
				oButtonSave.click();
			}
			else if (charCode == 13 || charCode == 3) {
				if (focusElem == 'buying_price') {
					oTextBoxBPrice.focus();
//					oTextBoxBPrice.select();
				}
				else if (focusElem == 'selling_price') {
					oTextBoxSPrice.focus();
//					oTextBoxSPrice.select();
				}
				else if (focusElem == 'button_save') {
					oButtonSave.focus();
				}
			} 
			else if (charCode == 27) {
				oTextBoxCode.select();
				clearValues;
			}	
			else if (charCode == 8) {
				if (focusElem == 'buying_price') {
					oTextBoxBPrice.focus();
//					oTextBoxBPrice.select();
				}
				else if (focusElem == 'selling_price') {
					oTextBoxSPrice.focus();
//					oTextBoxSPrice.select();
				}
				if (focusElem == 'button_save') {
					oButtonSave.focus();
				}
			}
			return true;
		}  

		function clearValues() {
			var oTextBoxCode = document.update_global_price.code;
			var oTextBoxDescription = document.getElementById('description');
			var oTextBoxStock = document.getElementById('current_stock');
			var oTextBoxBPrice = document.update_global_price.buying_price;
			var oTextBoxSPrice = document.update_global_price.selling_price;
			
			oTextBoxCode.value = '';
			oTextBoxDescription.innerHTML = '';
			oTextBoxStock.innerHTML = '';
			oTextBoxBPrice.value = '0.0';
			oTextBoxSPrice.value = '0.0';
		}
		
		function update_price() {
			if (can_save == true) {
				document.update_global_price.Save.onclick = '';
				document.update_global_price.submit();
			}
		}

	function openSearch() {
		myWin = window.open("../common/product_search.php?formname=update_global_price&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.focus();
	}
	</script>
</head>

<?
	$str_message = '';
	
	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
			$can_save = true;
			$int_product_id = 0;
			$str_product_code = $_POST["code"];
			$flt_buying_price = $_POST["buying_price"];
			$flt_selling_price = $_POST['selling_price'];

			$qry = new Query("
				SELECT *
				FROM stock_product
				WHERE (product_code = '".$str_product_code."')
					AND (deleted = 'N')
			");
			if ($qry->RowCount() == 0) {
				$str_message = "product code not found";
				$can_save = false;
			}
			else {
				$int_product_id = $qry->FieldByName('product_id');
			}
			
			if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
				$str_message = '';
			}
			else {
				$str_message = 'Cannot update prices in previous months. \\n Select the current month/year and continue.';
				$can_save = false;
			}
			
			if ($can_save) {
				
				$str_query = "
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET buying_price = ".$flt_buying_price.",
						sale_price = ".$flt_selling_price."
					WHERE product_id = ".$int_product_id."
						AND storeroom_id = ".$_SESSION['int_current_storeroom'];
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					$str_message = "Error updating the prices";
				}
			}
		}
	}
?>

<body id='body_bgcolor'>
<form name="update_global_price" method="POST" onsubmit="return false">

<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../images/blank.gif");

	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?
	}
?>
	<table width="98%" height="30" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td align="right" width='100px' class="normaltext_bold">Code</td>
			<td width='300px'><input type="text" class="input_100" name="code" value="" autocomplete="OFF" onblur="javascript:getDescription(this)" onkeypress="focusNext(this, 'buying_price', event)">&nbsp;<a href="javascript:openSearch()"><img src="../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold"></td>
			<td class="spantext" id="description">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Current Stock</td>
			<td class="spantext" id="current_stock">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Margin</td>
			<td class="spantext" id="margin">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Buying Price</td>
			<td><input type="text" class="input_100" name="buying_price" value="" autocomplete="OFF" onkeypress="return focusNext(this, 'selling_price', event)"></td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Selling Price</td>
			<td><input type="text" class="input_100" name="selling_price" value="" autocomplete="OFF" onkeypress="return focusNext(this, 'button_save', event)"></td>
		</tr>
		<tr>
			<td align="right">
				<input type="hidden" name="action" value="save">
				<input type="button" class="mainmenu_button" name="Save" value="Save" onclick="update_price()">
			</td>
			<td>
				<input type="button" class="mainmenu_button" name="Close" value="Close" onclick="CloseWindow()">
			</td>
		</tr>
	</table>
<?
    boundingBoxEnd("400", "../images/blank.gif");
?>

</td></tr>
</table>

</form>

<script language="javascript">
	document.update_global_price.code.focus();
</script>

</body>
</html>