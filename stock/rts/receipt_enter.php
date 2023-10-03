<?
    require_once("../../include/const.inc.php");
    require_once("../../include/session.inc.php");
    require_once("../../include/db.inc.php");


?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />


<script language="javascript">

	var arr_retval = new Array();

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

	var can_bill = false;
	var requester = createRequest();
	var requester2 = createRequest();
	var requester3 = createRequest();
	var requester4 = createRequest();

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				oTextBoxCode = document.receipt_enter.code;
				oListBoxBatches = document.receipt_enter.listBatches;
				oTextBoxDescription = document.getElementById('description');
				oTextBoxUnit = document.getElementById('measurement_unit');
				oTextBoxQty = document.receipt_enter.qty;

				str_retval = requester.responseText;
				arr_retval = str_retval.split('|');

				if (arr_retval[0] == '__NOT_FOUND') {
					can_bill = false;
					oTextBoxCode.value = "";
				}
				else if (arr_retval[0] == '__NOT_AVAILABLE') {
					if (confirm('This product has been disabled.\n Do you want to return it?'))
						can_bill = false;
					else {
						can_bill = true;
						oTextBoxDescription.innerHTML = arr_retval[0];
						oTextBoxUnit.innerHTML = arr_retval[1];
						getBatches(oTextBoxCode);
					}
				}
				else {
					can_bill = true;
					oTextBoxDescription.innerHTML = arr_retval[0];
					oTextBoxUnit.innerHTML = arr_retval[1];
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
	try {
		if (requester2.readyState == 4) {
			if (requester2.status == 200 || requester2.status == 0)  {
				oTextBoxCode = document.receipt_enter.code;
				oListBoxBatches = document.receipt_enter.listBatches;
				oTextBoxDescription = document.getElementById('description');
				oTextBoxQty = document.receipt_enter.qty;

				oListBoxBatches.options.length = 0;
				oSpan = document.getElementById('available');

				str_retval = requester2.responseText;

				if (str_retval == 'nil') {
					oSpan.innerHTML = '';
					oTextBoxDescription.innerHTML = 'NO BATCHES FOR GIVEN PRODUCT';
				}
				else {
					// the return value is & delimited for batch code and quantity
					// and | delimited per batch
					arr_batches = str_retval.split('|');
					arr_batch_codes = new Array();
					arr_batch_quantities = new Array();
					arr_batch_taxes = new Array();

					for (i=0; i<arr_batches.length; i++) {
						arr_temp = arr_batches[i].split('&');
						arr_batch_codes[i] = arr_temp[0];
						arr_batch_quantities[i] = arr_temp[1];
						arr_batch_taxes[i] = arr_temp[2];
						
						if (arr_temp[3]=='PO')
							oListBoxBatches.options[i] = new Option(arr_batch_codes[i] + ' | Inv ' + arr_temp[4] + ', Dt '+arr_temp[5] + ', Rcvd ' + arr_temp[6], arr_batch_codes[i]);
						else
							oListBoxBatches.options[i] = new Option( arr_batch_codes[i], arr_batch_codes[i] );
					}

					oSpan.innerHTML = arr_batch_quantities[0]+"<br>"+arr_batch_taxes[0];
				}
			}
			else {
				alert("failed to load batches... please try again.");
			}
			requester2 = null;
			requester2 = createRequest();
		}
	}
	catch (e) {
		alert('ERROR : ' + e.message);
	}	
	}

	// REMOVES AN ITEM+BATCH IF ALREADY IN THE LIST
	function stateHandler3() {
		if (requester3.readyState == 4) {
			if (requester3.status == 200)  {
				oTextBoxCode = document.receipt_enter.code;
				oTextBoxQty = document.receipt_enter.qty;

				str_retval = requester3.responseText;
				arr_retval = str_retval.split('|');

				if (arr_retval[0] != 'nil') {
					oTextBoxQty.value = arr_retval[1];
					parent.frames["frame_list"].document.location="receipt_list.php?del=Y&atIndex="+arr_retval[0]+"&del_qty="+arr_retval[1];
				}
				else
					oTextBoxQty.value = '1';

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
				oTextBoxDiscount = document.receipt_enter.discount;
				oTextBoxQty = document.receipt_enter.qty;

				str_retval = requester4.responseText;

				if (str_retval != 'nil') {
					oTextBoxQty.value = str_retval;
					updateList();
				}
				else {
					oTextBoxQty.value = '1';
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

	function getDescription(strProductCode) {
		requester.onreadystatechange = stateHandler;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester.open("GET", "product_description.php?live=1&product_code="+strPassValue);
		requester.send('');
	}

	function getBatches(strProductCode) {
		requester2.onreadystatechange = stateHandler2;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester2.open("GET", "product_batches.php?live=1&product_code="+strPassValue);
		requester2.send('');
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.getElementById('code');
		var oListBoxBatches = document.receipt_enter.listBatches;
		var oTextBoxQty = document.receipt_enter.qty;
	
		if (charCode == 9 || charCode == 3) {
			if (focusElem == 'code') {
				if (oListBoxBatches.options.length > 0) {
					checkQty(aField);
					if (can_bill == true) {
						oTextBoxCode.focus();
					}
				}
			} else if (focusElem == 'discount') {
				if (oListBoxBatches.options.length > 0)
					checkQty(aField);
			}
		} else if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'code') {
				if (oListBoxBatches.options.length > 0) {
					checkQty(aField);
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
			else if (focusElem == 'discount') {
				checkQty(aField);
				oTextBoxDiscount.focus();
				oTextBoxDiscount.select();
			}
		} else if (charCode == 27) {
			oTextBoxCode.focus();
			clearValues();
		}

		return false;
	}

	function updateBatch() {
		var oTextBoxCode = document.receipt_enter.code;
		var oTextBoxBatches = document.receipt_enter.listBatches;

		var strBatchCode = '';
		if (oTextBoxBatches.options.length > 0) {
			strBatchCode = oTextBoxBatches.options[oTextBoxBatches.options.selectedIndex].value;
	
			requester3.onreadystatechange = stateHandler3;
			requester3.open("GET", "update_batch.php?live=1&product_code=" + oTextBoxCode.value + "&batch_code=" + strBatchCode);
			requester3.send('');
		}
	}

	function setBatchQty() {
		var oTextBoxBatches = document.receipt_enter.listBatches;
		var oSpan = document.getElementById('available');

		if (oTextBoxBatches.options.length > 0) {
			oSpan.innerHTML = arr_batch_quantities[oTextBoxBatches.options.selectedIndex]+"<br>"+arr_batch_taxes[oTextBoxBatches.options.selectedIndex];
		}
	}

	function checkQty(aField) {
		var oTextBoxCode = document.receipt_enter.code;
		var oTextBoxBatches = document.receipt_enter.listBatches;

		if (can_bill == true) {
			var strPassValue = '';
			if ((oTextBoxCode.value == '') || (aField.value == '0') || (aField.value == ''))
				strPassValue = 'nil'
			else
				strPassValue = oTextBoxCode.value;
			if (oTextBoxBatches.options.length > 0) {
				if (Number(aField.value) > Number(arr_batch_quantities[oTextBoxBatches.options.selectedIndex])) {
				  alert('Quantity cannot be larger than available quantity');
				}
				else {
					requester4.onreadystatechange = stateHandler4;
					requester4.open("GET", "product_quantities.php?live=1&product_code=" + strPassValue + "&batch_code=" + arr_batch_codes[oTextBoxBatches.options.selectedIndex] + "&qty=" + aField.value);
					requester4.send('');
				}
			}
		}
	}

	function updateList() {
		var oTextBoxCode = document.receipt_enter.code;
		var oTextBoxBatches = document.receipt_enter.listBatches;
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxQty = document.receipt_enter.qty;

		if (can_bill == true) {
			parent.frames["frame_list"].document.location = "receipt_list.php?code=" + oTextBoxCode.value + "&batch_code=" + arr_batch_codes[oTextBoxBatches.options.selectedIndex];
		}

		clearValues();
	}

	function openSearch(int_supplier_id) {
		myWin = window.open("../../common/product_search.php?formname=receipt_enter&fieldname=code&supplier_id="+int_supplier_id,'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 600/2));
		myWin.focus();
	}

	function clearValues() {
		var oTextBoxCode = document.getElementById('code');
		var oTextBoxBatches = document.receipt_enter.listBatches;
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxUnit = document.getElementById('measurement_unit');
		var oTextBoxQty = document.receipt_enter.qty;
		var oSpan = document.getElementById('available');

		oTextBoxBatches.options.length = 0;
		oTextBoxDescription.innerHTML = '';
		oTextBoxUnit.innerHTML = '';
		oTextBoxQty.value = '1';
		oSpan.innerHTML = '';
		oTextBoxCode.value = '';
	}
</script>


<?
	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == "set_supplier") {
			$_SESSION["current_supplier_id"] = $_GET["supplier_id"];
			$_SESSION['current_bill_day'] = $_GET['receipt_day'];
		}
	}
?>


</head>
<body id='body_bgcolor' leftmargin=0 topmargin=4>
<form name="receipt_enter" method="GET" onsubmit="return false">

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr><td align='center'>
<?
	boundingBoxStart("750", "../../images/blank.gif");
?>

    <table width="600" height="30" border="0" cellpadding="2" cellspacing="0">
	<tr class="normaltext">
	    <td>Code</td>
	    <td>&nbsp;</td>
	    <td>Batch</td>
	    <td>Description<img src="../images/blank.gif" width="270px" height="1px"></td>
	    <td>Quantity</td>
	    <td><img src="../images/blank.gif" width="90px" height="1px"></td>
	    <td><img src="../images/blank.gif" width="35px" height="1px"></td>
	</tr>
	<tr>
	    <!-- CODE -->
	    <td><input type="text" id="code" name="code" value="" autocomplete="OFF" class="input_100" onkeypress="focusNext(this, 'listbatches', event)" onblur="javascript:getDescription(this);" <?php if (!is_null($_SESSION['stock_rts_id'])) echo "disabled"; ?>></td>
	    
	    <!-- SEARCH BUTTON FOR CODE -->
	    <td><a href="javascript:openSearch(<?echo $_SESSION["current_supplier_id"]?>)"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
	    
	    <!-- LIST OF BATCHES -->
	    <td><select name="listBatches" class="select_100" onchange="javascript:setBatchQty()" onblur="javascript:updateBatch()" onkeypress="focusNext(this, 'qty', event)" <?php if (!is_null($_SESSION['stock_rts_id'])) echo "disabled"; ?>>
		    </select>
	    </td>
	    
	    <!-- DESCRIPTION -->
	    <td><span id="description" class="spantext">&nbsp;</span></td>
	    
	    <!-- QTY checkQty(this) -->
	    <td><input type="text" name="qty" value="1" class="input_50" autocomplete="OFF" onkeypress="focusNext(this, 'code', event)" onfocus="setBatchQty()" <?php if (!is_null($_SESSION['stock_rts_id'])) echo "disabled"; ?>></td>
	    
	    <!-- AVAILABLE QUANTITY -->
	    <td align="right"><span id="available" class="spantext" style="font-weight: normal; padding: 0px;">&nbsp;</span></td>
	    
	    <!-- MEASUREMENT UNIT -->
	    <td align="left"><span id="measurement_unit" class="spantext">&nbsp;</span></td>
	</tr>
    </table>
	
<?
    boundingBoxEnd("750", "../../images/blank.gif");
?>
</td></tr>
</table>

</form>
</body>
</html>