<?
	require_once("../../include/const.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../include/session.inc.php");
	require_once("direct_transfer_funcs.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}
	
	$arr_storeroom_list = getStoreroomList();
	$str_message = '';

?>

<html>
<head>
	<title>Direct Transfer</title>
        <link rel="stylesheet" type="text/css" href="../../include/styles.css" />

<script language='javascript'>

	var can_save = false;
	var bool_is_decimal = false;

	function transferStock() {
	    if (can_save == true) {
		    document.direct_transfer.Save.onclick = '';
		    document.direct_transfer.submit();
	    }
	    else
		alert('Could not save');
	}
	
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

	function get_description(strProductCode) {
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
				var oTextCode = document.direct_transfer.code;
				var oSpanDescription = document.getElementById('description');
				var oSpanStock = document.getElementById('current_stock');
				
				str_retval = requester.responseText;
				
				if (str_retval == '__NOT_FOUND') {
					can_save = false;
					oTextCode.value = "";
					oSpanDescription.innerHTML = '';
					oSpanStock.innerHTML = '';
				}
				else {
					arr_details = str_retval.split('|');
					if (arr_details[6] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
					can_save = true;
					oSpanDescription.innerHTML = arr_details[0];
					oSpanStock.innerHTML = arr_details[4] + ' ' + arr_details[5];
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
		var oTextBoxCode = document.direct_transfer.code;
		var oTextQuantity = document.direct_transfer.quantity;
		var oSelectStoreroom = document.direct_transfer.select_storeroom;
		var oSelectDay = document.direct_transfer.select_day;
		var oButtonSave = document.direct_transfer.Save;
		var oTextRefNum = document.direct_transfer.ref_num;
		
		if (charCode == 113) { // F2 Save
			oButtonSave.click();
		}
		else if (charCode == 13 || charCode == 3) {
			if (focusElem == 'quantity') {
				oTextQuantity.focus();
			}
			else if (focusElem == 'select_storeroom') {
				oSelectStoreroom.focus();
			}
			else if (focusElem == 'select_day'){
				oSelectDay.focus();
			}
			else if (focusElem == 'ref_num') {
				oTextRefNum.focus();
			}
			else if (focusElem == 'button_save') {
				oButtonSave.focus();
			}
		} 
		else if (charCode == 27) {
			clearValues();
			oTextBoxCode.select();
		}	
		else if (charCode == 8) {
			if (focusElem == 'quantity') {
				oTextQuantity.focus();
			}
			else if (focusElem == 'select_storeroom') {
				oSelectStoreroom.focus();
			}
			else if (focusElem == 'select_day'){
				oSelectDay.focus();
			}
			else if (focusElem == 'ref_num') {
				oTextRefNum.focus();
			}
			else if (focusElem == 'button_save') {
				oButtonSave.focus();
			}
		}
		else if (charCode == 46) {
			if (focusElem == 'select_storeroom') {
				if (bool_is_decimal == false)
					return false;
			}
		}
		return true;
	}

	function clearValues() {
		var oTextBoxCode = document.direct_transfer.code;
			var oTextQuantity = document.direct_transfer.quantity;
			var oSpanDescription = document.getElementById('description');
			var oSpanStock = document.getElementById('current_stock');
			var oTextRefNum = document.direct_transfer.ref_num;
			
			oTextBoxCode.value = '';
			oTextQuantity.value = '';
			oSpanDescription.innerHTML = '';
			oSpanStock.innerHTML = '';
			oTextRefNum.value = '';
	}

	function openSearch() {
		myWin = window.open("../../common/product_search.php?formname=direct_transfer&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.focus();
	}
</script>

</head>

<?
if (IsSet($_POST["action"])) {
	if ($_POST["action"] == "save") {
	
		$can_save = true;
		$str_code = $_POST['code'];
		$flt_quantity = $_POST['quantity'];
		$int_day = $_POST['select_day'];
		$int_storeroom_id = $_POST['select_storeroom'];
		$str_ref_num = $_POST['ref_num'];
		$str_message = '';
		$int_product_id = -1;
		
		$qry = new Query("SELECT * FROM stock_product WHERE product_code = '".$str_code."' AND (deleted = 'N')");
		if ($qry->RowCount() > 0) {
			$int_product_id = $qry->FieldByName('product_id');
		}
		else {
			$can_save = false;
			$str_message = "Could not retrieve the product id";
		}
		
		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
			$str_message = '';
		}
		else {
			$str_message = 'Cannot transfer in previous months. \\n Select the current month/year and continue.';
			$can_save = false;
		}
		
		if ($can_save) {
			//===================================
			// START TRANSACTION
			//-----------------------------------
			$qry_transfer = new Query("START TRANSACTION");
			
			//===================================
			// DEDUCT STOCK FROM SOURCE STOREROOM
			//-----------------------------------
			$str_retval = deduct_stock($int_product_id, $flt_quantity, $int_storeroom_id, $int_day, $str_ref_num);
			$arr_retval = explode('|', $str_retval);
			if ($arr_retval[0] == 'OK')
				$bool_success = true;
			else {
				$bool_success = false;
				$str_message = $arr_retval[1];
			}
			
			//===================================
			// ADD STOCK TO DESTINATION STOREROOM
			//-----------------------------------
			if ($bool_success) {
				$str_retval = add_stock($int_product_id, $int_storeroom_id, $flt_quantity, $int_day, $str_ref_num);
				$arr_retval = explode('|', $str_retval);
				if ($arr_retval[0] == 'OK') {
					$bool_success = true;
				}
				else {
					$bool_success = false;
					$str_message = $arr_retval[1];
				}
			}
			
			//===================================
			// FINALIZE TRANSACTION
			//-----------------------------------
			if ($bool_success) {
				$qry_transfer->Query("COMMIT");
			}
			else {
				$qry_transfer->Query("ROLLBACK");
			}
		}
	}
}

?>


<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=7 marginheight=7>
<form name="direct_transfer" method="POST" onsubmit="return false">

<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../../images/blank.gif");

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
			<td width='550px'><input type="text" class="input_100" name="code" value="" autocomplete="OFF" onblur="javascript:get_description(this)" onkeypress="focusNext(this, 'quantity', event)">&nbsp;<a href="javascript:openSearch()"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		<tr>
			<td align="right" width='100px' class="normaltext_bold"></td>
			<td class="spantext" width="200px" id="description">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" width='100px' class="normaltext_bold">Stock</td>
			<td class="spantext" id="current_stock">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Quantity</td>
			<td><input type="text" class="input_100" name="quantity" value="" autocomplete="OFF" onkeypress="focusNext(this, 'select_day', event)"></td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Day</td>
			<td>
				<select name="select_day" onkeypress="focusNext(this, 'select_storeroom', event)" class='select_100'>
				<?
					$int_num_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
					for ($i=1; $i<=$int_num_days; $i++) {
						if ($i == date('j'))
							echo "<option value=".$i." selected=\"selected\">".$i;
						else
							echo "<option value=".$i.">".$i;
				}
				?>
				</select>
				<font class="normaltext_bold">
					<? echo getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]."&nbsp;"; ?>
				</font>
			</td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">To</td>
			<td>
				<select name='select_storeroom' onkeypress="focusNext(this, 'ref_num', event)" class='select_100'>
				<?
					foreach ($arr_storeroom_list as $key=>$value) {
							if ($key != $_SESSION['int_current_storeroom'])
								echo "<option value='$key'>$value</option>\n";
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td align="right" class="normaltext_bold">Reference<br>number</td>
			<td>
				<input type="text" class="input_100" name="ref_num" value="" autocomplete="OFF" onkeypress="focusNext(this, 'button_save', event)">
			</td>
		</tr>
		<tr>
			<td align="right">
				<input type="hidden" name="action" value="save">
				<? if ($int_access_level > ACCESS_READ) { ?>
					<input type="button" class="mainmenu_button" name="Save" value="Save" onclick="transferStock()">
				<? } else { ?>
					&nbsp;
				<? } ?>
			</td>
			<td>
				<input type="button" class="mainmenu_button" name="Close" value="Close" onclick="CloseWindow()">
			</td>
		</tr>
	</table>
<?
    boundingBoxEnd("400", "../../images/blank.gif");
?>

</td></tr>
</table>


</form>

<script language="javascript">
	document.direct_transfer.code.focus();
</script>

</body>
</html>