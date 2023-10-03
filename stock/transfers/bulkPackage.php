<?
	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../common/product_funcs.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}
	
	$qry_settings = new Query("SELECT stock_is_equal_prices FROM user_settings");
	
?>

<script language="javascript">

	var can_save = false;
	var bool_is_decimal = false;
	var	bulk_weight = 1;
	var pkg_weight = 1;
	var quantity = 0;


	function IsEmpty(aValue) {
		if ((aValue.length==0) || (aValue==null)) {
			return true;
		}
		else {
			return false;
		}
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

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.bulk_package.code;
				var oTextBoxDescription = document.getElementById('description');
				var oTextBoxStock = document.getElementById('bulk_stock');
				var oTextBoxDetails = document.getElementById('bulk_details');
				var oHiddenBPrice = document.bulk_package.hidden_bprice;
				var oHiddenSPrice = document.bulk_package.hidden_sprice;
				var oHiddenTax = document.bulk_package.hidden_tax;

				str_retval = requester.responseText;
				
				if (str_retval == '__NOT_FOUND') {
					can_save = false;
					oTextBoxCode.value = "";
					oTextBoxDescription.innerHTML = '';
					oTextBoxStock.innerHTML = '';
					oTextBoxDetails.innerHTML = '';
				}
				else {
					arr_details = str_retval.split('|');
				
					if (arr_details[2] == "__NOT_AVAILABLE") {
						can_save = false;
						oTextBoxDescription.innerHTML = '';
						oTextBoxStock.innerHTML = '';
						oTextBoxDetails.innerHTML = '';
						alert('This product cannot be received.\n It has been disabled');
						oTextBoxCode.focus();
					}
					else {
						can_save = true;
						
						oTextBoxDescription.innerHTML = arr_details[0];
						oTextBoxStock.innerHTML = arr_details[3] + ' (adjusted: ' + arr_details[4] + ') ' + arr_details[5];
						oTextBoxDetails.innerHTML = arr_details[6] + ' ' + arr_details[7];
						
						bulk_weight = parseFloat(arr_details[6]);
						
						if (arr_details[13] == 'Y')
							bool_is_decimal = true;
						else
							bool_is_decimal = false;
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
	
	
	function stateHandler2() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.bulk_package.pkgcode;
				var oTextBoxDescription = document.getElementById('pkg_description');
				var oTextBoxStock = document.getElementById('pkg_stock');
				var oTextBoxDetails = document.getElementById('pkg_add_stock');
				var oTextBoxQuantity = document.bulk_package.quantity;
				var oHiddenBPrice = document.bulk_package.hidden_bprice;
				var oHiddenSPrice = document.bulk_package.hidden_sprice;
				var oHiddenTax = document.bulk_package.hidden_tax;
				var quantity;
				
				str_retval = requester.responseText;
				
				if (str_retval == '__NOT_FOUND') {
					can_save = false;
					oTextBoxCode.value = "";
					oTextBoxDescription.innerHTML = '';
					oTextBoxStock.innerHTML = '';
					oTextBoxDetails.innerHTML = '';
				}
				else {
					arr_details = str_retval.split('|');
					
					if (arr_details[10] == "__NOT_AVAILABLE") {
							can_save = false;
							oTextBoxPkgDescription.innerHTML = '';
							oTextBoxStock.innerHTML = '';
							oTextBoxDetails.innerHTML = '';
							alert('This product cannot be received.\n It has been disabled');
							oTextBoxPkgCode.focus();
					}
					else {
						can_save = true;
						oTextBoxDescription.innerHTML = arr_details[0];
						oTextBoxStock.innerHTML = arr_details[3] + ' (adjusted: ' + arr_details[4] + ') ' + arr_details[5];
						quantity = parseFloat(oTextBoxQuantity.value);
						pkg_weight = parseFloat(arr_details[6]);
						
						if (isNaN(quantity)) {
							alert('Invalid quantity entered');
						}
						qty_to_add = quantity * (bulk_weight / pkg_weight);
						oTextBoxDetails.innerHTML = qty_to_add.toString();
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

	function setStockToAdd() {
		var oTextBoxDetails = document.getElementById('pkg_add_stock');
		var oTextBoxQuantity = document.bulk_package.quantity;
		quantity = parseFloat(oTextBoxQuantity.value);
		qty_to_add = quantity * (bulk_weight / pkg_weight);
		oTextBoxDetails.innerHTML = qty_to_add.toString();
	}

	function getDescription(strProductCode) {
		requester.onreadystatechange = stateHandler;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester.open("GET", "bulkpackage_product_details.php?live=1&product_code="+strPassValue);
		requester.send(null);
	}

	function getDescription2(strProductCode) {
		requester.onreadystatechange = stateHandler2;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester.open("GET", "bulkpackage_product_details.php?live=1&product_code="+strPassValue);
		requester.send(null);
	}

	function receiveStock() {
		if (can_save == true) {
			document.bulk_package.Save.onclick = '';
			document.bulk_package.submit();
		}
	}
	
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		
		var oTextBoxCode = document.bulk_package.code;
		var oTextBoxPkgCode = document.bulk_package.pkgcode;
		var oTextBoxQuantity = document.bulk_package.quantity;
		var oButtonSave = document.bulk_package.Save;
		
		if (charCode == 113) { // F2 Save
			oButtonSave.click();
		}
		else if (charCode == 13 || charCode == 3) {
			if (focusElem == 'code') {
				oTextBoxCode.select();
			}
			else if (focusElem == 'pkgcode') {
				oTextBoxPkgCode.select();
			}
			else if (focusElem == 'quantity') {
				oTextBoxQuantity.select();
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
			if (focusElem == 'code') {
				oTextBoxCode.select();
			}
			else if (focusElem == 'pkgcode') {
				oTextBoxPkgCode.select();
			}
			else if (focusElem == 'quantity') {
				oTextBoxQuantity.select();
			}
			else if (focusElem == 'button_save') {
				oButtonSave.focus();
			}
		}
		return true;
	}  
	
	function clearValues() {
		var oTextBoxCode = document.bulk_package.code;
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxStock = document.getElementById('current_stock');
		var oTextBoxBPrice = document.bulk_package.b_price;
		var oTextBoxSPrice = document.bulk_package.s_price;
		var oListBoxSupplier = document.bulk_package.list_supplier;
		var oListBoxTax = document.bulk_package.list_tax;
		
		var oHiddenBPrice = document.bulk_package.hidden_bprice;
		var oHiddenSPrice = document.bulk_package.hidden_sprice;
		var oHiddenTax = document.bulk_package.hidden_tax;
		
		oTextBoxCode.value = '';
		oTextBoxDescription.innerHTML = '';
		oTextBoxStock.innerHTML = '';
		oTextBoxBPrice.value = '0.0';
		oTextBoxSPrice.value = '0.0';
		oListBoxSupplier.selectedIndex = 0;
		oListBoxTax.selectedIndex = 0;
		
		oHiddenBPrice.value = 0;
		oHiddenSPrice.value = 0;
		oHiddenTax.value = 0;
	}
	
	function openSearch() {
		myWin = window.open("../../common/product_search.php?formname=bulk_package&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.focus();
	}

	function openSearch2() {
		myWin = window.open("../../common/product_search.php?formname=bulk_package&fieldname=pkgcode",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.focus();
	}


	function batchesCallback() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oListBoxBatches = document.bulk_package.list_batches;
				var oTextBoxCode = document.bulk_package.code;
				
				str_retval = requester.responseText;
				
				if (str_retval == '__NOT_FOUND') {
					can_save = false;
					oTextBoxCode.value = "";
					oTextBoxDescription.innerHTML = '';
				}
				else {
					oListBoxBatches.options.length = 0;
					var arr_lines = str_retval.split('|');
					
					for (i=0;i<arr_lines.length;i++) {
						arr_details = arr_lines[i].split('&');
						oListBoxBatches.options[i] = new Option(arr_details[0]+" ("+arr_details[1]+" Available)",arr_details[0]);
						can_save = true;
					}
				}
			}
			else {
				alert("failed to get batches... please try again... Return was "+requester.status);
			}
	 
		requester = null;
		requester = createRequest();
		}
	}

</script>

<?	
	function get_date($date_day) {
		$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".sprintf("%02d", $date_day)." ".date("H:i:s");

		return $str_date;
	}

	function get_expiry_date($date_day, $date_month, $date_year, $int_increment) {
		$str_date = mktime(0, 0, 0, $date_day, $date_month, $date_year);
		$str_increment = "+".$int_increment." days";
		return date("Y-m-d H:i:s", strtotime($str_increment, $str_date));
	}

	$str_message = '';

	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
			
			$can_save = true;
			$int_shelf_life = 0;
			$int_min_qty = 0;
			
			$qry_settings = new Query("
				SElECT stock_bulk_unit, stock_packaged_unit 
				FROM user_settings 
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
			
			//=========================
			// verify bulk product code
			// and bulk weight
			//-------------------------
			$str_bulk_product_code = '';
			$int_bulk_product_id = -1;
			$int_bulk_product_weight = 1;
			
			$qry = new Query("
				SELECT *
				FROM stock_product
				WHERE (product_code = '".$_POST["code"]."')
					AND (deleted = 'N')
			");
			if ($qry->RowCount() == 0) {
				$str_message = "Bulk product code not found<br>";
				$can_save = false;
			}
			else {
				$int_bulk_product_id = $qry->FieldByName('product_id');
				$str_bulk_product_code = $qry->FieldByName('product_code');
				$int_bulk_product_weight = $qry->FieldByName('product_weight');
				
				if ($int_bulk_product_weight <= 0) {
					$str_message .= "Bulk product's weight not defined<br>";
					$can_save = false;
				}
			}
			
			//============================
			// verify package product code
			// and package weight
			//----------------------------
			$int_package_product_id = -1;
			$int_package_product_weight = 1;
			
			$qry->Query("
				SELECT *
				FROM stock_product
				WHERE (product_code = '".$_POST["pkgcode"]."')
					AND (deleted = 'N')
			");
			if ($qry->RowCount() == 0) {
				$str_message .= "Package product code not found<br>";
				$can_save = false;
			}
			else {
				$int_package_product_id = $qry->FieldByName('product_id');
				$int_package_product_weight = $qry->FieldByName('product_weight');
				
				if ($int_package_product_weight <= 0) {
					$str_message .= "Packaged product's weight not defined<br>";
					$can_save = false;
				}
			}
			
			//=======================================
			// check for valid quantity value entered
			//---------------------------------------
			$int_quantity = 1;
			$int_stock_to_add = 0;
			if (empty($_POST['quantity'])) {
				$str_message .= "Quantity not specified<br>";
				$can_save = false;
			}
			else if (IsSet($_POST['quantity'])) {
				$int_quantity = $_POST['quantity'];
				
				//============================================
				// quantity entered should not be greater than
				// available quantity
				//--------------------------------------------
				$qry->Query("
					SELECT stock_current
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE (product_id = ".$int_bulk_product_id.")
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				if ($qry->FieldByName('stock_current') >= $int_quantity) {
					$int_stock_to_add = $int_quantity * ($int_bulk_product_weight / $int_package_product_weight);
					
					if ($int_stock_to_add <= 0) {
						$str_message .= "Invalid 'stock to add'<br>";
					}
				}
				else {
					$str_message .= "Quantity entered cannot be greater than available quantity<br>";
					$can_save = false;
				}
			}
			else  {
				$str_message .= "Quantity not specified<br>";
				$can_save = false;
			}
			
			if ($can_save) {
				$qry->Query("START TRANSACTION");
				$bool_success = true;
				
				//========================================
				// deduct stock from the bulk product
				// get the list of batches that have stock
				//----------------------------------------
				$arr_batches = get_active_batches($str_bulk_product_code);
				
				if (count($arr_batches) > 0) {
					$int_quantity_to_deduct = $int_quantity;
					$int_current_quantity = $int_quantity;
					$bool_break = false;
					
					for ($i=0;$i<count($arr_batches);$i++) {
						if ($int_current_quantity <= $arr_batches[$i][2]) {
							$int_quantity_to_deduct = $int_current_quantity;
							$bool_break = true;
						}
						else {
							$int_current_quantity = $int_quantity_to_deduct - $arr_batches[$i][2];
							$int_quantity_to_deduct = $arr_batches[$i][2];
						}
						
						//===================================
						// update table STOCK_STOREROOM_BATCH
						//-----------------------------------
						$qry->Query("
							UPDATE ".Monthalize('stock_storeroom_batch')."
							SET stock_available = ROUND(stock_available - ".$int_quantity_to_deduct.", 3)
							WHERE batch_id = ".$arr_batches[$i][0]."
								AND storeroom_id=".$_SESSION["int_current_storeroom"]."
								AND product_id=".$int_bulk_product_id);
						if ($qry->b_error == true) {
							$str_message = "error updating ".Monthalize('stock_storeroom_batch');
							$bool_success = false;
						}
						
						//=====================================
						// update table STOCK_STOREROOM_PRODUCT
						//-------------------------------------
						$qry->Query("
							UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_current = ROUND(stock_current - ".$int_quantity_to_deduct.", 3)
							WHERE product_id = ".$int_bulk_product_id."
								AND storeroom_id=".$_SESSION["int_current_storeroom"]
						);
						if ($qry->b_error == true) {
							$str_message = "error updating ".Monthalize('stock_storeroom_product');
							$bool_success = false;
						}
						
						//===========================
						// update table STOCK_BALANCE
						//---------------------------
						$qry->Query("
							UPDATE ".Yearalize('stock_balance')."
							SET stock_out = stock_out + ".($int_quantity_to_deduct).",
								stock_closing_balance = ROUND(stock_closing_balance - ".($int_quantity_to_deduct).",3)
							WHERE (product_id = ".$int_bulk_product_id.")
								AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
								AND (balance_month = ".$_SESSION["int_month_loaded"].")
								AND (balance_year = ".$_SESSION["int_year_loaded"].")
								
						");
						if ($qry->b_error == true) {
							$str_message = "error updating ".Yearalize('stock_balance');
							$bool_success = false;
						}
						
						//==================
						// create a transfer
						//------------------
						$str_insert = "
							INSERT INTO ".Monthalize('stock_transfer')."
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
								$int_quantity_to_deduct.", '".
								"BULK PACKAGING', '".
								date("Y-m-d",time())."', ".
								"3, ".
								$_SESSION["int_user_id"].", 
								0, ".
								$_SESSION["int_current_storeroom"].", ".
								$int_bulk_product_id.", ".
								$arr_batches[$i][0].", ".
								"0, ".
								TYPE_INTERNAL.", ".
								STATUS_COMPLETED.", ".
								$_SESSION["int_user_id"].", ".
								$_SESSION["int_user_id"].", ".
								"'N'
							)";
						$qry->Query($str_insert);
						if ($qry->b_error == true) {
								$str_message = "Error inserting bulk quantity into ".Monthalize('stock_transfer');
								$bool_success = false;
						}
						
						if ($bool_break == true)
							break;
					}
				}
				else {
					$str_message .= "No active batches found for the bulk product<br>";
					$bool_success = false;
				}
				
				//==================================
				// add stock to the packaged product
				//----------------------------------
				$stock_received = $int_stock_to_add;
				
				//==================================
				// update the adjusted stock, if any
				//----------------------------------
				$qry->Query("
					SELECT stock_adjusted
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE (product_id = ".$int_package_product_id.")
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				
				$str_adjusted = '';
				if ($qry->RowCount() > 0) {
					if ($qry->FieldByName('stock_adjusted') > 0) {
						if ($qry->FieldByName('stock_adjusted') > $stock_received) {
							$qry_adjust = new Query("
								UPDATE ".Monthalize('stock_storeroom_product')."
								SET stock_adjusted = stock_adjusted - ROUND(".$stock_received.", 3)
								WHERE (product_id = ".$int_package_product_id.")
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
							
							$str_adjusted = ", adjusted: ".$stock_received;
							$stock_adjusted = $stock_received;
							$stock_received = 0;
						}
						else {
							$qry_adjust = new Query("
								UPDATE ".Monthalize('stock_storeroom_product')."
								SET stock_adjusted = 0
								WHERE (product_id = ".$int_package_product_id.")
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
							
							$str_adjusted = ", adjusted: ".$qry->FieldByName('stock_adjusted');
							$stock_adjusted = number_format($qry->FieldByName('stock_adjusted'), 3, '.', '');
							$stock_received = $stock_received - $qry->FieldByName('stock_adjusted');
						}
					}
				}
				
				//=========================
				// update table STOCK_BATCH
				//-------------------------
				$flt_bprice = getBuyingPrice($int_package_product_id);
				$flt_sprice = getSellingPrice($int_package_product_id);
				
				$qry->Query("
					SELECT supplier_id, tax_id
					FROM stock_product
					WHERE product_id = ".$int_package_product_id);
				$int_supplier_id = $qry->FieldByName('supplier_id');
				$int_tax_id = $qry->FieldByName('tax_id');
				
				$str_query = "
					INSERT INTO ".Yearalize('stock_batch')."
						(buying_price,
						selling_price,
						date_created,
						opening_balance,
						date_manufacture,
						date_expiry,
						is_active,
						status,
						user_id,
						buyer_id,
						supplier_id,
						product_id,
						storeroom_id,
						tax_id)
					VALUES(".
						$flt_bprice.", ".
						$flt_sprice.", '".
						date('Y-m-d H:i:s', time())."', ".
						$stock_received.", '".
						date('Y-m-d H:i:s', time())."', '".
						date('Y-m-d H:i:s', time())."', ".
						"'Y', '".
						STATUS_COMPLETED."', ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_user_id"].", ".
						$int_supplier_id.", ".
						$int_package_product_id.", ".
						$_SESSION["int_current_storeroom"].", ".
						$int_tax_id."
					)";
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					$str_message = "Error inserting into ".Yearalize('stock_batch');
					$bool_success = false;
				}
				$int_batch_id = $qry->getInsertedID();
				
				//===================================
				// update the batch code
				//-----------------------------------
				$qry->Query("
					UPDATE ".Yearalize('stock_batch')."
					SET batch_code = '$int_batch_id'
					WHERE (batch_id = $int_batch_id)
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				");
				
				//===================================
				// update table STOCK_STOREROOM_BATCH
				//-----------------------------------
				$qry->Query("
					INSERT INTO ".Monthalize('stock_storeroom_batch')."
					(
						stock_available,
						batch_id,
						storeroom_id,
						product_id
					)
					VALUES (
						$stock_received,
						$int_batch_id,
						".$_SESSION["int_current_storeroom"].",
						$int_package_product_id
					)
				");
				if ($qry->b_error == true) {
					$str_message = "error updating ".Monthalize('stock_storeroom_batch');
					$bool_success = false;
				}
				
				//=====================================
				// update table STOCK_STOREROOM_PRODUCT
				//-------------------------------------
				$qry->Query("
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_current = stock_current + ".$stock_received."
					WHERE product_id = ".$int_package_product_id."
						AND storeroom_id=".$_SESSION["int_current_storeroom"]
				);
				if ($qry->b_error == true) {
					$str_message = "error updating ".Monthalize('stock_storeroom_product');
					$bool_success = false;
				}
				
				//===========================
				// update table STOCK_BALANCE
				//---------------------------
				$qry->Query("
					UPDATE ".Yearalize('stock_balance')."
					SET stock_in = stock_in + ".($int_stock_to_add).",
						stock_closing_balance = stock_closing_balance + ".($stock_received)."
					WHERE (product_id = ".$int_package_product_id.")
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (balance_month = ".$_SESSION["int_month_loaded"].")
						AND (balance_year = ".$_SESSION["int_year_loaded"].")
						
				");
				if ($qry->b_error == true) {
					$str_message = "error updating ".Yearalize('stock_balance');
					$bool_success = false;
				}
				
				//======================
				// insert stock_transfer
				//----------------------
				$str_insert = "
					INSERT INTO ".Monthalize('stock_transfer')."
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
						$int_stock_to_add.", '".
						"BULK PACKAGING ".$str_adjusted."', '".
						date("Y-m-d",time())."', ".
						"3, ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_current_storeroom"].", 
						0, ".
						$int_package_product_id.", ".
						$int_batch_id.", ".
						"0, ".
						TYPE_INTERNAL.", ".
						STATUS_COMPLETED.", ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_user_id"].", ".
					"'N')";
				$qry->Query($str_insert);
				if ($qry->b_error == true) {
						$str_message = "error inserting into ".Monthalize('stock_transfer')."- ".$str_insert;
						$bool_success = false;
				}
				
				if ($bool_success == true)
					$qry->Query("COMMIT");
				else {
					$qry->Query("ROLLBACK");
					
					echo "<script language='javascript'>";
					echo "alert('".$str_message."');";
					echo "</script>";
				}
	
			} // end of if (can_save)
		} // end of action == save
	} // end of IsSet($_GET["action"]))

	
?>

<html>
<head>
	<title>Bulk Packaging</title>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>
<body id='body_bgcolor'>
<form name="bulk_package" method="POST" onsubmit="return false">

  <input type="hidden" name="hidden_bprice" value="0">
  <input type="hidden" name="hidden_sprice" value="0">
  <input type="hidden" name="hidden_tax" value="0">

<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../../images/blank.gif");

	if ($str_message <> '')  {
		echo $str_message;
	}
?>
	
	<table width="98%" height="30" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<TD><img src='../../images/blank.gif' width='120px' height='1px'></TD>
			<td><img src='../../images/blank.gif' width='280px' height='1px'></td>
		</tr>
		
		<tr>
			<td align="right" class="normaltext_bold">Bulk Code</td>
			<td><input type="text" class="input_100" name="code" value="" autocomplete="OFF" onblur="javascript:getDescription(this)" onkeypress="focusNext(this, 'quantity', event)">&nbsp;<a href="javascript:openSearch()"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		
		<tr>
			<td align='right' class='normaltext'>Description</td>
			<td class="<?echo $str_class_span?>" id="description">&nbsp;</td>
		</tr>
		
		<tr>
			<td align='right' class='normaltext'>Stock</td>
			<td class="<?echo $str_class_span?>" id="bulk_stock">&nbsp;</td>
		</tr>
		
		<tr>
			<td align='right' class='normaltext'>Packaging details</td>
			<td class="<?echo $str_class_span?>" id="bulk_details">&nbsp;</td>
		</tr>
		
		<tr>
			<td align="right" class="normaltext_bold">Quantity</td>
			<td>
				<input type='text' class='input_100' name='quantity' value='' onblur="javascript:setStockToAdd()" onkeypress="focusNext(this, 'pkgcode', event)" autocomplete="OFF">
			</td>
		</tr>
		
		<tr>
			<td align="right" class="normaltext_bold">Package Code</td>
			<td><input type="text" class="input_100" name="pkgcode" value="" autocomplete="OFF" onblur="javascript:getDescription2(this)" onkeypress="focusNext(this, 'button_save', event)">&nbsp;<a href="javascript:openSearch2()"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		
		<tr>
			<td align='right' class='normaltext'>Description</td>
			<td class="<?echo $str_class_span?>" id="pkg_description">&nbsp;</td>
		</tr>
		
		<tr>
			<td align='right' class='normaltext'>Stock</td>
			<td class="<?echo $str_class_span?>" id="pkg_stock">&nbsp;</td>
		</tr>
		
		<tr>
			<td align='right' class='normaltext'>Stock to be added</td>
			<td class="<?echo $str_class_span?>" id="pkg_add_stock">&nbsp;</td>
		</tr>
		
		<tr>
			<td align="right">
				<input type="hidden" name="action" value="save">
				<? if ($int_access_level > ACCESS_READ) { ?>
					<input type="button" class="mainmenu_button" name="Save" value="Save" onclick="receiveStock()">
				<? } else { ?>
					&nbsp;
				<? } ?>
			</td>
			<td>
				<input type="button" class="mainmenu_button" name="Close" value="Close" onclick="CloseWindow()">
			</td>
		</tr>
		<tr>
			<td align='right'>
				<font class="normaltext">[<b>F2</b> Save]</font>
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
	document.bulk_package.code.focus();
</script>

</body>
</html>