<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$qry_settings = new Query("
		SELECT *
		FROM user_settings
	");
	$int_decimals = 2;
	$int_default_discount = 10;
	$str_batches_enabled = 'N';
	$str_display_messages = 'N';
	if ($qry_settings->RowCount() > 0) {
		$int_decimals = $qry_settings->FieldByName('bill_decimal_places');
		$int_default_discount = $qry_settings->FieldByName('bill_default_discount');
		$str_batches_enabled = $qry_settings->FieldByName('bill_enable_batches');
		$str_display_messages = $qry_settings->FieldByName('bill_display_messages');
	}
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
<script language="javascript">

	var ver    = parseFloat (navigator.appVersion.slice(0,4));
	var verIE  = (navigator.appName == "Microsoft Internet Explorer" ? ver : 0.0);
	var verNS  = (navigator.appName == "Netscape" ? ver : 0.0);
	var verOP  = (navigator.appName == "Opera"    ? ver : 0.0);
	var verOld = (verIE < 4.0 && verNS < 5.0);
	var isMSIE = (verIE >= 4.0);

	var arr_retval = new Array();
	var intDiscount = 0;
	var bool_is_decimal = false;
	var int_decimals = <?echo $int_decimals;?>;
	var str_batches_enabled = '<? echo $str_batches_enabled; ?>';
	var str_display_messages = '<? echo $str_display_messages; ?>';
	var flt_previous_qty = 0;
	var can_bill = false;
	var flt_total_batch_quantity = 0;
	var arr_batches = new Array();
	var arr_batch_codes = new Array();
	var arr_batch_ids = new Array();
	var arr_batch_quantities = new Array();
	var arr_batch_prices = new Array();
	var save_clicked = false;

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
	var requester3 = createRequest();
	var requester4 = createRequest();
	
	var requester_completed = false;
	var requester2_completed = false;
	var requester3_completed = false;
	var requester4_completed = false;

	function setCompletedFalse() {
		requester_completed = false;
		requester2_completed = false;
		requester3_completed = false;
		requester4_completed = false;
	}

// RETURNS THE DESCRIPTION OF THE CODE ENTERED
function stateHandler() {
	if (requester.readyState == 4) {
		if (requester.status == 200)  {
			oTextBoxCode = document.billing_enter.code;
			oTextBoxDescription = document.getElementById('description');
			oTextBoxUnit = document.getElementById('measurement_unit');
			
			str_retval = requester.responseText;
			arr_retval = str_retval.split('|');
			
			requester_completed = true;
				
			if (arr_retval[0] == '__NOT_FOUND') {
				can_bill = false;
				oTextBoxDescription.innerHTML = '';
				oTextBoxCode.value = "";
				if (str_display_messages == 'Y')
					alert('Code not found');
				oTextBoxCode.focus();
			}
			else if (arr_retval[0] == "__NOT_AVAILABLE") {
				can_bill = false;
				alert('This product cannot be billed.\n It has been disabled');
				oTextBoxDescription.innnerHTML = arr_retval[0]+' '+arr_retval[3];
				oTextBoxCode.value = "";
				oTextBoxCode.focus();
			}
			else {
				can_bill = true;
				oTextBoxDescription.innerHTML = arr_retval[0]+' '+arr_retval[3];
				oTextBoxUnit.innerHTML = arr_retval[1];
				if (arr_retval[2] == 'Y')
					bool_is_decimal = true;
				else
					bool_is_decimal = false;
				
				getBatches(oTextBoxCode);
			}
		}
		else {
			alert("failed to get description... please try again.");
		}
		requester = null;
		requester = createRequest();
	}
}

    // RETURNS THE BATCHES FOR THE GIVEN PRODUCT CODE
    function stateHandler2() {
	if (requester2.readyState == 4) {
	    if (requester2.status == 200)  {
		oTextBoxCode = document.billing_enter.code;
		if (str_batches_enabled == 'Y')
		    oListBoxBatches = document.billing_enter.listBatches;
		oTextBoxDescription = document.getElementById('description');
		oTextBoxQty = document.billing_enter.qty;
		
		if (str_batches_enabled == 'Y')
		    oListBoxBatches.options.length = 0;
		oSpanPrice = document.getElementById('price');
		oSpan = document.getElementById('available');
		
		flt_total_batch_quantity = 0;
		
		str_retval = requester2.responseText;
		
		requester2_completed = true;
		
		if (str_retval == 'nil') {
		    oSpan.innerHTML = '';
		    oTextBoxDescription.innerHTML = 'NO BATCHES FOR GIVEN PRODUCT';
		}
		else {
		    // the return value is & delimited for batch code and quantity
		    // and | delimited per batch
		    arr_batches.length = 0;
		    arr_batch_codes.length = 0;
		    arr_batch_quantities.length = 0;
		    arr_batch_prices.length = 0;
		    arr_batch_ids.length = 0;
		    
		    arr_batches = str_retval.split('|');
		    
		    for (i=0; i<arr_batches.length; i++) {
			arr_temp = arr_batches[i].split('&');
			arr_batch_codes[i] = arr_temp[0];
			arr_batch_ids[i] = arr_temp[3];
			arr_batch_quantities[i] = arr_temp[1];
			arr_batch_prices[i] = arr_temp[2];
			
			flt_total_batch_quantity += parseFloat(arr_temp[1]);
			
			if (str_batches_enabled == 'Y')
			    oListBoxBatches.options[i] = new Option(arr_batch_codes[i], arr_batch_ids[i]);
		    }
		    
		    oSpanPrice.innerHTML = 'Rs.' + arr_batch_prices[0];
		    
		    if (str_batches_enabled == 'Y') {
			if (bool_is_decimal) {
			    tmp_num = parseFloat(arr_batch_quantities[0]);
			    oSpan.innerHTML = tmp_num.toFixed(int_decimals);
			}
			else {
			    tmp_num = parseInt(arr_batch_quantities[0]);
			    oSpan.innerHTML = tmp_num.toFixed(0);
			}
		    }
		    else {
			updateBatch();
			oSpanPrice.innerHTML = 'Rs.' + arr_batch_prices[0];
			
			if (bool_is_decimal) {
			    oSpan.innerHTML = flt_total_batch_quantity.toFixed(int_decimals);
			}
			else {
			    oSpan.innerHTML = flt_total_batch_quantity.toFixed(0);
			}
		    }
		}
	    }
	    else {
		alert("failed to load batches... please try again.");
	    }
	    requester2 = null;
	    requester2 = createRequest();
	}
    }

    String.prototype.trim = function() {
	var reExtraSpace = /^\s+(.*?)\s+$/;
	return this.replace(reExtraSpace, "$1");
    }

// REMOVES AN ITEM + BATCH IF ALREADY IN THE LIST
function stateHandler3() {
	if (requester3.readyState == 4) {
	    if (requester3.status == 200)  {
			var oTextBoxCode = document.billing_enter.code;
			var oTextBoxQty = document.billing_enter.qty;
			var oTextHeader = document.getElementById('header_qty');
			
			str_retval = requester3.responseText;
			arr_retval = str_retval.split('|');
			
			requester3_completed = true;
			
			if (arr_retval[0] != 'nil') {
				oTextHeader.innerHTML = 'Quantity ('+arr_retval[1]+')';
				flt_previous_qty = arr_retval[1];
				parent.frames["frame_list"].document.location="billing_list.php?del=Y";
			}
			else {
				oTextBoxQty.value = '1';
				flt_previous_qty = 0;
			}
			
			oTextBoxQty.select();
		}
		else {
			alert("failed to update list... please try again.");
		}
		requester3 = null;
		requester3 = createRequest();
	}
}

function stateHandler4() {
	if (requester4.readyState == 4) {
	    if (requester4.status == 200)  {
			oTextBoxQty = document.billing_enter.qty;
			
			str_retval = requester4.responseText;
			
			requester4_completed = true;
			
			if (str_retval != 'nil') {
				oTextBoxQty.value = str_retval;
				updateList();
			}
			else {
				oTextBoxQty.value = '1';
				flt_previous_qty = 0;
			}
			
			oTextBoxCode.select();
		}
		else {
			alert("failed to update list... please try again.");
	    }
	    requester4 = null;
	    requester4 = createRequest();
	}
}

function openSearch() {
	myWin = window.open("../common/product_search.php?formname=billing_enter&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
	myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 600/2));
	myWin.focus();
}

//====================================================
// common/product_description.php
//
// returns the product description, measurement unit, is decimal and supplier abbreviation.
// and saves the following session variables:
//				$_SESSION["current_product_id"]
//				$_SESSION["current_code"]
//				$_SESSION["current_description"]
//----------------------------------------------------
function getDescription(strProductCode) {
	var strPassValue = '';
	if (strProductCode.value != '') {
		strPassValue = strProductCode;
		requester.onreadystatechange = stateHandler;
		strPassValue = strProductCode.value;
		if (strPassValue.length == 13) 
			requester.open("GET", "../common/product_description.php?live=1&product_code="+strPassValue+"&is_bar_code=Y");
		else
			requester.open("GET", "../common/product_description.php?live=1&product_code="+strPassValue+"&is_bar_code=N");
		requester.send(null);
	}
}

//====================================================
// common/product_batches.php
//
// saves the batch codes and corresponding quantities in the session array arr_item_batches
// for the product code that is passed
//				$_SESSION["arr_item_batches"][$int_counter][0] = batch code
//				$_SESSION["arr_item_batches"][$int_counter][1] = quantity
//				$_SESSION["arr_item_batches"][$int_counter][2] = batch_id
//----------------------------------------------------
function getBatches(strProductCode) {
	requester2.onreadystatechange = stateHandler2;
	var strPassValue = '';
	if (strProductCode.value == '')
	    strPassValue = 'nil'
	else
	    strPassValue = strProductCode.value;
	
	if (strPassValue.length == 13)
		requester2.open("GET", "../common/product_batches.php?live=1&product_code="+strPassValue+"&is_bar_code=Y");
	else
		requester2.open("GET", "../common/product_batches.php?live=1&product_code="+strPassValue+"&is_bar_code=N");
	
	requester2.send(null);
}

//====================================================
// common/update_batch.php
//
// iterates through the session array $_SESSION['arr_total_qty']
// and returns, if found,
//		the index where the item was found
//		the quantity of the found item
//----------------------------------------------------
function updateBatch() {
	var oTextBoxCode = document.billing_enter.code;
	var strPassValue = '';
	
	if (str_batches_enabled == 'Y')
	    var oTextBoxBatches = document.billing_enter.listBatches;
	
	strPassValue = oTextBoxCode.value;
	
	var strBatchCode = '';
	if (arr_batches.length > 0) {
	    if (str_batches_enabled == 'Y')
			strBatchCode = oTextBoxBatches.options[oTextBoxBatches.options.selectedIndex].value;
	    else
			strBatchCode = arr_batch_codes[0];
		
	    requester3.onreadystatechange = stateHandler3;
	    if (strPassValue.length == 13)
			requester3.open("GET", "../common/update_batch.php?live=1&product_code="+strPassValue+"&batch_code="+strBatchCode+"&is_bar_code=Y");
	    else
			requester3.open("GET", "../common/update_batch.php?live=1&product_code="+strPassValue+"&batch_code="+strBatchCode+"&is_bar_code=N");
	    requester3.send(null);
	}
}

function setBatchQty() {
	if (str_batches_enabled == 'Y')
	    var oTextBoxBatches = document.billing_enter.listBatches;
	var oSpanPrice = document.getElementById('price');
	var oSpan = document.getElementById('available');
	
	if (arr_batches.length > 0) {
	    if (str_batches_enabled == 'Y') {
			if (bool_is_decimal) {
				tmp_num = parseFloat(arr_batch_quantities[oTextBoxBatches.options.selectedIndex]);
				oSpan.innerHTML = tmp_num.toFixed(int_decimals);
			}
			else {
				tmp_num = parseInt(arr_batch_quantities[oTextBoxBatches.options.selectedIndex]);
				oSpan.innerHTML = tmp_num.toFixed(0);
			}
	    }
	    else {
			if (bool_is_decimal) {
				tmp_num = parseFloat(flt_total_batch_quantity);
				oSpan.innerHTML = tmp_num.toFixed(int_decimals);
			}
			else {
				tmp_num = parseInt(flt_total_batch_quantity);
				oSpan.innerHTML = tmp_num.toFixed(0);
			}
	    }
	}
}

function updateList() {
	var oTextBoxCode = document.billing_enter.code;
	if (str_batches_enabled == 'Y')
	    var oTextBoxBatches = document.billing_enter.listBatches;
	var oTextBoxDescription = document.getElementById('description');
	var oTextBoxQty = document.billing_enter.qty;
	
	if (can_bill == true) {
	    if (str_batches_enabled == 'Y')
			int_index = oTextBoxBatches.options.selectedIndex;
	    else
			int_index = 0;
		
	    if (intDiscount > 0)
			parent.frames["frame_list"].document.location = "billing_list.php?code=" + oTextBoxCode.value +
				"&set_discount=Y" +
				"&discount=" + intDiscount +
				"&batch_code=" + arr_batch_codes[int_index];
	    else
			parent.frames["frame_list"].document.location = "billing_list.php?code=" + oTextBoxCode.value +
				"&set_discount=N" + 
				"&discount=0" +
				"&batch_code=" + arr_batch_codes[int_index];
	}
	
	clearValues();
}

//====================================================
// common/product_quantities.php
//
//	This function loads all the details of a 
//	product based on the product code, batch code
//	and billed quantity into the session array
//	arr_total_qty.	
// 
//	This function uses the arr_item_batches session array
//	which lists the product's batch details
//----------------------------------------------------
function checkQty(aValue) {
	var oTextBoxCode = document.billing_enter.code;
	if (str_batches_enabled == 'Y')
	    var oTextBoxBatches = document.billing_enter.listBatches;
	var oTextBoxQty = document.billing_enter.qty;
	var flt_pass_qty;
	
	if (oTextBoxQty.value <= 0) {
	    alert('Quantity must be greater than zero');
	    can_bill = false;
	}

	if ((requester_completed == false) || (requester2_completed == false) || (requester3_completed == false)) {
		setTimeout("checkQty("+aValue+")", 1000);
		return;
	}

	if (can_bill == true) {
		var strPassValue = '';
		var is_bar_code = 'N';
		
		if ((oTextBoxCode.value == '') || (aValue == '0') || (aValue == ''))
			strPassValue = 'nil'
		else
			strPassValue = oTextBoxCode.value;
			
		flt_pass_qty = parseFloat(aValue) + parseFloat(flt_previous_qty);
		
		if (str_display_messages == 'Y') {
			if (flt_pass_qty == flt_total_batch_quantity) {
				alert('The current stock of this product is now zero');
			}
			else if (flt_pass_qty > flt_total_batch_quantity) {
				alert('The current stock of this product is now negative');
			}
		}
		
		if (strPassValue.length == 13)
			is_bar_code = 'Y';
		
		if (arr_batches.length > 0) {
			requester4.onreadystatechange = stateHandler4;
			if (str_batches_enabled == 'Y') {
				requester4.open("GET", "../common/product_quantities.php?live=1" +
					"&product_code=" + strPassValue +
					"&batch_code=" + arr_batch_codes[oTextBoxBatches.options.selectedIndex] +
					"&qty=" + flt_pass_qty +
					"&is_bar_code=" + is_bar_code +
					"&batch_id=" + arr_batch_ids[oTextBoxBatches.options.selectedIndex]);
			}
			else {
				requester4.open("GET", "../common/product_quantities.php?live=1" +
					"&product_code=" + strPassValue +
					"&batch_code=" + arr_batch_codes[0] +
					"&qty=" + flt_pass_qty +
					"&is_bar_code=" + is_bar_code +
					"&batch_id=" + arr_batch_ids[0]);
			}
			requester4.send(null);
		}
	}
}

function focusNext(aField, focusElem, evt) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	
	var oTextBoxCode = document.getElementById('code');
	if (str_batches_enabled == 'Y')
	    var oListBoxBatches = document.billing_enter.listBatches;
	var oTextBoxQty = document.billing_enter.qty;
	var oTextHeader = document.getElementById('header_qty');
	
	if (charCode == 9 || charCode == 3) {
	    if (focusElem == 'code') {
			if (arr_batches.length > 0) {
				checkQty(aField.value);
				if (can_bill == true) {
					oTextBoxCode.focus();
				}
				else {
					oTextBoxQty.select();
					can_bill = true;
				}
			}
	    }
	    else if (focusElem == 'listbatches') {
			oListBoxBatches.focus();
	    }
	    else if (focusElem == 'qty') {
			oTextBoxQty.focus();
			oTextBoxQty.select();
	    } 
	} else if (charCode == 13 || charCode == 3 || charCode == 9) {
	    if (focusElem == 'code') {
			if (arr_batches.length > 0) {
				checkQty(aField.value);
				if (can_bill == true) {
					oTextBoxCode.focus();
				}
				else {
					oTextBoxQty.select();
					can_bill = true;
				}
			}
	    }
	    else if (focusElem == 'listbatches')
			oListBoxBatches.focus();
	    else if (focusElem == 'qty') {
			oTextBoxQty.focus();
			oTextBoxQty.select();
	    }
	} else if (charCode == 27) {
	    if (flt_previous_qty > 0) {
			oTextHeader.innerHTML = 'Quantity';
			flt_previous_qty = 0;
	    }
	    else {
			oTextBoxCode.focus();
			clearValues();
	    }
	} else if (charCode == 46) { // full stop dissallowed for non-decimal unit of measurement
	    if (focusElem == 'code') {
			if (bool_is_decimal == false)
				return false;
	    }
	}
	
	return true;
}

function doKeyDown(evt) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	
	if (verOld) return true;
	
	var oTextBoxCode = document.getElementById('code');
	var oTextBoxQty = document.billing_enter.qty;
	var oList = parent.frames["frame_list"].document.billing_list.item_list;
	
	if (charCode == 113) { // F2 Save 
		if (oList.options.length > 0) {
			if (save_clicked == false) {
				var oButtonSave = parent.frames["frame_action"].document.bill_action.button_save;
				save_clicked = true;
				oButtonSave.click();
			}
			else
				alert('This bill has been saved already');
		}
		else
			alert('no items listed to save');
	}
	else if (charCode == 121) { // F10 Exit 
	    if (oList.options.length > 0) {
		if (confirm('Items have been billed. \n Exit anyway?')) {
		    var oButtonClose = parent.frames["frame_action"].document.bill_action.button_close;
		    oButtonClose.click();
		}
	    }
	    else {
		var oButtonClose = parent.frames["frame_action"].document.bill_action.button_close;
		oButtonClose.click();
	    }
	}
	else if (charCode == 117) { // F6 Discount
	    if (oTextBoxCode.value != "") {
			intDiscount = prompt('Enter discount','<?echo $int_default_discount;?>');
			if (intDiscount != null) {
				if (isNaN(intDiscount)) {
					alert('Please enter a valid number');
				}
			}
	    }
	    oTextBoxQty.focus();
		return false;
	}
	
	return true;
} // doKeyDown

function clearValues() {
	var oTextBoxCode = document.getElementById('code');
	if (str_batches_enabled == 'Y')
	    var oTextBoxBatches = document.billing_enter.listBatches;
	var oTextBoxDescription = document.getElementById('description');
	var oTextBoxUnit = document.getElementById('measurement_unit');
	var oTextBoxQty = document.billing_enter.qty;
	var oSpanPrice = document.getElementById('price');
	var oSpan = document.getElementById('available');
	var oTextHeader = document.getElementById('header_qty');
	
	if (str_batches_enabled == 'Y')
	    oTextBoxBatches.options.length = 0;
	oTextBoxDescription.innerHTML = '';
	oTextBoxUnit.innerHTML = '&nbsp;';
	oTextBoxQty.value = '1';
	oSpanPrice.innerHTML = '';
	oSpan.innerHTML = '&nbsp;';
	intDiscount = 0;
	flt_total_batch_quantity = 0;
	oTextBoxCode.value = '';
	oTextHeader.innerHTML = 'Quantity';
}

</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7 onKeyDown="return doKeyDown(event);">
<form name="billing_enter" method="GET" onsubmit="return false">

	<table width="600" height="30" border="0" cellpadding="0" cellspacing="5">
		<tr class="<?echo $str_class_header?>">
			<td>Code</td>
			<td>&nbsp;</td>
			<? if ($str_batches_enabled == 'Y') { ?>
			    <td>Batch</td>
			<? } ?>
			<td>Description<img src="../images/blank.gif" width="350px" height="1px"></td>
			<td id='header_qty'>Quantity</td>
			<td><img src="../images/blank.gif" width="80px" height="1px"></td>
			<td><img src="../images/blank.gif" width="80px" height="1px"></td>
			<td><img src="../images/blank.gif" width="30px" height="1px"></td>
		</tr>
		<tr>
			<!-- CODE -->
            <? if ($str_batches_enabled == 'Y') { ?>
			    <td><input type="text" id="code" name="code" value="" autocomplete="OFF" class="<?echo $str_class_input?>" onkeypress="focusNext(this, 'listbatches', event)" onblur="javascript:getDescription(this);" onfocus="setCompletedFalse()"></td>
			<? } else { ?>
			    <td><input type="text" id="code" name="code" value="" autocomplete="OFF" class="<?echo $str_class_input?>" onkeypress="focusNext(this, 'qty', event)" onblur="javascript:getDescription(this);" onfocus="setCompletedFalse()"></td>
			<? } ?>

			<!-- SEARCH BUTTON FOR CODE -->
			<td><a href="javascript:openSearch()"><img src="../images/find.png" border="0" title="Search" alt="Search"></a></td>

			<!-- LIST OF BATCHES -->
                        <? if ($str_batches_enabled == 'Y') { ?>
			    <td>
				<select name="listBatches" class="<?echo $str_class_select?>" onchange="javascript:setBatchQty()" onblur="javascript:updateBatch()" onkeypress="focusNext(this, 'qty', event)">
				</select>
			    </td>
			<? } ?>

			<!-- DESCRIPTION -->
			<td class="<?echo $str_class_span?>"><span id="description" class="<?echo $str_class_span?>" style='width:500px'>&nbsp;</span></td>

			<!-- QTY -->
			<td><input type="text" name="qty" value="1" class="<?echo $str_class_input?>" autocomplete="OFF" onkeypress="return focusNext(this, 'code', event)" onfocus="setBatchQty()"></td>

			<!-- PRICE -->
                        <td align='right' class='<?echo $str_class_span?>'><span id ='price' class='<?echo $str_class_header?>'>&nbsp;</span></td>
			
			<!-- AVAILABLE QUANTITY -->
			<td align="right" class='<?echo $str_class_span?>'><span id="available" class="<?echo $str_class_header?>">&nbsp;</span></td>

			<!-- MEASUREMENT UNIT -->
			<td align="left" class='<?echo $str_class_span?>'><span id="measurement_unit" class="<?echo $str_class_header?>">&nbsp;</span></td>

			<!-- DISCOUNT 
			<td><input type="text" name="discount" value="0" autocomplete="OFF" class="inputbox60" onkeypress="focusNext(this, 'code', event)"></td> -->
		</tr>
	</table>
</form>
</body>
</html>