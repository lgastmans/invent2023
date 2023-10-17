<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    //require_once('../common/tax.php');
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

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
	var requester_qty = createRequest();

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.purchase_order_enter.code;
				var oTextBoxQty = document.purchase_order_enter.qty;
				var oTextBoxDescription = document.getElementById('description');
				var oTextBoxUnit = document.getElementById('measurement_unit');
				var oTextBPrice = document.purchase_order_enter.bprice;
				var oTextSPrice = document.purchase_order_enter.sprice;
				var oTextBoxTax = document.getElementById('tax');
				
				str_retval = requester.responseText;
				arr_retval = str_retval.split('|');
				
				if (arr_retval[0] == '__NOT_FOUND') {
					can_bill = false;
					alert('Given code not found');
					oTextBoxCode.value = "";
					oTextBoxCode.focus();
				}
				else if (arr_retval[0] == '_ERROR') {
					can_bill = false;
					alert('Error retrieving product information');
					oTextBoxCode.value = "";
					oTextBoxCode.focus();
				}
				else {
					can_bill = true;
					
					oTextBoxDescription.innerHTML = arr_retval[0];
					oTextBoxUnit.innerHTML = arr_retval[1];
					oTextBoxTax.innerHTML = arr_retval[7];
					
					if (arr_retval[2] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
					
					if (arr_retval[3] > -1) {
						oTextBoxQty.value = arr_retval[4];
					}
					oTextBPrice.value = arr_retval[5];
					oTextSPrice.value = arr_retval[6];
				}
			}
			else {
				alert("failed to get description... please try again.");
			}
		requester = null;
		requester = createRequest();
		}
	}

	function stateHandler_qty() {
		if (requester_qty.readyState == 4) {
			if (requester_qty.status == 200)  {
				var oTextBoxCode = document.purchase_order_enter.code;
				var oTextBoxQty = document.purchase_order_enter.qty;
				
				str_retval = requester_qty.responseText;
				arrRetval = str_retval.split('|');
				
				if (str_retval != 'nil') {
					if (can_bill == true) {
						//appendRow(arrRetval[1]);
						
						//parent.frames["frame_enter"].document.location = "purchase_order_list.php";
						document.getElementById('frame_products').contentWindow.location.reload(true);

						parent.frames["frame_total"].document.location = "purchase_order_total.php";
					}
					clearValues();
				}
				else {
					oTextBoxQty.value = '1';
				}
				oTextBoxCode.select();
			}
			else {
				alert("failed to update list... please try again.");
			}
			requester_qty = null;
			requester_qty = createRequest();
		}
	}
	
	function appendRow(strProductID) {
		var oFrame = document.getElementById('frame_products');
		var oDoc = oFrame.contentWindow || oFrame.contentDocument;
		var oTable = oDoc.document.getElementById('tbl_products');
		var oTextCode = document.purchase_order_enter.code;
		var oTextQty = document.purchase_order_enter.qty;
		var oTextDescription = document.getElementById('description');
		var oTextBPrice = document.purchase_order_enter.bprice;
		var oTextSPrice = document.purchase_order_enter.sprice;
		var oTextTax = document.getElementById('tax');
		
		checkRow(strProductID);
		
		var newRow = oTable.insertRow(-1);
		var rowCounter = oTable.rows.length;
		newRow.setAttribute('id', strProductID);
		if (rowCounter % 2 == 0)
			strClass = 'even';
		else
			strClass = 'odd';
		newRow.className = strClass;
		
		// Code
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','70px');
		var currentText = oDoc.document.createTextNode(oTextCode.value);
		newCell.appendChild(currentText);
		
		// Description
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','250px');
		var currentText = oDoc.document.createTextNode(oTextDescription.innerHTML);
		newCell.appendChild(currentText);
		
		// B.Price
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','80px');
		newCell.setAttribute('align','right');
		var strVal = makeFloat(oTextBPrice.value);
		var currentText = oDoc.document.createTextNode(strVal);
		newCell.appendChild(currentText);
		
		// S. Price
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','80px');
		newCell.setAttribute('align','right');
		var strVal = makeFloat(oTextSPrice.value);
		var currentText = oDoc.document.createTextNode(strVal);
		newCell.appendChild(currentText);
		
		// Tax
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','60px');
		//var strVal = makeFloat(oTextTax.value);
		//var currentText = oDoc.document.createTextNode(oTextTax.innerHTML);
		var currentText = oDoc.document.createTextNode('tax');
		newCell.appendChild(currentText);

		// Taxable Value
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','60px');
		//var strVal = makeFloat(oTextTax.value);
		var currentText = oDoc.document.createTextNode('taxable');
		newCell.appendChild(currentText);
		
		// Qty
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','70px');
		newCell.setAttribute('align','right');
		var currentText = oDoc.document.createTextNode(oTextQty.value);
		newCell.appendChild(currentText);
		
		// Amount
		var newCell = newRow.insertCell(-1);
		newCell.setAttribute('width','80px');
		newCell.setAttribute('align','right');
		var fltAmount = parseFloat(oTextBPrice.value) * parseFloat(oTextQty.value);
		var currentText = oDoc.document.createTextNode(fltAmount.toFixed(2));
		newCell.appendChild(currentText);
	}

	function checkRow(strProductID) {
		var oFrame = document.getElementById('frame_products');
		var oDoc = oFrame.contentWindow || oFrame.contentDocument;
		var oTable = oDoc.document.getElementById('tbl_products');
		var numRows = oTable.rows.length;
		for (var i=0;i<numRows;i++) {
			var aRow = oTable.rows[i];
			if (strProductID == aRow.id) {
				oTable.deleteRow(aRow.rowIndex);
				updateRowClasses();
				break;
			}
		}
	}
	
	function updateRowClasses() {
		var oFrame = document.getElementById('frame_products');
		var oDoc = oFrame.contentWindow || oFrame.contentDocument;
		var oTable = oDoc.document.getElementById('tbl_products');
		var numRows = oTable.rows.length;
		
		for (var i = 0; i < numRows; i++) {
			var myRow = oTable.rows[i];
			if (i % 2 == 0)
				myRow.className = 'odd';
			else
				myRow.className = 'even';
		}
	}
	
	function getDescription(strProductCode) {
		var strPassValue = '';
		
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else {
			requester.onreadystatechange = stateHandler;
			strPassValue = strProductCode.value;
			requester.open("GET", "product_description.php?live=1&product_code="+strPassValue);
			requester.send(null);
		}
	}

	function getSPrice(strBPrice) {
		var oCode = document.getElementById('code');

		$.ajax({
			method: "POST",
			url: "get_sprice.php",
			//dataType: "text/xml",
			data : { 
				'bprice': strBPrice.value, 
				'code': oCode.value
			},
		})
		.done(function( msg ) {
			var obj = jQuery.parseJSON( msg );
			var oTextSPrice = document.purchase_order_enter.sprice;

			oTextSPrice.value = obj.sprice;
		})
		.fail(function() {
			alert( "Error fectching data." );
		})
		.always(function() {
			//
		});
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.purchase_order_enter.qty;
		var oTextBPrice = document.purchase_order_enter.bprice;
		var oTextSPrice = document.purchase_order_enter.sprice;

		if (charCode == 9 || charCode == 3) {
			if (focusElem == 'code') {
				checkQty(aField);
				if (can_bill == true) {
					oTextBoxCode.focus();
				}
				else {
					oTextBoxQty.select();
					can_bill = true;
				}
			}
			else if (focusElem == 'bprice') {
				oTextBPrice.focus();
				oTextBPrice.select();
			} 
			else if (focusElem == 'sprice') {
				oTextSPrice.focus();
				oTextSPrice.select();
			} 
			else if (focusElem == 'qty') {
				oTextBoxQty.focus();
				oTextBoxQty.select();
			} 
		} else if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'code') {
				checkQty(aField);
				if (can_bill == true) {
					oTextBoxCode.focus();
				}
				else {
					oTextBoxQty.select();
					can_bill = true;
				}
			}
			else if (focusElem == 'qty') {
				oTextBoxQty.focus();
				oTextBoxQty.select();
			}
			else if (focusElem == 'bprice') {
				oTextBPrice.focus();
				oTextBPrice.select();
			} 
			else if (focusElem == 'sprice') {
				oTextSPrice.focus();
				oTextSPrice.select();
			} 
		} else if (charCode == 27) {
			oTextBoxCode.focus();
			clearValues();
			
		} else if (charCode == 46) { // full stop dissallowed for non-decimal unit of measurement
			if (focusElem == 'code') {
				if (bool_is_decimal == false)
					return false;
			}
		}

		return true;
	}
	
	function clearValues() {
		var oTextBoxCode = document.getElementById('code');
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxUnit = document.getElementById('measurement_unit');
		var oTextBPrice = document.purchase_order_enter.bprice;
		var oTextSPrice = document.purchase_order_enter.sprice;
		var oTextBoxQty = document.purchase_order_enter.qty;
		var oTextTax = document.getElementById('tax');
		oTextBPrice.value = '0.0';
		oTextSPrice.value = '0.0';
		oTextBoxDescription.innerHTML = '';
		oTextBoxUnit.innerHTML = '';
		oTextBoxQty.value = '1';
		oTextBoxCode.value = '';
		oTextTax.value = '';
	}

	function trim(s) {
		return s.replace(/^\s+|\s+$/g, "");
	}
	
	function isFloat(s) {
		var n = trim(s);
		return n.length>0 && !(/[^0-9.]/).test(n) && (/\.\d/).test(n);
	}

	function makeFloat(s) {
		s = String(s);
		if (s.indexOf('.') < 0)
			s += '.0';
		return s;
	}
	
	function checkQty(aField) {
		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.purchase_order_enter.qty;
		var oTextBPrice = document.purchase_order_enter.bprice;
		var oTextSPrice = document.purchase_order_enter.sprice;
		
		strVal = makeFloat(aField.value);
		if (!isFloat(strVal)) {
			alert('Invalid quantity');
			can_bill = false;
			oTextBoxQty.focus();
		}
		
		strVal = makeFloat(oTextBPrice.value);
		if (!isFloat(strVal)) {
			alert('Invalid buying price');
			can_bill = false;
			oTextBPrice.focus();
		}

		strVal = makeFloat(oTextSPrice.value);
		if (!isFloat(strVal)) {
			alert('Invalid selling price');
			can_bill = false;
			oTextSPrice.focus();
		}
		
		if (aField.value <= 0) {
			alert('Quantity must be greater than zero');
			can_bill = false;
		}

		if (can_bill == true) {
			var strPassValue = '';
			if ((oTextBoxCode.value == '') || (aField.value == '0') || (aField.value == ''))
				strPassValue = 'nil'
			else {
				strPassValue = oTextBoxCode.value;
				var strURL = "live=1"+
					"&product_code=" + strPassValue +
					"&bprice=" + oTextBPrice.value +
					"&sprice=" + oTextSPrice.value +
					"&qty=" + aField.value;
				
				requester_qty.onreadystatechange = stateHandler_qty;
				requester_qty.open("GET", "order_quantities.php?"+strURL);
				requester_qty.send(null);
			}
		}
	}

	function openSearch() {
		myWin = window.open("../common/product_search.php?formname=purchase_order_enter&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 600/2));
		myWin.focus();
	}
	
</script>
</head>

<body id='body_bgcolor' marginwidth="10" marginheight="10">

<form name="purchase_order_enter" method="GET">

<table border='0' cellpadding='0' cellspacing='0' class="edit">
	<tr>
		<td align='center' valign="top">
			
			<table width="600" height="30" border="0" cellpadding="4" cellspacing="0">
				<tr class="normaltext_bold">
					<td>Code</td>
					<td>&nbsp;</td>
					<td>Description<img src="../images/blank.gif" width="325px" height="1px"></td>
					<td>B.Price</td>
					<td>S.Price</td>
					<td>Quantity</td>
					<td><img src="../images/blank.gif" width="30px" height="1px"></td>
					<td><img src="../images/blank.gif" width="35px" height="1px"></td>
					<td><img src="../images/blank.gif" width="35px" height="1px"></td>
				</tr>
				<tr>
					<!-- CODE -->
					<td><input type="text" id="code" name="code" value="" autocomplete="OFF" class="input_100" onblur="getDescription(this);" onkeypress="focusNext(this, 'bprice', event)"></td>

					<!-- SEARCH BUTTON FOR CODE -->
					<td><a href="javascript:openSearch()"><img src="../images/findfree.gif" border="0" title="Search" alt="Search"></a></td>

					<!-- DESCRIPTION -->
					<td bgcolor='#e7e7e7'><span id="description">&nbsp;</span></td>

					<!-- B PRICE -->
					<td><input type="text" name="bprice" value="0.0" class="input_50" autocomplete="OFF" onblur="getSPrice(this);" onkeypress="return focusNext(this, 'sprice', event)"></td>
<!-- 					<td><input type="text" name="bprice" value="0.0" class="input_50" autocomplete="OFF" onkeypress="return focusNext(this, 'sprice', event)"></td>
 -->
					<!-- S PRICE -->
					<td><input type="text" name="sprice" value="0.0" class="input_50" autocomplete="OFF" onkeypress="return focusNext(this, 'qty', event)"></td>

					<!-- QTY -->
					<td><input type="text" name="qty" value="1" class="input_50" autocomplete="OFF" onkeypress="return focusNext(this, 'code', event)"></td>

					<!-- MEASUREMENT UNIT -->
					<td align="left"><span id="measurement_unit" class="normaltext">&nbsp;</span></td>
					<td><span id="tax" class="normaltext"></span></td>
				</tr>
			</table>
			<br>
			
			<table width="100%" height="75%" border="0" cellpadding="0" cellspacing="0">
<!-- 				<tr bgcolor="lightgrey" height='12px'>
					<td width='70px' class="normaltext_bold" >Code</td>
					<td width='250px' class="normaltext_bold">Description</td>
					<td width='80px' class="normaltext_bold" align="right">B.Price</td>
					<td width='80px' class="normaltext_bold" align="right">S.Price</td>
					<td width='60px' class="normaltext_bold" align="right">Tax</td>
					<td width='60px' class="normaltext_bold" align="right">Taxable<br>Value</td>
					<td width='70px' class="normaltext_bold" align="right">Qty</td>
					<td width='80px' class="normaltext_bold" align="right">Amount</td>
					<td width='10px'>&nbsp;</td>
				</tr>
 -->
 				<tr>
					<td colspan='7'>
						<iframe name='frame_products' id='frame_products' src='purchase_order_list.php' width='100%' height="100%" frameborder="0" allowtransparency="TRUE"></iframe>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</form>
</body>
</html>
