<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/account.php");
	require_once("../../common/product_bill.php");
//	require_once("bill_cancel.php");
	require_once("get_dc_number.php");
	
	function is_all_nonzero() {
		$str_retval = 'OK';
		for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
			if (($_SESSION['arr_total_qty'][$i][2] == 0) && ($_SESSION['arr_total_qty'][$i][5] == 0)) {
				$str_retval = "A quantity error occurred while billing.\\nPlease cancel product ".$_SESSION['arr_total_qty'][$i][0]." and enter it again.";
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
		if ($_SESSION['dc_id'] == -1) {
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
			$fname = $str_path."dc_error_log_".date('j').".txt";
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
		//if (oList.options.length > 0)
		if (oList.value > 0)
			if (confirm('Are you sure you want to cancel the bill?')) {
				top.document.location = "billing_frameset.php?action=clear_bill&bill_type="+intBillType;
				parent.frames["frame_enter"].document.billing_enter.code.focus();
			}
	}

	function saveBill(strAction) {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		var oButtonSave = document.bill_action.button_save;
		
		//if (oList.options.length > 0) {
		if (oList.value > 0) {
			// first disable the save button
			if (save_clicked == false) {
				oButtonSave.disabled = true;
				oButtonSave.onclick = '';
				save_clicked = true;
				document.location = "dc_action.php?action=" + strAction;
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
		//if (oList.options.length > 0) {
		if (oList.value > 0) {
			requester.onreadystatechange = stateHandler;
			
			var fltReceived = prompt('Enter amount received','');
			if (fltReceived != null) {
				if (isNaN(fltReceived)) {
					alert('Please enter a valid number');
				}
				else {
					requester.open("GET", "../../billing/get_bill_change.php?live=1&amount_received="+fltReceived);
					requester.send(null);
				}
			}
		}
	}

	function setSalesPromotion() {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		//if (oList.options.length > 0) {
		if (oList.value > 0) {
			requester2.onreadystatechange = stateHandler2;
			
			var fltSalesPromotion = prompt('Enter sales promotion','');
			if (fltSalesPromotion != null) {
				if (isNaN(fltSalesPromotion)) {
					alert('Please enter a valid number');
				}
				else {
					if ((fltSalesPromotion == '0') || (fltSalesPromotion == '') || isNaN(fltSalesPromotion)) {
						requester2.open("GET", "../../billing/set_sales_promotion.php?live=1&salesprom_amount=del");
					}
					else {
						requester2.open("GET", "../../billing/set_sales_promotion.php?live=1&salesprom_amount=" + fltSalesPromotion);
					}
					requester2.send(null);
				}
			}
		}
	}
	
	function printBill(aBillId) {
		myWin = window.open("print_dc.php?id="+aBillId, 'print_dc', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=450,height=250');
		myWin.focus();
	}
	
	function CloseWindow() {
		var oList = parent.frames["frame_list"].document.billing_list.item_list;
		//if (oList.options.length > 0) {
		if (oList.value > 0) {
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
	if ($_GET["action"] == 'close') {
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
		
		/*
			if it is an edit, make sure the status
			is set to "unresolved"
		*/
		if ($_SESSION['dc_id'] > 0) {
			$qry = new Query("SELECT * FROM ".Monthalize('dc')." WHERE dc_id = ".$_SESSION['dc_id']);
			if ($qry->FieldByName('dc_status') > DC_STATUS_UNRESOLVED) {
				die('This DC cannot be editted');
			}
		}
		
		/*
			a check to make sure the quantities stored
			in the session array are valid
		*/
		$str_result = verify_billed_items($str_application_path);
		$arr_retval = explode('|', $str_result);
		if ($arr_retval[0] == 'FALSE') {
			$str_message = $arr_retval[1];
			$err_status = 0;
		}

		/*
			a check to make sure the quantities stored
			in the session array are non-zero
		*/
		$str_result = is_all_nonzero();
		if ($str_result <> 'OK') {
			$str_message = $str_result;
			$err_status = 0;
		}
		
		if ($err_status != 0) {
			/*
				new dc
			*/
			if ($_SESSION['dc_id'] <= 0) {
				/*
					get the last bill number
				*/
				$int_next_billNumber = get_dc_number();
				
				/*
					start transaction in case the CREATE TRANSFER fails
				*/
				$result_set = new Query("START TRANSACTION");
				
				$bill_saved = 1;
				$bill_items_saved = 1;
				
				/*
					insert row in bill table
				*/
				$str_query = "
					INSERT INTO ".Monthalize('dc')."
						(
							storeroom_id,
							client_id,
							dc_number,
							date_created,
							total_amount,
							dc_status,
							user_id,
							is_modified
						)
					VALUES (".
						$_SESSION['int_current_storeroom'].", ".
						$_SESSION['dc_client_id'].", ".
						$int_next_billNumber.", '".
						getBillDate($_SESSION['current_dc_day'])."', ".
						number_format(RoundUp($_SESSION['dc_total']),2,'.','').", ".
						"1, ".
						$_SESSION['int_user_id'].
						",'Y'".
					")";
					
				$result_set->Query($str_query);
				if ($result_set->b_error == true) {
					$bill_saved = 0;
					$str_message = 'An error occurred trying to save the dc. ';
					die($str_query);
				}
				$int_bill_id = $result_set->getInsertedID();
				
				/*
					insert a row for each item that was billed
				*/
				for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
					
					/*
						get the batch id of the current product
					*/
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
					
					$int_tax = $_SESSION['arr_total_qty'][$i][7];
						
					$str_query = "
						INSERT INTO ".Monthalize('dc_items')."
							(quantity,
							discount,
							price,
							product_id,
							dc_id,
							batch_id,
							product_description
							)
						VALUES(".
							$_SESSION['arr_total_qty'][$i][2].", ".
							$_SESSION['arr_total_qty'][$i][4].", ".
							number_format($_SESSION['arr_total_qty'][$i][6],3,'.','').", ".
							$_SESSION['arr_total_qty'][$i][13].", ".
							$int_bill_id.", ".
							$current_batch_id.", ".
							"'".addslashes($_SESSION['arr_total_qty'][$i][12])."')
					";
					$result_set->Query($str_query);
					if ($result_set->b_error == true) {
						$bill_items_saved = 0;
						$str_message = "An error occurred trying to save one of the items (".$_SESSION['arr_total_qty'][$i][13].") of the current bill.";
						die($str_query);
						break;
					} 
				}
			}
			else {
				$result_set = new Query("START TRANSACTION");
				
				$bill_saved = 1;
				$bill_items_saved = 1;
				
				/*
					remove all items that were entered before
				*/
				$str_query = "
					DELETE FROM ".Monthalize('dc_items')."
					WHERE dc_id = ".$_SESSION['dc_id'];
				$result_set->Query($str_query);
				
				/*
					insert a row for each item that was billed
				*/
				for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
					
					/*
						get the batch id of the current product
					*/
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
					
					$int_tax = $_SESSION['arr_total_qty'][$i][7];
						
					$str_query = "
						INSERT INTO ".Monthalize('dc_items')."
							(quantity,
							discount,
							price,
							product_id,
							dc_id,
							batch_id,
							product_description
							)
						VALUES(".
							$_SESSION['arr_total_qty'][$i][2].", ".
							$_SESSION['arr_total_qty'][$i][4].", ".
							number_format($_SESSION['arr_total_qty'][$i][6],3,'.','').", ".
							$_SESSION['arr_total_qty'][$i][13].", ".
							$_SESSION['dc_id'].", ".
							$current_batch_id.", ".
							"'".addslashes($_SESSION['arr_total_qty'][$i][12])."')
					";
					$result_set->Query($str_query);
					if ($result_set->b_error == true) {
						$bill_items_saved = 0;
						$str_message = "An error occurred trying to save one of the items (".$_SESSION['arr_total_qty'][$i][13].") of the current bill.";
						break;
					} 
				}
			}
			
			if (($bill_saved == 1) && ($bill_items_saved == 1)) {
				/*
					set 'ok' to update the stock
				*/
				$can_save = 1;
				$result_set->Query("COMMIT");
				
			}
			else {
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
	echo "<script language=\"javascript\">\n";
	echo "if (confirm('DC saved successfully. \\n Would you like to print the DC?'))\n";
	echo "	printBill(".$int_bill_id.");\n";
	echo "setTimeout('top.document.location = \"dc_frameset.php?action=clear_dc\"',500);\n";
	echo "</script>\n";
	
	/*
		clear the session variables related to the billing
	*/
	$_SESSION['dc_id'] = -1;
	$_SESSION['dc_number'] = '';
	unset($_SESSION["arr_total_qty"]);
	unset($_SESSION["arr_item_batches"]);
	$_SESSION['current_dc_day'] = date('j');
	$_SESSION['dc_total'] = 0;
	$_SESSION['save_counter'] = 0;
}
?>


	<input  type="button" class="v3button" name="button_save" value="Save" onclick="javascript:saveBill('save')">
	<input type="button" class="v3button" name="button_cancel" value="Cancel" onclick="javascript:cancelBill(<? echo $_SESSION['current_bill_type']?>)">
	<input type="button" name="button_close" value="Close" class="v3button" onclick="CloseWindow()">
<!--
	<input type="button" name="button_change" value="Change" class="v3button" onclick="javascript:getChange()">
	<input type="button" name="button_promotion" value="Promotion" class="v3button" onclick="javascript:setSalesPromotion()">&nbsp;&nbsp;&nbsp;&nbsp;
-->
	<font class="headertext">[<b>F2</b> Save][<b>F6</b> Discount][<b>F10</b> Exit]</font>
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