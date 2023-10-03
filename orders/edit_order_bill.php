<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("order_bill_deliver.php");

	//====================
	// process GET variables
	//--------------------
	$int_bill_id = 0;
	if (IsSet($_GET['id'])) {
		$int_bill_id = $_GET['id'];
	}

	$str_message = '';

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'save') {

			$bool_success = true;
			
			if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
				$str_message = '';
			}
			else {
				$str_message = 'Cannot edit order bills in previous months. \\n Select the current month/year and continue.';
				$bool_success = false;
			}
			
			if ($bool_success) {
				$qry_bill_item = new Query("START TRANSACTION");
				
				$qry_product = new Query("SELECT * FROM stock_product LIMIT 1");
				
				//====================
				// remove items from the table that may have been deleted
				//====================
				$qry_bill_item->Query("
					SELECT *
					FROM ".Monthalize('bill_items')."
					WHERE (bill_id = ".$int_bill_id.")
				");
				
				$arr_existing = array();
				for ($i=0; $i<$qry_bill_item->RowCount(); $i++) {
					$arr_existing[] = $qry_bill_item->FieldByName('product_id');
					$qry_bill_item->Next();
				}
				
				$arr_updated = array();
				for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
					$arr_updated[] = $_SESSION['arr_order_items'][$i][0];
				}
				
				$arr_deleted = array_diff($arr_existing, $arr_updated);
				
				foreach ($arr_deleted as $key=>$value) {
					$qry_bill_item->Query("
						SELECT *
						FROM ".Monthalize('bill_items')."
						WHERE (product_id = ".$value.")
							AND (bill_id = ".$int_bill_id.")
					");
					if ($qry_bill_item->RowCount() > 0) {
						$flt_ordered = $qry_bill_item->FieldByName('quantity');
						
						$qry_bill_item->Query("
							UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_reserved = ROUND(stock_reserved - ".$flt_ordered.", 3)
							WHERE product_id = ".$value."
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
					}
					
					$qry_bill_item->Query("
						DELETE FROM ".Monthalize('bill_items')."
						WHERE (product_id = ".$value.")
							AND (bill_id = ".$int_bill_id.")
					");
					if ($qry_bill_item->b_error == true) {
						$bool_success = false;
						$str_message = 'error deleting removed product';
					}
				}
				
				//====================
				//update the ordered bill items
				//====================
				for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
					
					$qry_bill_item->Query("
						SELECT *
						FROM ".Monthalize('bill_items')."
						WHERE (bill_id = ".$int_bill_id.")
							AND (product_id = ".$_SESSION['arr_order_items'][$i][0].")
					");
					if ($qry_bill_item->b_error == true) {
						$bool_success = false;
						$str_message = 'error selecting bill_items '.mysql_error();
					}
					$flt_ordered = $qry_bill_item->FieldByName('quantity');
					
					$qry_product->Query("
						SELECT *
						FROM stock_product
						WHERE (product_id = ".$_SESSION['arr_order_items'][$i][0].")
					");
					if ($qry_product->b_error == true) {
						$str_message = 'error selecting stock_product';
						$bool_success = false;
					}
					
					if ($qry_bill_item->RowCount() > 0) {
						$flt_quantity_updated = $flt_ordered - $_SESSION['arr_order_items'][$i][2];
						
						$qry_bill_item->Query("
							UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_reserved = ROUND(stock_reserved - ".$flt_quantity_updated.", 3)
							WHERE product_id = ".$_SESSION['arr_order_items'][$i][0]."
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
						
						$qry_bill_item->Query("
							UPDATE ".Monthalize('bill_items')."
							SET quantity = ".$_SESSION['arr_order_items'][$i][2]."
							WHERE (bill_id = ".$int_bill_id.")
								AND (product_id = ".$_SESSION['arr_order_items'][$i][0].")
						");
						if ($qry_bill_item->b_error == true) {
							$str_message = 'error updating bill_items';
							$bool_success = false;
						}
					}
					else {
						$qry_bill_item->Query("
							UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_reserved = stock_reserved + ".$_SESSION['arr_order_items'][$i][2]."
							WHERE product_id = ".$_SESSION['arr_order_items'][$i][0]."
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
						
						$qry_bill_item->Query("
							INSERT INTO ".Monthalize('bill_items')."
							(
								quantity,
								quantity_ordered,
								discount,
								price,
								tax_id,
								tax_amount,
								product_id,
								bill_id,
								batch_id,
								adjusted_quantity,
								product_description
							)
							VALUES (
								".$_SESSION['arr_order_items'][$i][2].",
								".$_SESSION['arr_order_items'][$i][3].",
								0,
								".$_SESSION['arr_order_items'][$i][4].",
								0,
								0,
								".$_SESSION['arr_order_items'][$i][0].",
								".$int_bill_id.",
								0,
								0,
								'".addslashes($qry_product->FieldByName('product_description'))."'
							)
						");
						if ($qry_bill_item->b_error == true) {
							$bool_success = false;
							$str_message = 'error inserting bill_items';
						}
					}
				}
			}
			
			if ($bool_success) {
				$qry_bill_item->Query('COMMIT');
				
				$str_delivery_message = '';
				if (IsSet($_GET['deliver'])) {
					if ($_GET['deliver'] == 'Y') {
						$str_retval = deliver_order_bill($int_bill_id);
						$arr_retval = explode("|", $str_retval);
						if ($arr_retval[0] <> 'OK')
							$str_delivery_message = $arr_retval[1];
					}
				}
				echo "<script language='javascript'>\n";
				if ($str_delivery_message <> '')
					echo "alert('".$str_delivery_message."');\n";
				echo "document.location='edit_order_bill_frameset.php';\n";
				echo "</script>";
			}
			else {
				if (IsSet($qry_bill_item))
					$qry_bill_item->Query('ROLLBACK');
				echo "<script language='javascript'>";
				echo "alert('".$str_message."');\n";
				echo "window.close();\n";
				echo "</script>";
			}
		}
		else if ($_GET['action'] == 'cancel') {
			require("order_bill_cancel.php");
			$str_retval = cancelOrderBill($_GET["id"]);
			$arr_retval = explode('|', $str_retval);
			
			echo "<script language='javascript'>\n";
			echo "alert('".$arr_retval[1]."');\n";
			echo "document.location='edit_order_bill_frameset.php';\n";
			echo "</script>";
		}
	}

	//====================
	// get the bill's details
	//--------------------
	$qry_bill = new Query("
		SELECT *
		FROM ".Monthalize('bill')."
		WHERE (bill_id = ".$int_bill_id.")
	");

	if ($qry_bill->FieldByName('bill_status') == BILL_STATUS_RESOLVED) {
		$str_resolved = 'Y';
	}
	else {
		$str_resolved = 'N';
	}

	//====================
	// get the order's details
	//--------------------
	$qry_order = new Query("
		SELECT *
		FROM ".Monthalize('orders')."
		WHERE (order_id = ".$qry_bill->FieldByName('module_record_id').")
	");
	
	$str_order_details = '';
	$str_order_type = '';
	$str_order_day = '';
	$str_order_month = '';
	$str_order_week = '';

	if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_SUNDAY )
		$str_order_day = 'Sunday';
	else if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_MONDAY )
		$str_order_day = 'Monday';
	else if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_TUESDAY )
		$str_order_day = 'Tuesday';
	else if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_WEDNESDAY )
		$str_order_day = 'Wednesday';
	else if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_THURSDAY )
		$str_order_day = 'Thursday';
	else if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_FRIDAY )
		$str_order_day = 'Friday';
	else if ($qry_order->FieldByName('day_of_week') == ORDER_DAY_SATURDAY )
		$str_order_day = 'Saturday';
	
	$str_order_month = getMonthName($qry_order->FieldByName('order_month'));
	
	if ($qry_order->FieldByName('order_week') == 1) {
		$str_order_week = 'Week 1';
	}
	else if ($qry_order->FieldByName('order_week') == 2) {
		$str_order_week = 'Week 2';
	}
	else if ($qry_order->FieldByName('order_week') == 3) {
		$str_order_week = 'Week 3';
	}
	else if ($qry_order->FieldByName('order_week') == 4) {
		$str_order_week = 'Week 4';
	}
	else if ($qry_order->FieldByName('order_week') == 5) {
		$str_order_week = 'Week 5';
	}

	if ($qry_order->FieldByName('order_type') == ORDER_TYPE_DAILY) {
		$str_order_type = 'Daily';
		$str_order_details = $str_order_type;
	}
	else if ($qry_order->FieldByName('order_type') == ORDER_TYPE_WEEKLY) {
		$str_order_type = 'Weekly';
		$str_order_details = $str_order_type." - ".$str_order_day;
	}
	else if ($qry_order->FieldByName('order_type') == ORDER_TYPE_MONTHLY) {
		$str_order_type = 'Monthly';
		$str_order_details = $str_order_type." - ".$str_order_day." - ".$str_order_week;
	}
	else if ($qry_order->FieldByName('order_type') == ORDER_TYPE_ONCE) {
		$str_order_type = 'Once';
		$str_order_details = $str_order_type." - ".$str_order_day." - ".$str_order_week." - ".$str_order_month;
	}
	

	//====================
	// get the order bill's details
	//--------------------
	$qry_bill_items = new Query("
		SELECT *
		FROM ".Monthalize('bill_items')."
		WHERE (bill_id = ".$int_bill_id.")
		ORDER BY product_description
	");
	$qry_product = new Query("SELECT * FROM stock_product LIMIT 1");

	//====================
	// load the items into an array
	//--------------------
	$_SESSION['arr_order_items'] = array();
	for ($i=0; $i<$qry_bill_items->RowCount(); $i++) {

		$qry_product->Query("SELECT * FROM stock_product WHERE (product_id = ".$qry_bill_items->FieldByName('product_id').")");

		$_SESSION['arr_order_items'][$i][0] = $qry_product->FieldByName('product_id');
		$_SESSION['arr_order_items'][$i][1] = $qry_product->FieldByName('product_code');
		$_SESSION['arr_order_items'][$i][2] = $qry_bill_items->FieldByName('quantity');
		$_SESSION['arr_order_items'][$i][3] = $qry_bill_items->FieldByName('quantity_ordered');
		if ($qry_bill_items->FieldByName('quantity_ordered') > 0)
			$_SESSION['arr_order_items'][$i][4] = 'Y';
		else
			$_SESSION['arr_order_items'][$i][4] = 'N';
		$_SESSION['arr_order_items'][$i][5] = 
			StuffWithBlank($qry_product->FieldByName('product_code'), 10)." ".
			PadWithBlank($qry_product->FieldByName('product_description'), 30)." ".
			StuffWithBlank($qry_bill_items->FieldByName('quantity_ordered'), 10)." ".
			StuffWithBlank($qry_bill_items->FieldByName('quantity'), 10);
			
		$qry_bill_items->Next();
	}

	//====================
	// get the orders's account details
	//--------------------
	$qry_account = new Query("
		SELECT *
		FROM account_cc
		WHERE (cc_id = ".$qry_bill->FieldByName('CC_id').")
	");
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
	<script language='javascript'>

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
	var requester2 = createRequest();

//	var flt_previous_qty = 0;
	var str_is_ordered_product = 'N';

	function getDescription(strProductCode) {
		requester.onreadystatechange = stateHandler;
		var strPassValue = '';

		if (strProductCode == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode;

		requester.open("GET", "get_product_details.php?live=1&product_code="+strPassValue);
		requester.send(null);
	}

	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.getElementById('code');
				var oTextBoxDescription = document.getElementById('description');
				var oTextBoxQty = document.getElementById('delivered');
				var oTextBoxUnit = document.getElementById('measurement_unit');
				var oTextBoxPreviousQty = document.getElementById('previous_qty');
				
				str_retval = requester.responseText;
				arr_return = str_retval.split('[]');
				arr_retval = arr_return[0].split('|');
				updateList(arr_return[1]);

				if (arr_retval[0] == '__NOT_FOUND') {
					can_bill = false;
					oTextBoxDescription.innerHTML = '';
					oTextBoxCode.value = '';
				}
				else if (arr_retval[0] == '__NOT_AVAILABLE') {
					can_bill = false;
					alert('This product cannot be billed.\n It has been disabled');
					oTextBoxDescription.innnerHTML = arr_retval[0]+' '+arr_retval[3];
					oTextBoxCode.value = '';
					oTextBoxCode.focus();
				}
				else {
					can_bill = true;
					oTextBoxDescription.innerHTML = arr_retval[0]+' '+arr_retval[3];
					oTextBoxUnit.innerHTML = arr_retval[1];
//					flt_previous_qty = arr_retval[4];
					str_is_ordered_product = arr_retval[5];

//					if (flt_previous_qty > 0)
//						oTextBoxPreviousQty.innerHTML = flt_previous_qty;

					if (arr_retval[2] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
					oTextBoxQty.value = 1;
					oTextBoxQty.focus();
					oTextBoxQty.select();
				}
			}
			else {
				alert("failed to get description... please try again.");
			}
			requester = null;
			requester = createRequest();
		}
	}

	function processQty(aValue) {

		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.getElementById('delivered');

		if ((oTextBoxQty.value <= 0) && (str_is_ordered_product == 'N')) {
			alert('Quantity must be greater than zero');
			can_bill = false;
		}
		else if ((str_is_ordered_product == 'Y') && (oTextBoxQty.value < 0)) {
			alert('Quantity cannot be negative');
			can_bill = false;
		}

		if (can_bill == true) {
			var strPassValue = '';
			if ((oTextBoxCode.value == '') || (aValue == ''))
				strPassValue = 'nil'
			else
				strPassValue = oTextBoxCode.value;

			flt_pass_qty = parseFloat(aValue); // + parseFloat(flt_previous_qty);
		
			requester2.onreadystatechange = stateHandler2;
			requester2.open("GET", "update_order_quantity.php?live=1" +
				"&product_code=" + strPassValue +
				"&qty=" + flt_pass_qty);
			requester2.send(null);
		}
	}

	function stateHandler2() {
		if (requester2.readyState == 4) {
			if (requester2.status == 200)  {
				oTextBoxCode = document.getElementById('code');
				oTextBoxDescription = document.getElementById('description');
				oTextBoxUnit = document.getElementById('measurement_unit');
				
				str_retval = requester2.responseText;

				if (arr_retval[0] == 'ERROR') {
					can_bill = false;
					oTextBoxDescription.innerHTML = '';
					oTextBoxCode.value = '';
					oTextBoxCode.focus();
					oTextBoxCode.select();
				}
				else if (arr_retval[0] == '__NOT_AVAILABLE') {
					can_bill = false;
					alert('This product cannot be billed.\n It has been disabled');
					oTextBoxDescription.innnerHTML = '';
					oTextBoxCode.value = '';
					oTextBoxCode.focus();
					oTextBoxCode.select();
				}
				else {
					can_bill = true;
					clearValues();
					updateList(str_retval);
				}
			}
			else {
				alert("failed to get description... please try again.");
			}
			requester2 = null;
			requester2 = createRequest();
		}
	}

	function updateList(str_list) {
		var oListItems = document.edit_order_bill.item_list;

		oListItems.options.length = 0;

		arr_items = str_list.split('|');

		str_space = '_';
		for (i=0; i<arr_items.length; i++) {
			str_option = arr_items[i];
			str_option = str_option.replace(/&nbsp;/g, str_space);
			oListItems[i] = new Option(str_option, i);
/*
			var y=document.createElement('option');
			y.text=str_option;
			oListItems.add(y, null);
*/
		}
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	
		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.getElementById('delivered');
		var oTextBoxPreviousQty = document.getElementById('previous_qty');
	
		// TAB
		if (charCode == 9 || charCode == 3) {
			if (focusElem == 'code') {
				processQty(aField.value);
				oTextBoxCode.focus();
				oTextBoxCode.select();
			}
			else if (focusElem == 'delivered') {
				getDescription(aField.value);
			} 
		// ENTER
		} else if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'code') {
				processQty(aField.value);
				oTextBoxCode.focus();
				oTextBoxCode.select();
			}
			else if (focusElem == 'delivered') {
				getDescription(aField.value);
			}
		// ESCAPE
		} else if (charCode == 27) {
//			if (flt_previous_qty > 0) {
//				flt_previous_qty = 0;
//				oTextBoxPreviousQty.innerHTML = '';
//			}
//			else {
				oTextBoxCode.focus();
				clearValues();
//			}
		}

		return true;
	}

	function clearValues() {
		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.getElementById('delivered');
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxPreviousQty = document.getElementById('previous_qty');

		oTextBoxCode.value = '';
		oTextBoxQty.value = '';
		oTextBoxDescription.innerHTML = '';
		oTextBoxPreviousQty.innerHTML = '';

		str_is_ordered_product = 'N';
	}

	function SaveOrderBill(aBillId) {
		document.location = 'edit_order_bill.php?id='+aBillId+'&action=save';
	}

	function SaveAndDeliver(aBillId) {
		document.location = 'edit_order_bill.php?id='+aBillId+'&action=save&deliver=Y';
	}
	
	function CancelOrderBill(aBillId) {
		document.location = 'edit_order_bill.php?id='+aBillId+'&action=cancel';
	}

	function WindowClose() {
		if (window.opener)
			window.opener.document.location=window.opener.document.location.href;
		window.close();
	}

	</script>
</head>

<body marginheight="10" marginwidth="10">

<form name='edit_order_bill' method='GET'>

<? if ($str_resolved == 'Y') { ?>

	<table width='100%' height='100%' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td class='<?echo $str_class_header?>'>
			<?
				echo "<b>".$qry_account->FieldByName('account_number')." - ".$qry_account->FieldByName('account_name')."</b><br>";
				echo $str_order_details;
			?>
			</td>
		</tr>
		<tr>
			<td>
				This order bill has already been delivered and resolved
			</td>
		</tr>
		<tr>
			<td>
				<input type='button' name='action' value='Close' onclick='WindowClose()'>
			</td>
		</tr>
	</table>

<? } else { ?>

	<table width='100%' height='100%' border='1' cellpadding='0' cellspacing='0'>
		<tr height='50px'>
			<td class='<?echo $str_class_header?>'>
			<?
				echo "<b>".$qry_account->FieldByName('account_number')." - ".$qry_account->FieldByName('account_name')."</b><br>";
				echo $str_order_details;
			?>
			</td>
		</tr>
		<tr height='50px'>
			<td>
				<table width='100%' height='100%' border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td class='<?echo $str_class_header?>'>Code</td>
						<td class='<?echo $str_class_header?>'>Description<img src="../images/blank.gif" width="250px" height="1px"></td>
						<td class='<?echo $str_class_header?>'>Delivered</td>
						<td class='<?echo $str_class_header?>' id='previous_qty'><img src="../images/blank.gif" width="30px" height="1px"></td>
					</tr>
					<tr>
						<td><input type='text' name='code' id='code' value='' onkeypress="focusNext(this, 'delivered', event)" class='<?echo $str_class_input?>'></td>
						<td><span id='description' class='<?echo $str_class_span?>'>&nbsp;</span></td>
						<td><input type='text' name='delivered' id='delivered' value='' onkeypress="focusNext(this, 'code', event)" class='<?echo $str_class_input?>'></td>
						<td><span id='measurement_unit' class='<?echo $str_class_span?>'>&nbsp;</span></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr height='20px'>
			<td>
				<font class="<?echo $str_class_list_header?>">
				<?
					echo StuffWithBlank('Code', 10)." ";
					echo PadWithBlank('Description', 30)." ";
					echo StuffWithBlank('Ordered', 10)." ";
					echo StuffWithBlank('Delivered', 10)." ";
				?>
				</font>
			</td>
		</tr>
		<tr>
			<td>
				<select name='item_list' height='100%' size='20' class='<?echo $str_class_list_box?>'>
				<?
				for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
					echo "<option value=".$i.">".$_SESSION['arr_order_items'][$i][5]."\n";
				}
				?>
				</select>
			</td>
		</tr>
		<tr height='30px'>
			<td>
				<input type='button' name='action' value='Save' onclick='javascript:SaveOrderBill(<?echo $int_bill_id?>)'>
				<input type='button' name='action' value='Cancel' onclick='javascript:CancelOrderBill(<?echo $int_bill_id?>)'>
				<input type='button' name='action' value='Save and Deliver' onclick='javascript:SaveAndDeliver(<?echo $int_bill_id?>)'>
				<input type='button' name='action' value='Close' onclick='javascript:WindowClose()'>
			</td>
		</tr>
	</table>

<?  } ?>

</form>

</body>
</html>