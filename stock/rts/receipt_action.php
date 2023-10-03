<?
//	require_once("/var/www/html/Gubed/Gubed.php");

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/product_funcs.inc.php");

	require_once("rts.inc.php");	


	function getBillDate($int_day) {
		$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".$int_day."-".date("H:i:s");
		return $str_date;
	}
?>

<html>
<head><TITLE></TITLE>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />

	<!-- Bootstrap -->
    <link href="../../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

</head>

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
				
				var oDiscount = parent.frames['frame_total'].document.getElementById("receipt_discount");
				var oGrandTotal = parent.frames['frame_total'].document.getElementById("receipt_grand_total");
				
				if (arr_retval[0] == 'nil') {
					oDiscount.innerHTML = '';
					oGrandTotal.innerHTML = '';
				}
				else {
					oDiscount.innerHTML = "Sales Promotion : " + arr_retval[0];
					oGrandTotal.innerHTML = "Grand Total : " + arr_retval[1];
				}
			}
			else {
				alert("failed to get change... please try again.");
			}
			requester2 = null;
			requester2 = createRequest();
		}
	}


	function cancelBill(bConfirm='Y') {

		var oList = parent.frames['frame_list'].billTable;

		if (oList.data().count() > 0) {

			if (bConfirm=='Y') {
				
				if (confirm('Are you sure you want to cancel the receipt?')) {

					parent.frames["frame_action"].document.location = "receipt_action.php?action=cancel";
					parent.frames["frame_supplier"].document.location = "receipt_supplier.php?action=cancel";

					parent.frames["frame_enter"].document.receipt_enter.code.focus();
				}
			}
			else {
					parent.frames["frame_action"].document.location = "receipt_action.php?action=cancel";
					parent.frames["frame_supplier"].document.location = "receipt_supplier.php?action=cancel";

					parent.frames["frame_enter"].document.receipt_enter.code.focus();
			}
		}
		else
			alert('list is empty');
	}


	function saveBill(strAction) {
		
		var oList = parent.frames['frame_list'].billTable;
		var oButtonSave = document.receipt_action.button_save;
		
//		oButtonSave.onclick = '';

		if (oList.data().count() > 0) {
			document.location = 'receipt_action.php?action=' + strAction;
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


	function printBill(aBillId) {
		myWin = window.open("print_receipt.php?id="+aBillId, 'printwin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=450,height=250');
		myWin.focus();
	}
	

	function print_temp_bill() {
		myWin = window.open("print_temp_receipt.php", 'printwin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=450,height=250');
		myWin.focus();
	}
	

	function CloseWindow(ignore='N') {

		if (ignore=='Y') {

			cancelBill();

			window.close('','_parent','');

		}
		else {

			var oList = parent.frames['frame_list'].billTable;

			if (oList.data().count() > 0) {

				if (confirm('Items have been entered. \n Exit anyway?')) {
					window.close('','_parent','');
				}

			}
			else {
				window.close('','_parent','');
			}
		}
	}

</script>

<style>
	body {
		margin-left:10px;
	}
</style>

</head>

<body id='body_bgcolor'>

<form name="receipt_action" method="GET">

<?

$err_status = 1;
$can_save = true;

if (IsSet($_GET['action'])) {
	
	if ($_GET["action"] == 'close') {

		echo "<script language=\"javascript\">";
		echo "CloseWindow();";
		echo "</script>";

	}

	else if ($_GET["action"] == 'save') {

		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
			$str_message = '';
		}
		else {
			$str_message = 'Cannot receive in previous months. \\n Select the current month/year and continue.';
			$can_save = false;
		}

		/*
			receipt has been edited
		*/
		if (!is_null($_SESSION['stock_rts_id'])) {

			$rts = new RTS();
			$rts->stock_rts_id 	= $_SESSION['stock_rts_id'];
			$rts->invoice_number = $_SESSION['current_invoice_number'];
			$rts->invoice_date 	= $_SESSION['current_invoice_date'];
			$rts->bill_number 	= $_SESSION['current_bill_number'];
			$rts->description 	= $_SESSION['current_note'];

			$ret = $rts->update();

			$can_save = false;
		}

		
		if ($can_save) {

			// start transaction
			$result_set = new Query("BEGIN");
	
			// insert row in rts table
			$result_set->Query("
				INSERT INTO ".Monthalize('stock_rts')."
					(storeroom_id,
					bill_number,
					date_created,
					total_amount,
					discount,
					bill_status,
					user_id,
					supplier_id,
					module_id,
					description,
					invoice_number,
					invoice_date)
				VALUES (".
					$_SESSION['int_current_storeroom'].", ".
					"'".$_SESSION['current_bill_number']."', '".
					getBillDate($_SESSION['current_bill_day'])."', ".
					$_SESSION['bill_total'].", ".
					$_SESSION["current_discount"].", ".
					BILL_STATUS_RESOLVED.", ".
					$_SESSION['int_user_id'].", ".
					$_SESSION["current_supplier_id"].",
					2,'".
					$_SESSION['current_note']."',".
					"'".$_SESSION['current_invoice_number']."',".
					"'".$_SESSION['current_invoice_date']."')");
			if ($result_set->b_error == true) {
				$str_message = "ERROR: ".mysql_error();
				$can_save = false;
			}
			$receipt_id = $result_set->getInsertedID();
	
			// insert a row for each item returned
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
	
				// get the batch id first
				$result_set->Query("
					SELECT batch_id
					FROM ".Yearalize('stock_batch')."
					WHERE (batch_code = '".$_SESSION['arr_total_qty'][$i][1]."') AND
						(product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				$current_batch_id = $result_set->FieldByName('batch_id');
				
				$flt_buying_price = getBuyingPrice($_SESSION['arr_total_qty'][$i][13], $current_batch_id);
				
				// save the row
				$result_set->Query("
					INSERT INTO ".Monthalize('stock_rts_items')."
						(quantity,
						bprice,
						price,
						product_id,
						rts_id,
						batch_id,
						tax_id
						)
					VALUES(".
						$_SESSION['arr_total_qty'][$i][2].", ".
						$flt_buying_price.", ".
						$_SESSION['arr_total_qty'][$i][6].", ".
						$_SESSION['arr_total_qty'][$i][13].", ".
						$receipt_id.", ".
						$current_batch_id.", ".
						$_SESSION['arr_total_qty'][$i][7].")
				");
				if ($result_set->b_error == true) {
					$str_message = "ERROR: ".mysql_error;
					$can_save = false;
				}
			}
			
			for ($i=0; $i<count($_SESSION['arr_total_qty']); $i++) {
			
				// get the batch_id
				$result_set->Query("
					SELECT batch_id, supplier_id
					FROM ".Yearalize('stock_batch')."
					WHERE (batch_code = '".$_SESSION['arr_total_qty'][$i][1]."') AND
						(product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				$current_batch_id = $result_set->FieldByName('batch_id');
				
				// double check whether more than the quantity available in the batch was entered
				if ($_SESSION["arr_total_qty"][$i][5] > 0) {
					$can_save = false;
				}
				
				if ($can_save == false)
					$str_message = "Error with quantities entered. \n Cancel the receipt and re-enter.";
				
				$flt_quantity_to_bill = $_SESSION['arr_total_qty'][$i][2];
	
	// TABLE stock_storeroom_product
				$result_set->Query("UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_current = ABS(ROUND((stock_current - ".$flt_quantity_to_bill."),3))
					WHERE (product_id=".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id=".$_SESSION["int_current_storeroom"].")");
				if ($result_set->b_error == true) {
					$can_save = false;
					$str_message = "Error updating stock_storeroom_product";
				}
	
	// TABLE stock_storeroom_batch
	// There was some strange behaviour subtracting here, hence the ROUND function call
	// very small amounts were generated, as -7.12548e-9
				$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
					SET stock_available = ROUND((stock_available - ".$flt_quantity_to_bill."),3)
					WHERE (batch_id = ".$current_batch_id.") AND
						(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(product_id = ".$_SESSION['arr_total_qty'][$i][13].")
				");
				if ($result_set->b_error == true) {
					$can_save = false;
					$str_message = "Error updating stock_storeroom_batch";
				}
	
				// if the current stock becomes zero, then set the batch's is_active flag to false
				// if there is more than one active batch available. There should always be one active
				// batch regardless of the available stock
				$result_set->Query("SELECT stock_available 
					FROM ".Monthalize('stock_storeroom_batch')."
					WHERE (batch_id = ".$current_batch_id.") AND
						(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(product_id = ".$_SESSION['arr_total_qty'][$i][13].")
					");
				if ($result_set->FieldByName('stock_available') <= 0) {
					// check number of available active batches, and if it is greater than one
					// set the current batch's is_active flag to false
					$qry_check = new Query("SELECT * 
						FROM ".Monthalize('stock_storeroom_batch')." 
						WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
							(product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND 
							(is_active = 'Y')
					");
					if ($qry_check->RowCount() > 1) {
						$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
						SET is_active = 'N'
						WHERE (batch_id = ".$current_batch_id.") AND
							(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
							(product_id = ".$_SESSION['arr_total_qty'][$i][13].")
						");
					}
				}
	
	// TABLE stock_balance
				$result_set->Query("
					SELECT *
					FROM ".Yearalize('stock_balance')."
						WHERE (product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
							(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
							(balance_month = ".$_SESSION["int_month_loaded"].") AND
							(balance_year = ".$_SESSION["int_year_loaded"].")
				");
				if ($result_set->RowCount() > 0) {
					$result_set->Query("UPDATE ".Yearalize('stock_balance')."
							SET stock_returned = stock_returned + ".$flt_quantity_to_bill.",
								stock_closing_balance = ROUND((stock_closing_balance - ".$flt_quantity_to_bill."),3)
							WHERE (product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
								(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
								(balance_month = ".$_SESSION["int_month_loaded"].") AND
								(balance_year = ".$_SESSION["int_year_loaded"].")");
					if ($result_set->b_error == true) {
						$can_save = false;
						$str_message = "Error updating stock_balance";
					}
				}
				else {
					$result_set->Query("
						INSERT INTO ".Yearalize('stock_balance')."
						(
							stock_returned,
							stock_closing_balance,
							product_id,
							storeroom_id,
							balance_month,
							balance_year
						)
						VALUES(
							".$flt_quantity_to_bill.",
							".$flt_quantity_to_bill.",
							".$_SESSION['arr_total_qty'][$i][13].",
							".$_SESSION["int_current_storeroom"].",
							".$_SESSION["int_month_loaded"].",
							".$_SESSION["int_year_loaded"]."
						)
					");
				}
	
	// TABLE stock_transfer
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
						$_SESSION['arr_total_qty'][$i][2].", '". 					// here, only index 2 is saved, as a manual transfer
						"RETURN TO SECTION NUMBER ".$_SESSION['current_bill_number']."', '".  	// for the remaining amount is 
						getBillDate($_SESSION['current_bill_day'])."', ".			// already created
						"2, ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_current_storeroom"].", ".
						"0, ".
						$_SESSION['arr_total_qty'][$i][13].", ".
						$current_batch_id.", ".
						$receipt_id.", ".
						TYPE_RETURNED.", ".
						STATUS_COMPLETED.", ".
						$_SESSION["int_user_id"].", ".
						"0, ".
						"'N')");
				if ($result_set->b_error == true) {
					$can_save = false;
					$str_message = "Error updating stock_transfer";
				}
			}
			
			if ($can_save) {
				$result_set->Query("COMMIT");
				
				echo "<script language=\"javascript\">\n";
				echo "if (confirm('Receipt saved successfully. \\n Would you like to print the receipt?'))\n";
				echo "	printBill(".$receipt_id.");\n";
				//echo "parent.frames[\"frame_supplier\"].document.location = \"receipt_supplier.php?action=cancel\";\n";
				echo "cancelBill('N');";
				echo "</script>\n";
			}
			else {
				$result_set->Query("ROLLBACK");
				echo "<script language=\"javascript\">\n";
				echo "alert('".$str_message."');\n";
				echo "</script>\n";
			}
		}
		else {
			echo "<script language=\"javascript\">\n";
			echo "alert('".$rts->ret['msg']."');\n";
			if (!$rts->ret['error'])
				echo "CloseWindow('Y');";

			echo "</script>\n";
		}
	} // end if (action ='save')

} // end if set $_GET['action']
?>

	<button type="button" id="btn-save" class="btn btn-primary">Save (F2)</button>
	<button type="button" id="btn-print" class="btn btn-primary" name="button_print" value="Print">Print</button>
	<button type="button" id="btn-cancel" class="btn btn-primary" name="button_cancel" value="Cancel">Cancel</button>
	<!-- <button type="button" id="btn-close" class="btn btn-primary" name="button_close" value="Close">Close</button> -->


<br>

<?php if (!is_null($_SESSION['stock_rts_id'])) { ?>
	<span class="normaltext"><i>In edit mode, only 'D.N. Reference', Note', 'Inv No' and 'Inv Dt' can be changed."</i></span>
<?php } ?>	

</form>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../../include/js/jquery-3.2.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

    <script>

		$(document).ready(function(){

			$(" #btn-save ").on("click", function(e) {

				oBillNumber = parent.frames['frame_supplier'].bill_number;
				oSupplier = parent.frames['frame_supplier'].list_supplier;
				oDay = parent.frames['frame_supplier'].list_day;
				oNote = parent.frames['frame_supplier'].note;

				$.ajax({
					method	: "POST",
					url		: "session_vars.php",
					data 	: { bill_number: oBillNumber.value, list_supplier: oSupplier.value, list_day: oDay.value, note: oNote.value }
				})
				.done(function( msg ) {

					console.log( msg );

					saveBill('save');
				});

			});



			$(" #btn-print ").on("click", function(e) {

				print_temp_bill();

			});


			$(" #btn-cancel ").on("click", function(e) {

				cancelBill();

			});


			$(" #btn-close ").on("click", function(e) {

				CloseWindow();

			});

		});

	</script>

</body>
</html>