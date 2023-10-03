<?
	require_once("../../include/const.inc.php");
	require_once("../../include/config.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../include/session.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}

	$qry_settings = new Query("SELECT stock_is_equal_prices FROM user_settings");
	
	$qry_suppliers = new Query("
		SELECT *
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");

	
	# pass the suppliers list to javascript
	echo "<script language='JavaScript'>\n";
	echo "var arr_suppliers = new Array();\n";
	$counter = 0;
	for ($i=0;$i<$qry_suppliers->RowCount();$i++) {
		echo "arr_suppliers[".$counter++."] = '".$qry_suppliers->FieldByName('supplier_id')."';\n";
		echo "arr_suppliers[".$counter++."] = '".str_replace('\'', '\\\'', $qry_suppliers->FieldByName('supplier_name'))."';\n";
		$qry_suppliers->Next();
	}
	echo "</script>\n";

?>
<script language="javascript">

	var can_save = false;
	var bool_is_decimal = false;

	function IsEmpty(aValue) {
		if ((aValue.length==0) || (aValue==null)) {
			return true;
		}
		else {
			return false;
		}
	}

	function setMnfrDay() {
		var oTextBoxRcvdDay = document.direct_receive.list_rcvd_day;
		var oTextBoxMnfrDay = document.direct_receive.list_mnfr_day;
		oTextBoxMnfrDay.selectedIndex = oTextBoxRcvdDay.selectedIndex;
	}
  
	function setSPrice(is_equal, int_margin) {
		var oTextBoxBPrice = document.direct_receive.b_price;
		var oTextBoxSPrice = document.direct_receive.s_price;
		var fltCalculatedPrice = 0;
		
		if (is_equal == 'Y') {
			oTextBoxSPrice.value = oTextBoxBPrice.value;
		}
		else if (isNaN(int_margin) == false) {
			if ((oTextBoxSPrice.value == '') || (oTextBoxSPrice.value == null)) {
				fltCalculatedPrice = Math.round(oTextBoxBPrice.value * (1 + (int_margin/100))*100)/100;
				oTextBoxSPrice.value = fltCalculatedPrice.toFixed(2);
			}
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
	var requester_tax = createRequest();

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.direct_receive.code;
				var oTextBoxDescription = document.getElementById('description');
				var oTextBoxStock = document.getElementById('current_stock');
				var oTextBoxAdjusted = document.getElementById('adjusted_stock');
				var oTextBoxTaxIncluded = document.adjust_tax;
				var oTextBoxBPrice = document.direct_receive.b_price;
				var oTextBoxSPrice = document.direct_receive.s_price;
				var oListBoxSupplier = document.direct_receive.list_supplier;
				var oListBoxTax = document.direct_receive.list_tax;
				var oHiddenBPrice = document.direct_receive.hidden_bprice;
				var oHiddenSPrice = document.direct_receive.hidden_sprice;
				var oHiddenTax = document.direct_receive.hidden_tax;
				

				str_retval = requester.responseText;

				if (str_retval == '__NOT_FOUND') {
				  can_save = false;
					oTextBoxCode.value = "";
					oTextBoxDescription.innerHTML = '';
				}
				else {
					arr_details = str_retval.split('|');

				if (arr_details[10] == "__NOT_AVAILABLE") {
					can_save = false;
						oTextBoxDescription.innerHTML = '';
						alert('This product cannot be received.\n It has been disabled');
						oTextBoxCode.focus();
				}
				else {
				
					can_save = true;

					oTextBoxDescription.innerHTML = arr_details[0];
					oTextBoxStock.innerHTML = arr_details[11]+" "+arr_details[12];
					oTextBoxAdjusted.innerHTML = arr_details[14]+" adjusted stock";
					oTextBoxBPrice.value = arr_details[1];
					oTextBoxSPrice.value = arr_details[2];
					// these are set in case the user does not have admin access
					// and cannot change the prices and tax
					oHiddenBPrice.value = arr_details[1];
					oHiddenSPrice.value = arr_details[2];
					oHiddenTax.value = arr_details[3];

					if (arr_details[13] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;

					// populate the supplier list
					oListBoxSupplier.options.length = 0;
					if (!IsEmpty(arr_details[4])) {
						oListBoxSupplier.options[0] = new Option(arr_details[5], arr_details[4]);
					}
					if (!IsEmpty(arr_details[6])) {
						oListBoxSupplier.options[1] = new Option(arr_details[7], arr_details[6]);
					}
					if (!IsEmpty(arr_details[8])) {
						oListBoxSupplier.options[2] = new Option(arr_details[9], arr_details[8]);
					}
					counter = oListBoxSupplier.options.length;
					for (i=0;i<arr_suppliers.length;i=i+2) {
						oListBoxSupplier.options[counter] = new Option(arr_suppliers[i+1], arr_suppliers[i]);
						counter++;
					}
				
					// set the tax list selectedIndex
					for (i=0;i<oListBoxTax.options.length;i++) {
						if (oListBoxTax.options[i].value == arr_details[3]) {
							oListBoxTax.selectedIndex = i;
							break;
					 	}
					}
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
	

	function stateHandler_tax() {
		if (requester_tax.readyState == 4) {
			if (requester_tax.status == 200)  {
				var oTextBoxSPrice = document.direct_receive.s_price;
				var oSpanTax = document.getElementById('tax_details');
				
				str_retval = requester_tax.responseText;
				
				flt_price = parseFloat(oTextBoxSPrice.value);
				flt_tax = parseFloat(str_retval);
				flt_total = flt_price + flt_tax;
				flt_total = flt_total.toFixed(2);
				
				oSpanTax.innerHTML = flt_total;
			}
			else {
				alert("failed to get tax details... please try again.");
			}
			requester_tax = null;
			requester_tax = createRequest();
		}
	}

	function getDescription(strProductCode) {
		requester.onreadystatechange = stateHandler;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester.open("GET", "productDetails.php?live=1&product_code="+strPassValue);
		requester.send(null);
	}

	function getPriceWithTax() {
		var oTextBoxSPrice = document.direct_receive.s_price;
		var oListBoxTax = document.direct_receive.list_tax;
		
		requester_tax.onreadystatechange = stateHandler_tax;
		requester_tax.open("GET", "get_product_tax_details.php?live=1&tax_id="+oListBoxTax.value+"&price="+oTextBoxSPrice.value);
		requester_tax.send(null);
	}
	
	function receiveStock() {
		if (can_save == true) {
			document.direct_receive.Save.onclick = '';
			document.direct_receive.submit();
		}
	}
	
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.direct_receive.code;
		var oTextBoxBatch = document.direct_receive.batch;
		var oTextBoxReceived = document.direct_receive.received;
		var oTextBoxRcvdDay = document.direct_receive.list_rcvd_day;
		var oTextBoxBPrice = document.direct_receive.b_price;
		var oTextBoxSPrice = document.direct_receive.s_price;
		var oTextBoxTax = document.direct_receive.list_tax;
		var oTextBoxMnfrDay = document.direct_receive.list_mnfr_day;
		var oTextBoxMnfrMonth = document.direct_receive.list_mnfr_month;
		var oTextBoxMnfrYear = document.direct_receive.list_mnfr_year;
		var oTextBoxSupplier = document.direct_receive.list_supplier;
		var oButtonSave = document.direct_receive.Save;
		
	
		if (charCode == 113) { // F2 Save
			oButtonSave.click();
                }
		else if (charCode == 13 || charCode == 3) {
			if (focusElem == 'batch') {
				oTextBoxBatch.select();
			}
			else if (focusElem == 'received') {
			 oTextBoxReceived.select();
			}
			else if (focusElem == 'list_rcvd_day') {
			 oTextBoxRcvdDay.focus();
			}
			else if (focusElem == 'b_price') {
			 if (oTextBoxBPrice.disabled == true)
			   oTextBoxMnfrDay.focus();
			 else
			   oTextBoxBPrice.focus();
			}
			else if (focusElem == 's_price') {
			 oTextBoxSPrice.focus();
			}
			else if (focusElem == 'list_tax') {
			 oTextBoxTax.focus();
			}
			else if (focusElem == 'list_mnfr_day') {
			 oTextBoxMnfrDay.focus();
			}
			else if (focusElem == 'list_mnfr_month') {
			 oTextBoxMnfrMonth.focus();
			}
			else if (focusElem == 'list_mnfr_year') {
			 oTextBoxMnfrYear.focus();
			}
			else if (focusElem == 'list_supplier') {
			 oTextBoxSupplier.focus();
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
			if (focusElem == 'received') {
			 //if (oTextBoxBatch.value.length == 0)
			   //oTextBoxCode.select();
			}
			else if (focusElem == 'list_rcvd_day') {
			 if (oTextBoxReceived.value.length == 0)
			   // oTextBoxBatch.focus(); as the batch field is disabled
                           oTextBoxCode.focus();
			}
			else if (focusElem == 'b_price') {
			 oTextBoxReceived.focus();
			}
			else if (focusElem == 's_price') {
			 if (oTextBoxBPrice.value.length == 0)
			   oTextBoxRcvdDay.focus();
			}
			else if (focusElem == 'list_tax') {
			 if (oTextBoxSPrice.value.length == 0)
			   oTextBoxBPrice.focus()
			}
			else if (focusElem == 'list_mnfr_day') {
			 oTextBoxSPrice.focus();
			}
			else if (focusElem == 'list_mnfr_month') {
			 oTextBoxTax.focus();
			}
			else if (focusElem == 'list_mnfr_year') {
			 oTextBoxMnfrDay.focus();
			}
			else if (focusElem == 'list_supplier') {
			 oTextBoxMnfrMonth.focus();
			}
			else if (focusElem == 'button_save') {
			 oTextBoxMnfrYear.focus();
			}
		}
		else if (charCode == 46) {
			if (focusElem == 'list_rcvd_day') {
				if (bool_is_decimal == false)
					return false;
			}
		}
		return true;
	}  
	
	function clearValues() {
		var oTextBoxCode = document.direct_receive.code;
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxStock = document.getElementById('current_stock');
		var oTextBoxBPrice = document.direct_receive.b_price;
		var oTextBoxSPrice = document.direct_receive.s_price;
		var oListBoxSupplier = document.direct_receive.list_supplier;
		var oListBoxTax = document.direct_receive.list_tax;
		var oHiddenBPrice = document.direct_receive.hidden_bprice;
		var oHiddenSPrice = document.direct_receive.hidden_sprice;
		var oHiddenTax = document.direct_receive.hidden_tax;
		var oSpanTax = document.getElementById('tax_details');
		
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
		oSpanTax.innerHTML = '';
	}
	
	function openSearch() {
		myWin = window.open("../../common/product_search.php?formname=direct_receive&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,top=0');
		myWin.focus();
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
	 
			// verify product code
			$can_save = true;
			$int_product_id = -1;
			$int_shelf_life = 0;
			$int_min_qty = 0;

			$qry = new Query("
				SELECT *
				FROM stock_product
				WHERE (product_code = '".$_POST["code"]."')
					AND (deleted = 'N')
			");
			if ($qry->RowCount() == 0) {
				$str_message = "product code not found";
				$can_save = false;
			}
			else {
				$int_product_id = $qry->FieldByName('product_id');
				$int_shelf_life = $qry->FieldByName('shelf_life');
				$int_min_qty = $qry->FieldByName('minimum_qty');
			}
			
			if ((number_format($_POST["received"], 3, '.', '') <= 0) || (empty($_POST["received"]))) {
				$str_message = "Received quantity must be greater than zero";
				$can_save = false;
			}
			
			if (empty($_POST["list_supplier"])) {
				$str_message = "The main supplier must be set for this product before you can receive stock.";
				$can_save = false;
			}
			
			
			if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
				// do nothing
			}
			else {
				$str_message = 'Cannot receive in previous months. \\n Select the current month/year and continue.';
				$can_save = false;
			}
			
			if ($int_access_level < ACCESS_ADMIN) {
				$flt_bprice = $_POST["hidden_bprice"];
				$flt_sprice = $_POST["hidden_sprice"];
				$int_tax = $_POST["hidden_tax"];
			
			}
			else {
				/*
				if (empty($_POST["b_price"])) {
					$str_message = "Buying price must have a value";
					$can_save = false;
				}
				else
				*/
					$flt_bprice = $_POST["b_price"];
				/*
				if (empty($_POST["s_price"])) {
					$str_message = "Selling price must have a value";
					$can_save = false;
				}
				else
				*/
					$flt_sprice = $_POST["s_price"];
			
				if (!empty($_POST["list_tax"]))
					$int_tax = $_POST["list_tax"];
			}

			if ($can_save) {
			
				if (@$_POST['adjust_tax']<>'') {
					 require_once('..\..\common\tax.php');
				   $f_tax = calculateTax($flt_sprice,$int_tax);
				   $flt_sprice = $flt_sprice - $f_tax;
				}
			
				$qry->Query("START TRANSACTION");
				$bool_success = true;
		
				if (empty($_POST["received"])) {
					$stock_received = 0;
					$actual_stock_received = 0;
				}
				else {
					$stock_received = number_format($_POST["received"], 3, '.', '');
					$actual_stock_received = number_format($_POST["received"], 3, '.', '');
				}
				$str_adjusted = "";
				$stock_adjusted = 0;
				
				/*
					the following if got commented out
					because adjusted stock should always
					be updated
				*/
				// update the adjusted stock, if any
//				if (IsSet($_POST["adjust_stock"])) {
					$qry->Query("
						SELECT stock_adjusted
						FROM ".Monthalize('stock_storeroom_product')."
						WHERE (product_id = ".$int_product_id.")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
					if ($qry->RowCount() > 0) {
						if ($qry->FieldByName('stock_adjusted') > 0) {
							if ($qry->FieldByName('stock_adjusted') > $stock_received) {
								// update the stock_adjusted in stock_storeroom_product
								$qry_adjust = new Query("
									UPDATE ".Monthalize('stock_storeroom_product')."
									SET stock_adjusted = ROUND(stock_adjusted - ".$stock_received.", 3)
									WHERE (product_id = ".$int_product_id.")
										AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
								");
								
								$str_adjusted = ", adjusted: ".$stock_received;
								$stock_adjusted = $stock_received;
								$stock_received = 0;
							}
							else {
								// update the stock_adjusted in stock_storeroom_product
								$qry_adjust = new Query("
									UPDATE ".Monthalize('stock_storeroom_product')."
									SET stock_adjusted = 0
									WHERE (product_id = ".$int_product_id.")
										AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
								");
								
								$str_adjusted = ", adjusted: ".$qry->FieldByName('stock_adjusted');
								$stock_adjusted = number_format($qry->FieldByName('stock_adjusted'), 3, '.', '');
								$stock_received = $stock_received - $qry->FieldByName('stock_adjusted');
							}
						}
					}
//				}
				
				// check stock_batch
				if (IsSet($_POST["batch"])) { 
					// check whether given batch exists
					$qry->Query("
						SELECT *
						FROM ".Yearalize('stock_batch')."
						WHERE (batch_code = '".$_POST["batch"]."')
							AND (product_id = ".$int_product_id.")
							AND (is_active = 'Y')
							AND (status = ".STATUS_COMPLETED.")
							AND (deleted = 'N')
							AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					");
					$int_batch_id = -1;
					if ($qry->b_error == true) {
						$bool_success = false;
						$str_message = "error retrieving batch in ".Yearalize('stock_batch')." :: ".mysql_error();
					}
					if ($qry->RowCount() > 0) {
						// use existing batch
						$int_batch_id = $qry->FieldByName('batch_id');
					}
					else {
					// create new batch
						$str_batch = "INSERT INTO ".Yearalize('stock_batch')."
								(batch_code,
								buying_price,
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
							VALUES('".
								$_POST["batch"]."', ".
								$flt_bprice.", ".
								$flt_sprice.", '".
								get_date($_POST["list_rcvd_day"])."', ".
								$stock_received.", '".
								get_date($_POST["list_mnfr_day"])."', '".
								get_expiry_date($_POST["list_mnfr_day"], $_POST["list_mnfr_month"], $_POST["list_mnfr_year"], $int_shelf_life)."', ".
								"'Y', '".
								STATUS_COMPLETED."', ".
								$_SESSION["int_user_id"].", ".
								$_SESSION["int_user_id"].", ".
								$_POST["list_supplier"].", ".
								$int_product_id.", ".
								$_SESSION["int_current_storeroom"].", ".
								$int_tax."
								)";

						$qry->Query($str_batch);
						if ($qry->b_error == true) {
							$bool_success = false;
							$str_message = "error (batch enabled) inserting into ".Yearalize('stock_batch')." :: ".mysql_error();
						}
						else {
							$int_batch_id = $qry->getInsertedID();
						}
					}
				}
				else {
					// create batch and save batch code 
					$str_query = "INSERT INTO ".Yearalize('stock_batch')."
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
								get_date($_POST["list_rcvd_day"])."', ".
								$stock_received.", '".
								get_date($_POST["list_mnfr_day"])."', '".
								get_expiry_date($_POST["list_mnfr_day"], $_POST["list_mnfr_month"], $_POST["list_mnfr_year"], $int_shelf_life)."', ".
								"'Y', '".
								STATUS_COMPLETED."', ".
								$_SESSION["int_user_id"].", ".
								$_SESSION["int_user_id"].", ".
								$_POST["list_supplier"].", ".
								$int_product_id.", ".
								$_SESSION["int_current_storeroom"].", ".
								$int_tax."
								)";
					$qry->Query($str_query);
					
					$int_batch_id = -1;
					
					if ($qry->b_error == true) {
						$str_message = "error (batch disabled) inserting into ".Yearalize('stock_batch')." :: ".mysql_error();
						$bool_success = false;
					}
					else {
						$int_batch_id = $qry->getInsertedID();
						
						// set the batch code to the autoincremental value of batch_id 
						$qry->Query("
							UPDATE ".Yearalize('stock_batch')."
							SET batch_code = '".$int_batch_id."'
							WHERE (batch_id=".$int_batch_id.")
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
					}
				}
				
				// update stock_storeroom_product
				// check whether an entry exists already
				$qry->Query("
					SELECT *
					FROM ".Monthalize('stock_storeroom_product')."
						WHERE (product_id = ".$int_product_id.") AND
							(storeroom_id = ".$_SESSION["int_current_storeroom"].")
				");
				if ($qry->RowCount() > 0) {
					$qry->Query("UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_current = stock_current + ".$stock_received.",
							buying_price = ".$flt_bprice.",
							sale_price = ".$flt_sprice."
						WHERE (product_id=".$int_product_id.") AND
							(storeroom_id=".$_SESSION["int_current_storeroom"].")");
					if ($qry->b_error == true) {
						$str_message = "error updating ".Monthalize('stock_storeroom_product');
						$bool_success = false;
					}
				}
				else {
					$qry->Query("
						INSERT INTO ".Monthalize('stock_storeroom_product')."
						(product_id,
							storeroom_id,
							stock_current,
							stock_minimum,
							buying_price,
							sale_price)
						VALUES(".
							$int_product_id.", ".
							$_SESSION["int_current_storeroom"].", ".
							$stock_received.", ".
							$int_min_qty.", ".
							$flt_bprice.", ".
							$flt_sprice.")
					");
					if ($qry->b_error == true) {
						$str_message = "error inserting into ".Monthalize('stock_storeroom_product');
						$bool_success = false;
					}
				}
				
				// flag is_active to false where stock_available is zero or below
				$qry->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
					SET is_active = 'N',
						debug = 'receive'
					WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (product_id = ".$int_product_id.")
						AND (stock_available <= 0)
				");
				
				// insert stock_storeroom_batch
				$qry->Query("INSERT INTO ".Monthalize('stock_storeroom_batch')."
						(stock_available,
						shelf_id,
						batch_id,
						storeroom_id,
						product_id)
					VALUES (".$stock_received.",
						0, ".
						$int_batch_id.", ".
						$_SESSION["int_current_storeroom"].", ".
						$int_product_id.")");
				if ($qry->b_error == true) {
					$str_message = "error updating ".Monthalize('stock_storeroom_batch');
					$bool_success = false;
				}

				// update stock_balance
				$qry->Query("
					SELECT *
					FROM ".Yearalize('stock_balance')."
					WHERE (product_id = ".$int_product_id.")
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (balance_month = ".$_SESSION["int_month_loaded"].")
						AND (balance_year = ".$_SESSION["int_year_loaded"].")
						
				");
				if ($qry->RowCount() > 0) {
					$qry->Query("
						UPDATE ".Yearalize('stock_balance')."
						SET stock_received = stock_received + ".$actual_stock_received.",
							stock_closing_balance = stock_closing_balance + ".$stock_received."
						WHERE (product_id = ".$int_product_id.")
							AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
							AND (balance_month = ".$_SESSION["int_month_loaded"].")
							AND (balance_year = ".$_SESSION["int_year_loaded"].")
							
					");
					if ($qry->b_error == true) {
						$str_message = "error updating ".Yearalize('stock_balance');
						$bool_success = false;
					}
				}
				else {
					$qry->Query("
						INSERT INTO ".Yearalize('stock_balance')."
							(stock_closing_balance,
							balance_month,
							balance_year,
							stock_received,
							product_id,
							storeroom_id)
						VALUES (".
							$stock_received.", ".
							$_SESSION["int_month_loaded"].", ".
							$_SESSION["int_year_loaded"].", ".
							$stock_received.", ".
							$int_product_id.", ".
							$_SESSION["int_current_storeroom"].")
					");
					if ($qry->b_error == true) {
						$str_message = "error inserting into ".Yearalize('stock_balance');
						$bool_success = false;
					}
				}
				
				// insert stock_transfer
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
						$actual_stock_received.", '".
						"DIRECT RECEIVE".$str_adjusted."', '".
						get_date($_POST["list_rcvd_day"])."', ".
						"1, ".
						$_SESSION["int_user_id"].", ".
						"0, ".
						$_SESSION["int_current_storeroom"].", ".
						$int_product_id.", ".
						$int_batch_id.", ".
						"0, ".
						TYPE_RECEIVED.", ".
						STATUS_COMPLETED.", ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_user_id"].", ".
					"'N')";
//echo $str_insert;
				$qry->Query($str_insert);
				if ($qry->b_error == true) {
						$str_message = "error inserting into ".Monthalize('stock_transfer')." :: ".$str_insert;
						$bool_success = false;
				}
					
				if ($bool_success == true)
					$qry->Query("COMMIT");
				else
					$qry->Query("ROLLBACK");
	
			} // end of if (can_save)
		} // end of action == save
	} // end of IsSet($_GET["action"]))

	// list of tax categories
	$result_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);
	
?>

<html>
<head>
	<title>Direct receive</title>
	<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
</head>
<body leftmargin=5 topmargin=5 marginwidth=7 marginheight=7>
<form name="direct_receive" method="POST" onsubmit="return false">

<? if ($str_message <> '')  { ?>
	<script language='javascript'>
	alert('<?echo $str_message?>');
	</script>
<? } ?>

  <input type="hidden" name="hidden_bprice" value="0">
  <input type="hidden" name="hidden_sprice" value="0">
  <input type="hidden" name="hidden_tax" value="0">

	
	<table width="98%" height="30" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td align="right" width='150px' class="<?echo $str_class_header?>">Code</td>
			<td width='550px'><input type="text" class="<?echo $str_class_input?>" id="code" name="code" value="" autocomplete="OFF" onblur="javascript:getDescription(this)" onkeypress="focusNext(this, 'received', event)">&nbsp;<a href="javascript:openSearch()"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		<tr>
		  <td>&nbsp;</td>
			<td class="<?echo $str_class_span?>" width="200px" id="description">&nbsp;</td>
		</tr>
		<tr>
		  <td>&nbsp;</td>
			<td class="<?echo $str_class_span?>" id="current_stock">&nbsp;</td>
		</tr>
		<tr>
		  <td>&nbsp;</td>
			<td class="<?echo $str_class_span?>" id="adjusted_stock">&nbsp;</td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Batch</td>
		  <td><input type="text" disabled="true" class="<?echo $str_class_input?>" name="batch" value="" autocomplete="OFF" onkeypress="focusNext(this, 'received', event)"></td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Received</td>
		  <td><input type="text" class="<?echo $str_class_input?>" name="received" value="" autocomplete="OFF" onkeypress="return focusNext(this, 'list_rcvd_day', event)"></td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Date Received</td>
		  <td><select name="list_rcvd_day" onkeypress="focusNext(this, 'b_price', event)" onblur="setMnfrDay()">
			<?
				for ($i=1; $i<=date('d',time()); $i++) {
					if ($i == date('j'))
						echo "<option value=".$i." selected=\"selected\">".$i;
					else
						echo "<option value=".$i.">".$i;
				}
		    ?>
		    </select>
			<font class="<?echo $str_class_header?>">
			<? echo getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]."&nbsp;"; ?>
			</font>
      		</td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Buying Price</td>
		  <td><input type="text" class="<?echo $str_class_input?>" name="b_price" <?if ($_SESSION['str_user_can_change_price'] == 'N') echo "disabled";?> value="" autocomplete="OFF" onkeypress="focusNext(this, 's_price', event)" onblur="setSPrice(<?echo "'".$qry_settings->FieldByName('stock_is_equal_prices')."',".$_SESSION["drcve_margin_percent"]?>)"></td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Selling Price</td>
		  <td><input type="text" class="<?echo $str_class_input?>" name="s_price" <?if ($_SESSION['str_user_can_change_price'] == 'N') echo "disabled";?> value="" onblur='getPriceWithTax()' autocomplete="OFF" onkeypress="focusNext(this, 'list_tax', event)"> <input type="checkbox" name="adjust_tax"><font class="headertext"> price already includes tax</font></td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Tax</td>
		  <td><select name="list_tax" <?if ($_SESSION['str_user_can_change_price'] == 'N') echo "disabled";?> onkeypress="focusNext(this, 'list_mnfr_day', event)">
		    <? 
		      for ($i=0; $i<$result_tax->RowCount(); $i++) {
		        echo "<option value=".$result_tax->FieldByName('tax_id').">".$result_tax->FieldByName('tax_description');
		        $result_tax->Next();
		      }
		?>
		</select>&nbsp;<span id='tax_details'></span>
		</td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Date of Mnfr</td>
		  <td><select name="list_mnfr_day" onkeypress="focusNext(this, 'list_mnfr_month', event)">
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
		    <select name="list_mnfr_month" onkeypress="focusNext(this, 'list_mnfr_year', event)">
		    <?
		      for ($i=1;$i<=12;$i++) {
		        if ($i == date('n'))
		          echo "<option value=".$i." selected=\"selected\">".$i;
		        else
		          echo "<option value=".$i.">".$i;
		      }
		    ?>
		    </select>
		    <select name="list_mnfr_year" onkeypress="focusNext(this, 'list_supplier', event)">
		    <?
		      $int_start = $_SESSION["int_year_loaded"]-5;
		      $int_end = $int_start + 10;
		      for ($i=$int_start;$i<=$int_end;$i++) {
		        if ($i == date('Y'))
		          echo "<option value=".$i." selected=\"selected\">".$i;
		        else
		          echo "<option value=".$i.">".$i;
		      }
		    ?>
		    </select>
      </td>
		</tr>
		<tr>
		  <td align="right" class="<?echo $str_class_header?>">Supplier</td>
		  <td><select name="list_supplier" onkeypress="focusNext(this, 'button_save', event)">
		    </select>
			</td>
		</tr>
<!--		<tr>
		  <td>&nbsp;</td>
		  <td><input type="checkbox" name="adjust_stock" checked disabled="true"><font class="headertext">deduct adjusted stock</font></td>
		</tr>-->
		<tr>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
		<tr>
		  <td align="right">
		    <input type="hidden" name="action" value="save">
		    <? if ($int_access_level > ACCESS_READ) { ?>
	       <input type="button" class="v3button" id="btn-save" name="Save" value="Save" onclick="receiveStock()">
	      <? } else { ?>
	       &nbsp;
	      <? } ?>
	    </td>
	     <td>
      <input type="button" class="v3button" name="Close" value="Close" onclick="CloseWindow()">
	</td>
	</tr>
	<tr>
		<td align='right'>
		<font class="headertext">[<b>F2</b> Save]</font>
	</td>
	</tr>
	</table>
</form>

<script language="javascript">
	document.direct_receive.code.focus();
</script>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../../include/js/jquery-3.2.1.min.js"></script>

   <script>

		$(document).ready(function(){

			$("body").keydown(function(e){

				var keyCode = e.keyCode || e.which || e.key;

				// F2
				if (keyCode == 113) {

 					e.preventDefault();

 					$(" #btn-save ").trigger( "click" );
				}

				else if (keyCode == 27) {

 					e.preventDefault();

					$(" #code ").select();
					clearValues();

				}

			});

		});

	</script>


</body>
</html>