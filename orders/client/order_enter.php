<?
    require_once("../../include/const.inc.php");
    require_once("../../include/session.inc.php");
	require_once("db.inc.php");
	
	$qry_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$edit_price = 'N';
	if ($qry_settings->RowCount() > 0) {
		$edit_price = $qry_settings->FieldByName('bill_edit_price');
	}	
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
    
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
				oTextBoxCode = document.order_enter.code;
				oTextBoxQty = document.order_enter.qty;
				oTextBoxDescription = document.getElementById('description');
				oTextBoxUnit = document.getElementById('measurement_unit');
				oPrice = document.getElementById('price');

				str_retval = requester.responseText;
				arr_retval = str_retval.split('|');

				if (arr_retval[0] == '__NOT_FOUND') {
					can_bill = false;
					oTextBoxCode.value = "";
				}
				else if (arr_retval[0] == "__NOT_AVAILABLE") {
					can_bill = false;
					alert('This product cannot be billed.\n It has been disabled');
					oTextBoxDescription.innnerHTML = arr_retval[0];
					oTextBoxCode.value = "";
					oTextBoxCode.focus();
				}
				else {
					can_bill = true;
					oTextBoxDescription.innerHTML = arr_retval[0];
					oTextBoxUnit.innerHTML = arr_retval[1];
					oPrice.value = arr_retval[5];
					
					if (arr_retval[2] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
					
					if (arr_retval[3] > -1) {
					    parent.frames['frame_list'].document.location = "order_list.php";
					    oTextBoxQty.value = arr_retval[4];
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

	function stateHandler_qty() {
		if (requester_qty.readyState == 4) {
			if (requester_qty.status == 200)  {
				oTextBoxQty = document.order_enter.qty;

				str_retval = requester_qty.responseText;

				if (str_retval != 'nil') {
				    if (can_bill == true) {
					parent.frames["frame_list"].document.location = "order_list.php";
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


    function openSearch() {
	myWin = window.open("../../common/product_search.php?formname=order_enter&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
	myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 600/2));
	myWin.focus();
    }

	function getDescription(strProductCode) {
            requester.onreadystatechange = stateHandler;

            var strPassValue = '';

            if (strProductCode.value == '')
                    strPassValue = 'nil'
            else
                    strPassValue = strProductCode.value;

            requester.open("GET", "product_description.php?live=1&product_code="+strPassValue);
            requester.send(null);
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.order_enter.qty;
		var oPrice = document.getElementById('price');
	
		if (charCode == 9 || charCode == 3) {
			if (focusElem == 'code') {
                            checkQty(oTextBoxQty);
                            if (can_bill == true) {
                                oTextBoxCode.focus();
                            }
                            else {
                                aField.select();
                                can_bill = true;
                            }
			}
			else if (focusElem == 'qty') {
                            oTextBoxQty.focus();
                            oTextBoxQty.select();
			}
			else if (focusElem == 'price') {
				oPrice.focus();
				oPrice.select();
			}
		} else if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'code') {
                            checkQty(oTextBoxQty);
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
			else if (focusElem == 'price') {
				oPrice.focus();
				oPrice.select();
			}
                        
		} else if (charCode == 27) {
			oTextBoxCode.focus();
			clearValues();
                        
		} else if (charCode == 46) { // full stop dissallowed for non-decimal unit of measurement
			if (aField.name == 'qty') {
				if (bool_is_decimal == false)
					return false;
			}
			else if (aField.name == 'price')
				return true;
			else
				return false;
		}

		return true;
	}

	function clearValues() {
		var oTextBoxCode = document.getElementById('code');
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxUnit = document.getElementById('measurement_unit');
		var oTextBoxQty = document.order_enter.qty;
		var oPrice = document.getElementById('price');

		oTextBoxDescription.innerHTML = '';
		oTextBoxUnit.innerHTML = '';
		oTextBoxQty.value = '1';
		oTextBoxCode.value = '';
		if (oPrice)
			oPrice.value = '';
	}
	
	function isNumeric(input){
		alert(input);
		return /^-?(0|[1-9]\d*|(?=\.))(\.\d+)?$/.test(input);
	}

	function checkQty(aField) {
		var oTextBoxCode = document.order_enter.code;
		var oTextBoxQty = document.order_enter.qty;
		var oPrice = document.getElementById('price');
		
		if ((aField.value <= 0) || (isNaN(oTextBoxQty.value))) {
			alert('Quantity must be greater than zero');
			can_bill = false;
		}
		
		if (isNaN(oPrice.value)) {
			alert('Invalid price');
			can_bill = false;
		}
		
		if (can_bill == true) {
			var strPassValue = '';
			if ((oTextBoxCode.value == '') || (aField.value == '0') || (aField.value == ''))
				strPassValue = 'nil'
			else
				strPassValue = oTextBoxCode.value;

			requester_qty.onreadystatechange = stateHandler_qty;
			requester_qty.open("GET", "order_quantities.php?live=1&product_code=" + strPassValue + "&qty=" + aField.value + "&price=" + oPrice.value);
			requester_qty.send(null);
		}
	}
    </script>
</head>

<body bgcolor="lightgrey" leftmargin="2" topmargin="2" marginwidth="2" marginheight="2">
<form name='order_enter' method='GET' onsubmit='return false'>
	<table class="edit" width="100%" height="100%" border="0" cellpadding="2" cellspacing="0">
		<tr class="normaltext">
			<td>Code</td>
			<td>&nbsp;</td>
			<td>Description<img src="../images/blank.gif" width="300px" height="1px"></td>
			<td>Quantity</td>
			<? if ($edit_price == 'Y') { ?>
				<td>Price</td>
			<? } else { ?>
				<td><img src="../../images/blank.gif" width="80px" height="1px"></td>
			<? } ?>
			<td><img src="../../images/blank.gif" width="35px" height="1px"></td>
			<!--<td>Discount</td>-->
		</tr>
		<tr>
			<!-- CODE -->
			<td><input type="text" id="code" name="code" value="" autocomplete="OFF" class="input_100" onkeypress="focusNext(this, 'qty', event)" onblur="getDescription(this);"></td>

			<!-- SEARCH BUTTON FOR CODE -->
			<td><a href="javascript:openSearch()"><img src="../../images/findfree.gif" border="0" title="Search" alt="Search"></a></td>

			<!-- DESCRIPTION -->
			<td><span id="description" style="font-size:11pt;font-family:Verdana,sans-serif;font-weight:bold;width:200px;">&nbsp;</span></td>

			<!-- QTY -->
			<td><input type="text" name="qty" value="1" class="input_100" autocomplete="OFF" onkeypress="return focusNext(this, '<?php if ($edit_price == 'Y') echo "price"; else echo "code" ?>', event)"></td>

			<!-- PRICE -->
			<? if ($edit_price == 'Y') { ?>
				<td><input type='text' name='price' id='price' class='input_100' autocomplete="OFF" onkeypress="return focusNext(this, 'code', event)"></td>
			<? } else { ?>
				<td><span id ='price' class='normaltext'>&nbsp;</span></td>
			<? } ?>
			
			<!-- MEASUREMENT UNIT -->
			<td align="left"><span id="measurement_unit" class="normaltext">&nbsp;</span></td>
		</tr>
	</table>
    </form>
</body>
</html>