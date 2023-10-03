<?
//	require_once("/var/www/html/Gubed/Gubed.php");

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/account.php");
//	require_once("bill_cancel.php");
	require_once("../get_bill_number.php");
	
	$qry_settings = new Query("SELECT bill_transfer_tax FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
	$int_transfer_tax = 0;
	if ($qry_settings->RowCount() > 0)
		$int_transfer_tax = $qry_settings->FieldByName('bill_transfer_tax');

	function getBillDate($int_day) {
		$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".$int_day." ".date("H:i:s");
		
		return $str_date;
	}

	function is_all_nonzero() {
		$str_retval = 'OK';
		for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
			if (($_SESSION['arr_total_qty'][$i][2] == 0) && ($_SESSION['arr_total_qty'][$i][5] == 0)) {
				$str_retval = "A quantity error occurred while billing.\\nPlease cancel product ".$_SESSION['arr_total_qty'][$i][0]." and enter it again.";
			}
			else if ($_SESSION['arr_total_qty'][$i][6] == 0) {
				$str_retval = "A price error occurred while billing.\\nPlease cancel product ".$_SESSION['arr_total_qty'][$i][0]." and enter it again.";
			}
		}
		return $str_retval;
	}

	function verify_billed_items($str_path) {
		$str_retval = 'OK|OK';
		$bool_retval = true;
		
		// check for negative entries
		for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
			if (($_SESSION['arr_total_qty'][$i][2] < 0) || ($_SESSION['arr_total_qty'][$i][2] < 0)) {
				$bool_retval = false;
				$str_retval = 'FALSE|There was a negative value entered for product '.$_SESSION['arr_total_qty'][$i][0];
				break;
			}
		}
		
		// cross check quantities with quantities in batches
		// not when editting bill
		if ($_SESSION['bill_id'] == -1) {
			$qry_check = new Query("SELECT * FROM ".Monthalize('stock_storeroom_batch')." LIMIT 1");
			for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
				$_SESSION['arr_total_qty'][$i][21] = "billed: ";
				$qry_check->Query("
					SELECT *
					FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (sb.batch_code = '".$_SESSION['arr_total_qty'][$i][1]."')
						AND (sb.product_id = ".$_SESSION['arr_total_qty'][$i][13].")
						AND (ssb.batch_id = sb.batch_id)
						AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
						AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
					ORDER BY sb.date_created DESC
					LIMIT 1
				");
				if ($qry_check->RowCount() > 0) {
					if ($_SESSION['arr_total_qty'][$i][2] > $qry_check->FieldByName('stock_available')) {
						$bool_retval = false;
						$str_retval = 'FALSE|Batch quantity and actual quantity entered not matched for product '.$_SESSION['arr_total_qty'][$i][0];
						$_SESSION['arr_total_qty'][$i][21] .= $_SESSION['arr_total_qty'][$i][2]." available : ".$qry_check->FieldByName('stock_available');
					}
				}
			}
		}
		
		// save the array in a file for debugging
                if ($bool_retval == false) {
			$fname = $str_path."bill_error_log_".date('j').".txt";
			$fhandle = fopen($fname,"w");
			for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
				$str_content = "\n".
					"       code :".$_SESSION['arr_total_qty'][$i][0]."\n".
					"      batch :".$_SESSION['arr_total_qty'][$i][1]."\n".
					"        qty :".$_SESSION['arr_total_qty'][$i][2]."\n".
					"   discount :".$_SESSION['arr_total_qty'][$i][3]."\n".
					"    percent :".$_SESSION['arr_total_qty'][$i][4]."\n".
					"   adjusted :".$_SESSION['arr_total_qty'][$i][5]."\n".
					"      price :".$_SESSION['arr_total_qty'][$i][6]."\n".
					"     tax_id :".$_SESSION['arr_total_qty'][$i][7]."\n".
					"  tax descr :".$_SESSION['arr_total_qty'][$i][8]."\n".
					"   is_taxed :".$_SESSION['arr_total_qty'][$i][9]."\n".
					"      total :".$_SESSION['arr_total_qty'][$i][10]."\n".
					"        tax :".$_SESSION['arr_total_qty'][$i][11]."\n".
					"description :".$_SESSION['arr_total_qty'][$i][12]."\n".
					" product_id :".$_SESSION['arr_total_qty'][$i][13]."\n".
					"   supplier :".$_SESSION['arr_total_qty'][$i][14]."\n".
					" is_decimal :".$_SESSION['arr_total_qty'][$i][15]."\n".
					"   location :".$_SESSION['arr_total_qty'][$i][20]."\n".
					"    comment :".$_SESSION['arr_total_qty'][$i][21]."\n";
				
				fwrite($fhandle, $str_content);
			}
			fclose($fhandle);
		}
		
		return $str_retval;
	}

?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />

<script language="javascript">

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

	var save_clicked = false;
	var requester = createRequest();
	var requester2 = createRequest();

	function stateHandler() {
		
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				str_retval = requester.responseText;
				alert(str_retval);
			}
			else {
				alert("failed to get change... please try again.");
			}
			requester = null;
			requester = createRequest();
		}
	}

	function stateHandler2() {
		
		if (requester2.readyState == 4) {
			if (requester2.status == 200)  {
				
				str_retval = requester2.responseText;
				arr_retval = str_retval.split('|');
				
				var oPromotion = parent.frames['frame_total'].document.getElementById("bill_promotion");
				var oPromotionLabel = parent.frames['frame_total'].document.getElementById("bill_promotion_label");
				var oGrandTotal = parent.frames['frame_total'].document.getElementById("bill_grand_total");
				var oGrandTotalLabel = parent.frames['frame_total'].document.getElementById("bill_grand_total_label");
				
				if (arr_retval[0] == 'nil') {
					oPromotionLabel.innerHTML = "";
					oPromotion.innerHTML = '';
					oGrandTotalLabel.innerHTML = "";
					oGrandTotal.innerHTML = '';
				}
				else {
					oPromotionLabel.innerHTML = "Sales Promotion : ";
					oPromotion.innerHTML = arr_retval[0];
					oGrandTotalLabel.innerHTML = "Grand Total : ";
					oGrandTotal.innerHTML = arr_retval[1];
				}
			}
			else {
				alert("failed to get change... please try again.");
			}
			requester2 = null;
			requester2 = createRequest();
		}
	}

	function cancelBill(intBillType) {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		if (oList.options.length > 0)
			if (confirm('Are you sure you want to cancel the bill?')) {
				top.document.location = "billing_frameset.php?action=clear_bill&bill_type="+intBillType;
				parent.frames["frame_enter"].document.billing_enter.code.focus();
			}
	}

	function saveBill(strAction) {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		var oButtonSave = document.bill_action.button_save;
		
		if (oList.options.length > 0) {
			// first disable the save button		
			if (save_clicked == false) {
				oButtonSave.disabled = true;
				oButtonSave.onclick = '';
				save_clicked = true;
				document.location = "billing_action.php?action=" + strAction;
			}
		}
		else
			alert('no items listed to save');
	}

	function getDescription(strProductCode) {
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
	}

	function getChange() {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		if (oList.options.length > 0) {
			requester.onreadystatechange = stateHandler;
			
			var fltReceived = prompt('Enter amount received','');
			if (fltReceived != null) {
				if (isNaN(fltReceived)) {
					alert('Please enter a valid number');
				}
				else {
					requester.open("GET", "../billing/get_bill_change.php?live=1&amount_received="+fltReceived);
					requester.send(null);
				}
			}
		}
	}

	function setSalesPromotion() {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		if (oList.options.length > 0) {
			requester2.onreadystatechange = stateHandler2;
			
			var fltSalesPromotion = prompt('Enter sales promotion','');
			if (fltSalesPromotion != null) {
				if (isNaN(fltSalesPromotion)) {
					alert('Please enter a valid number');
				}
				else {
					if ((fltSalesPromotion == '0') || (fltSalesPromotion == '') || isNaN(fltSalesPromotion)) {
						requester2.open("GET", "../billing/set_sales_promotion.php?live=1&salesprom_amount=del");
					}
					else {
						requester2.open("GET", "../billing/set_sales_promotion.php?live=1&salesprom_amount=" + fltSalesPromotion);
					}
					requester2.send(null);
				}
			}
		}
	}
	
	function printBill(aBillId) {
		myWin = window.open("../print_bill.php?id="+aBillId, 'printwin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=450,height=250');
		myWin.focus();
	}
	
	function CloseWindow() {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		if (oList.options.length > 0) {
			if (confirm('Items have been billed. \n Exit anyway?')) {
				if (top.window.opener)
					top.window.opener.document.location=top.window.opener.document.location.href;
				top.window.close();
			}
		}
		else {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	}
</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7 bgcolor="0000FF">
<form name="bill_action" method="GET">

<?

$err_status = 1;
$can_save = 0;

if (IsSet($_GET['action'])) {
	if ($_GET['action'] == "set_type") {
		// save the bill type
		$_SESSION['current_bill_type'] = $_GET['bill_type'];
		$_SESSION['current_bill_day'] = $_GET['bill_day'];
	}
	else if ($_GET['action'] == "account") {
		// save the account number
		$_SESSION['current_account_number'] = $_GET['account_number'];
	}
	else if ($_GET['action'] == "creditcard") {
		$_SESSION['bill_card_name'] = $_GET['card_name'];
		$_SESSION['bill_card_number'] = $_GET['card_number'];
		$_SESSION['bill_card_date'] = $_GET['card_date'];
	}
	else if ($_GET["action"] == 'close') {
		echo "<script language=\"javascript\">";
		echo "CloseWindow();";
		echo "</script>";
	}
	else if ($_GET['action'] == 'save') {

		$_SESSION['save_counter'] = intval($_SESSION['save_counter']) +1;

		if ($_SESSION['save_counter'] > 1)
			die('This bill has already been saved. \nClose this window and open again.');

		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
			$str_message = 'Current month and year';
			$err_status = 1; 
		}
		else {
			$str_message = 'Cannot bill in previous months. \\n Select the current month/year and continue.';
			$err_status = 0; //false
		}

		$str_account_name = '';
		// IF ACCOUNT BILL, CHECK THE ACCOUNT NUMBER
		if (($_SESSION['current_bill_type'] == 2) || ($_SESSION['current_bill_type'] == 6)){
			$str_retval = getAccountName($_SESSION['current_account_number'], ACCOUNT_METHOD);
			$arr_retval = explode("|", $str_retval);
			if ($arr_retval[0] != 'OK') {
				$str_message = $arr_retval[0];
				$err_status = 0; //false
			}
			else {
				$str_account_name = $arr_retval[1];
				
				$int_current_CCID = getAccountCCID($_SESSION['current_account_number'], ACCOUNT_METHOD);

				if ($int_current_CCID == -1) {
					$str_retval = get_account_status($_SESSION['current_account_number']);
					$arr_retval = explode('|', $str_retval);
					
					if ($arr_retval[0] == 'OK') {
						$str_active = 'FALSE';
						if ($arr_retval[1] == 'Y')
							$str_active = 'TRUE';
							
						$str_message = "The active status of this account is ".$str_active.". \\n The balance is ".$arr_retval[2];
					}
					else
						$str_message = $arr_retval[1];
					
					$err_status = 0;
				}
			}
		}
		
		// IF PT ACCOUNT
		else if ($_SESSION['current_bill_type'] == 3) {
		  // get the account_id of the current account number
			$result_search = new Query("
				SELECT account_id, account_name, enabled
				FROM account_pt
				WHERE (account_number = '".$_SESSION['current_account_number']."')
			");
			if ($result_search->RowCount() > 0) {
				$str_account_name = $result_search->FieldByName('account_name');
				$int_current_CCID = $result_search->FieldByName('account_id');
				if ($result_search->FieldByName('enabled') == 'N') {
					$str_message = "This account has been disabled.";
					$err_status = 0;
				}
			}
			else {
				$str_message = "Invalid PT account";
				$err_status = 0; //false
			}
		}
		else {
			$int_current_CCID = 0;
		}
			
		$int_payment_number = '';
		
		// a check to make sure the quantities stored in the session array
		// are non-zero
		$str_result = is_all_nonzero();
		if ($str_result <> 'OK') {
			$str_message = $str_result;
			$err_status = 0;
		}
		
		if ($err_status != 0) {
			//=========================
			// get the last bill number
			//-------------------------
			$int_next_billNumber = get_bill_number($_SESSION['current_bill_type']);
			
			//====================================================
			// start transaction in case the CREATE TRANSFER fails
			//----------------------------------------------------
			$result_set = new Query("START TRANSACTION");
			
			$bill_saved = 1;
			$bill_items_saved = 1;
			
			//=========================
			// insert row in bill table
			//-------------------------
			$str_query = "
				INSERT INTO ".Monthalize('bill')."
					(storeroom_id,
					bill_number,
					date_created,
					total_amount,
					payment_type,
					payment_type_number,
					bill_promotion,
					bill_status,
					is_pending,
					user_id,
					module_id,
					resolved_on,
					CC_id,
					account_number,
					account_name,
					card_name,
					card_number,
					card_date,
					is_debit_bill)
				VALUES (".
					$_SESSION['int_current_storeroom'].", ".
					$int_next_billNumber.", '".
					getBillDate($_SESSION['current_bill_day'])."', ".
					$_SESSION['bill_total'].", ".
					$_SESSION['current_bill_type'].", '".
					$int_payment_number."', ".
					$_SESSION["sales_promotion"].", ".
					BILL_STATUS_RESOLVED.", ".
					"'N', ".
					$_SESSION['int_user_id'].", ".
					"2, '".
					getBillDate($_SESSION['current_bill_day'])."', ".
					$int_current_CCID.", '".
					$_SESSION['current_account_number']."', '".
					$str_account_name."', '".
					addslashes($_SESSION['bill_card_name'])."', '".
					addslashes($_SESSION['bill_card_number'])."', '".
					addslashes($_SESSION['bill_card_date'])."',
					'Y')";
			$result_set->Query($str_query);
			if ($result_set->b_error == true) {
				$bill_saved = 0;
				$str_message = 'An error occurred trying to save the bill.';
			}
			$int_bill_id = $result_set->getInsertedID();
			
			//===========================================
			// insert a row for each item that was billed
			//-------------------------------------------
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				
				//========================================
				// get the batch id of the current product
				//----------------------------------------
				$result_set->Query("
					SELECT batch_id
					FROM ".Yearalize('stock_batch')."
					WHERE (batch_code = '".$_SESSION['arr_total_qty'][$i][1]."') AND
						(product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				if ($result_set->b_error == true) {
					$bill_items_saved = 0;
					$str_message = "An error occurred trying to retrieve the batch (".$_SESSION['arr_total_qty'][$i][1].") of item ".$_SESSION['arr_total_qty'][$i][13];
					break;
				}
				$current_batch_id = $result_set->FieldByName('batch_id');
				
				if ($_SESSION['current_bill_type'] == 6) // transfer of goods
					$int_tax = $int_transfer_tax;
				else
					$int_tax = $_SESSION['arr_total_qty'][$i][7];
					
				// save the row
				$result_set->Query("
					INSERT INTO ".Monthalize('bill_items')."
						(quantity,
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
					VALUES(".
						$_SESSION['arr_total_qty'][$i][2].", ".
						$_SESSION['arr_total_qty'][$i][4].", ".
						$_SESSION['arr_total_qty'][$i][6].", ".
						$int_tax.", ".
						$_SESSION['arr_total_qty'][$i][11].", ".
						$_SESSION['arr_total_qty'][$i][13].", ".
						$int_bill_id.", ".
						$current_batch_id.", ".
						$_SESSION['arr_total_qty'][$i][5].", '".
						addslashes($_SESSION['arr_total_qty'][$i][12])."')
				");
				if ($result_set->b_error == true) {
					$bill_items_saved = 0;
					$str_message = "An error occurred trying to save one of the items (".$_SESSION['arr_total_qty'][$i][13].") of the current bill.";
					break;
				} 
			}
			
			if (($bill_saved == 1) && ($bill_items_saved == 1)) {
				//=============================
				// set 'ok' to update the stock
				//-----------------------------
				$can_save = 1;
				$result_set->Query("COMMIT");
				
			}
			else {
				recycle_bill_number($int_next_billNumber, $_SESSION['current_bill_type']);
				$result_set->Query("ROLLBACK");
				
				echo "<script language=\"javascript\">\n";
				echo "alert('".$str_message."')\n";
				echo "</script>\n";
			}
		} // end if ($err_status != 0)
		else {
			echo "<script language=\"javascript\">\n";
			echo "alert('".$str_message."')\n";
			echo "</script>\n";
		}
		$_SESSION['save_counter'] = 0;
	} // end if (action ='save')
} // end if set $_GET['action']


if ($can_save == 1) {
	//===================================
	// if the bill was saved successfully
	// update the stock
	//-----------------------------------
	
	$result_set->Query("START TRANSACTION");
	$bool_success = true;
	
	for ($i=0; $i<count($_SESSION['arr_total_qty']); $i++) {
		
		//======================================================
		// get the batch_id
		//------------------------------------------------------
		$result_set->Query("
			SELECT batch_id, supplier_id
			FROM ".Yearalize('stock_batch')."
			WHERE (batch_code = '".$_SESSION['arr_total_qty'][$i][1]."') AND
				(product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
				(storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		$current_batch_id = $result_set->FieldByName('batch_id');
		
		//======================================================
		// get the adjusted quantity
		//------------------------------------------------------
		$result_set->Query("
			SELECT stock_adjusted
			FROM ".Monthalize('stock_storeroom_product')."
				WHERE (product_id=".$_SESSION['arr_total_qty'][$i][13].") AND
					(storeroom_id=".$_SESSION["int_current_storeroom"].")
		");
		if ($result_set->b_error == true) {
			$bool_success = false;
			$str_message = "Error retrieving adjusted quantity from stock_storeroom_product";
		}
		
		$flt_stock_adjusted = number_format($result_set->FieldByName('stock_adjusted'),3,'.',',');
		
		$flt_quantity = number_format(($_SESSION['arr_total_qty'][$i][2] + $_SESSION['arr_total_qty'][$i][5]), 3,'.','');
		
		if ($flt_stock_adjusted >= $flt_quantity) {
			$flt_update_adjusted = $flt_quantity;
			$flt_update_stock = 0;
		}
		else {
			$flt_update_adjusted = $flt_stock_adjusted;
			$flt_update_stock = $flt_quantity - $flt_stock_adjusted;
		}
		
		if ($flt_update_stock > 0) {
			//======================================================
			// TABLE stock_storeroom_product
			//------------------------------------------------------
			$result_set->Query("UPDATE ".Monthalize('stock_storeroom_product')."
				SET stock_current = ABS(ROUND((stock_current + ".$flt_update_stock."),3))
				WHERE (product_id=".$_SESSION['arr_total_qty'][$i][13].") AND
					(storeroom_id=".$_SESSION["int_current_storeroom"].")");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_storeroom_product";
			}
			
			//=================================================================================
			// TABLE stock_storeroom_batch
			// There was some strange behaviour subtracting here, hence the ROUND function call
			// very small amounts were generated, as -7.12548e-9
			//---------------------------------------------------------------------------------
			$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
				SET stock_available = ROUND((stock_available + ".$flt_update_stock."),3)
				WHERE (batch_id = ".$current_batch_id.") AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(product_id = ".$_SESSION['arr_total_qty'][$i][13].")
			");
			if ($result_set->b_error == true) {
			$bool_success = false;
				$str_message = "Error updating stock_storeroom_batch";
			}
			
			//======================================================
			// TABLE stock_balance
			//------------------------------------------------------
			$result_set->Query("UPDATE ".Yearalize('stock_balance')."
					SET stock_received = stock_received + ".$flt_update_stock.",
						stock_closing_balance = ROUND((stock_closing_balance + ".$flt_update_stock."),3)
					WHERE (product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(balance_month = ".$_SESSION["int_month_loaded"].") AND
						(balance_year = ".$_SESSION["int_year_loaded"].")");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_balance";
			}
			
			//======================================================
			// TABLE stock_transfer
			//------------------------------------------------------
			$result_set->Query("INSERT INTO  ".Monthalize('stock_transfer')."
					(transfer_quantity,
					transfer_description,
					date_created,
					module_id,
					user_id,
					storeroom_id_from,
					storeroom_id_to,
					product_id,
					batch_id,
					module_record_id,
					transfer_type,
					transfer_status,
					user_id_dispatched,
					user_id_received,
					is_deleted)
				VALUES(".
					$flt_update_stock.", '". 
					"DEBIT BILL NUMBER ".$int_next_billNumber."', '".  
					getBillDate($_SESSION['current_bill_day'])."', ".
					"2, ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					$_SESSION["int_current_storeroom"].", ".
					$_SESSION['arr_total_qty'][$i][13].", ".
					$current_batch_id.", ".
					$int_bill_id.", ".
					TYPE_DEBIT_BILL.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					"'N')");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_transfer for product id ".$_SESSION['arr_total_qty'][$i][13]." and batch id ".$current_batch_id;
			}
		}
		
		if ($flt_update_adjusted > 0) {
			//======================================================
			// TABLE stock_storeroom_product 
			//------------------------------------------------------
			$result_set->Query("UPDATE ".Monthalize('stock_storeroom_product')."
				SET stock_adjusted = ABS(ROUND((stock_adjusted - ".$flt_update_adjusted."),3))
				WHERE (product_id=".$_SESSION['arr_total_qty'][$i][13].") AND
					(storeroom_id=".$_SESSION["int_current_storeroom"].")");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_storeroom_product";
			}
			
			//======================================================
			// TABLE stock_balance
			//------------------------------------------------------
			$result_set->Query("UPDATE ".Yearalize('stock_balance')."
					SET stock_received = stock_received + ".$flt_update_adjusted."
					WHERE (product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(balance_month = ".$_SESSION["int_month_loaded"].") AND
						(balance_year = ".$_SESSION["int_year_loaded"].")");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_balance";
			}
			
			//======================================================
			// TABLE stock_transfer
			//------------------------------------------------------
			$result_set->Query("INSERT INTO  ".Monthalize('stock_transfer')."
					(transfer_quantity,
					transfer_description,
					date_created,
					module_id,
					user_id,
					storeroom_id_from,
					storeroom_id_to,
					product_id,
					batch_id,
					module_record_id,
					transfer_type,
					transfer_status,
					user_id_dispatched,
					user_id_received,
					is_deleted)
				VALUES(".
					$flt_update_adjusted.", '". 
					"DEBIT BILL NUMBER ".$int_next_billNumber."', '".  
					getBillDate($_SESSION['current_bill_day'])."', ".
					"2, ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					$_SESSION["int_current_storeroom"].", ".
					$_SESSION['arr_total_qty'][$i][13].", ".
					$current_batch_id.", ".
					$int_bill_id.", ".
					TYPE_DEBIT_BILL.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					"'N')");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_transfer for product id ".$_SESSION['arr_total_qty'][$i][13]." and batch id ".$current_batch_id;
			}
		}
	}
	
	if ($bool_success) {
		$result_set->Query("COMMIT");
		
		//===================================
		// create transfer if FS account bill
		//-----------------------------------
		$str_transfer_message = '';
		
		if (($_SESSION['current_bill_type'] == BILL_ACCOUNT) || ($_SESSION['current_bill_type'] == BILL_TRANSFER_GOOD)) {
			//========================================
			// get the account to make the transfer to
			//----------------------------------------
			$result_set->Query("
				SELECT bill_credit_account, bill_description
				FROM stock_storeroom
				WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			$credit_acount = $result_set->FieldByName('bill_credit_account');
			if ($_SESSION['current_bill_type'] == BILL_ACCOUNT) {
				$bill_description = str_replace("%s", $int_next_billNumber, $result_set->FieldByName('bill_description'));
				$bill_description = str_replace("%d", substr(getBillDate($_SESSION['current_bill_day']),0,10), $bill_description);
			}
			else {
				$bill_description = "TG:".$int_next_billNumber." - ".substr(getBillDate($_SESSION['current_bill_day']),0,10);
			}
			
			//==========================
			// make the reverse transfer
			//--------------------------
			$int_result = createTransfer(
				$credit_acount,
				$_SESSION['current_account_number'],
				$bill_description,
				$_SESSION['bill_total'],
				2,
				$int_bill_id);
			//========================
			// if transfer successfull
			// result +ve => success
			//------------------------
			if (($int_result > 0) || ($int_result == -1)) {
				$str_transfer_message = '';
			}
			//=========================
			// transfer not successfull
			//-------------------------
			else {
				// result -2 => insufficient funds
				// prompt for desired subsequent action
				if (($int_result == -2) && ($_SESSION['connect_mode'] <> CONNECT_ONLINE)) {
					$str_transfer_message = "ERROR (".$int_result."): Insufficient funds to create this transfer.";
				}
				else {
					$str_transfer_message = "ERROR (".$int_result."): This transfer could not be completed.";
				}
			}
		}
		//====================================
		// Create transfer for Pour Tous bills
		//------------------------------------
		else if ($_SESSION['current_bill_type'] == 3) {
			$bill_description = "TRANSFER NUMBER ".$int_next_billNumber;
			
			$int_result = createPTTransfer(
				0,
				$_SESSION['current_account_number'],
				$bill_description, 
				($_SESSION['bill_total'] * -1),
				2,
				$int_bill_id);
				
			if ($int_result > 0) {
				$str_transfer_message = '';
			}
			else {
				// result = -2 => insufficient funds
				if ($int_result == -2) {
					$str_transfer_message = "ERROR (".$int_result."): This transfer could not be completed.";
				}
			}
		}
		
		if ($str_transfer_message <> '') {
			echo "<script language=\"javascript\">\n";
			echo "alert('".$str_transfer_message."')";
			echo "</script>\n";
		}
		
		echo "<script language=\"javascript\">\n";
		echo "if (confirm('Debit Bill saved successfully. \\n Would you like to print the bill?'))\n";
		echo "	printBill(".$int_bill_id.");\n";
		echo "top.document.location = \"billing_frameset.php?action=clear_bill&bill_type=".$_SESSION['current_bill_type']."\";\n";
		echo "</script>\n";
	}
	else {
		//====================
		// ERROR SAVING STOCK
		//--------------------
		// rollback
		//--------------------
		$result_set->Query("ROLLBACK");
		
		//====================
		// mark bill cancelled
		//--------------------
		$result_set->Query("
			UPDATE ".Monthalize('bill')."
			SET bill_status = ".BILL_STATUS_CANCELLED.",
				cancelled_user_id = ".$_SESSION["int_user_id"].",
				cancelled_reason = 'error saving bill'
			WHERE (bill_id = ".$int_bill_id.")
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		");
		
		//====================
		// inform
		//--------------------
		echo "<script language=\"javascript\">\n";
		echo "alert('".$str_message." \\nThis bill could not be saved.');\n";
		echo "top.document.location = \"billing_frameset.php?action=clear_bill&bill_type=".$_SESSION['current_bill_type']."\";\n";
		echo "</script>\n";
	}
	
	// clear the session variables related to the billing
	$_SESSION['bill_id'] = -1;
	$_SESSION['bill_number'] = '';
	unset($_SESSION["arr_total_qty"]);
	unset($_SESSION["arr_item_batches"]);
	if (IsSet($_GET["bill_type"]))
		$_SESSION['current_bill_type'] = $_GET["bill_type"];
	else
		$_SESSION['current_bill_type'] = 1;
	$_SESSION['current_bill_day'] = date('j');
	$_SESSION['current_account_number'] = "";
	$_SESSION['current_account_name'] = "";
	$_SESSION['bill_total'] = 0;
	$_SESSION['sales_promotion'] = 0;

	$_SESSION['save_counter'] = 0;
} // end of can_save
?>

	<input type="button" class="v3button" name="button_save" value="Save" onclick="javascript:saveBill('save')">
	<input type="button" class="v3button" name="button_cancel" value="Cancel" onclick="javascript:cancelBill(<? echo $_SESSION['current_bill_type']?>)">
	<input type="button" name="button_close" value="Close" class="v3button" onclick="CloseWindow()">
	<input type="button" name="button_change" value="Change" class="v3button" onclick="javascript:getChange()">
	<input type="button" name="button_promotion" value="Promotion" class="v3button" onclick="javascript:setSalesPromotion()">&nbsp;&nbsp;&nbsp;&nbsp;
	<font class="headertext">[<b>F2</b> Save][<b>F4</b> Discount][<b>F10</b> Exit]</font>
	<br><br>
	<span id='error_message'><font color='red'><b>
	<?
		if ($_SESSION['bill_error'] <> '') {
			echo $_SESSION['bill_error'];
			$_SESSION['bill_error'] = '';
		}
	?>
	</b></font></span>
	<script language='javascript'>
		var oSpan = document.getElementById('error_message');
		setTimeout("oSpan.innerHTML = ''", 5000);
	</script>
</form>
</body>
</html>