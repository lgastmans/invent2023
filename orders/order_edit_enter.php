<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

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
	var requester_qty = createRequest();

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				oTextBoxCode = document.order_enter.code;
				oTextBoxQty = document.order_enter.qty;
				oTextBoxDescription = document.getElementById('description');
				oTextBoxUnit = document.getElementById('measurement_unit');

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
					if (arr_retval[2] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
					
					if (arr_retval[3] > -1) {
					    parent.frames['frame_list'].document.location = "order_edit_list.php";
					    oTextBoxQty.value = arr_retval[4];
					    oTextBoxQty.select();
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
					parent.frames["frame_list"].document.location = "order_edit_list.php";
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
	myWin = window.open("../common/product_search.php?formname=billing_enter&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
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

            requester.open("GET", "order_edit_product_description.php?live=1&product_code="+strPassValue);
            requester.send(null);
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.getElementById('code');
		var oTextBoxQty = document.order_enter.qty;
	
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
			else if (focusElem == 'qty') {
                            oTextBoxQty.focus();
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
		var oTextBoxQty = document.order_enter.qty;

		oTextBoxDescription.innerHTML = '';
		oTextBoxUnit.innerHTML = '';
		oTextBoxQty.value = '1';
		oTextBoxCode.value = '';
	}

	function checkQty(aField) {
		var oTextBoxCode = document.order_enter.code;
		var oTextBoxQty = document.order_enter.qty;

		if (aField.value < 0) {
			alert('Quantity cannot be negative');
			can_bill = false;
		}

		if (can_bill == true) {
			var strPassValue = '';
			if ((oTextBoxCode.value == '') || (aField.value == ''))
				strPassValue = 'nil'
			else
				strPassValue = oTextBoxCode.value;

			requester_qty.onreadystatechange = stateHandler_qty;
			requester_qty.open("GET", "order_edit_quantities.php?live=1&product_code=" + strPassValue + "&qty=" + aField.value);
			requester_qty.send(null);
		}
	}

    </script>
    
</head>

<body leftmargin=5 topmargin=5 marginwidth=0 marginheight=0>

    <form name='order_enter' method='GET' onsubmit='return false'>
    
        <table width="600" height="30" border="0" cellpadding="2" cellspacing="0">
		<tr class="headertext">
			<td>Code</td>
			<td>&nbsp;</td>
			<td>Description<img src="../images/blank.gif" width="400px" height="1px"></td>
			<td>Quantity</td>
			<td><img src="../images/blank.gif" width="30px" height="1px"></td>
			<td><img src="../images/blank.gif" width="35px" height="1px"></td>
			<!--<td>Discount</td>-->
		</tr>
		<tr>
			<!-- CODE -->
			<td><input type="text" id="code" name="code" value="" autocomplete="OFF" class="inputbox60" onkeypress="focusNext(this, 'qty', event)" onblur="getDescription(this);"></td>

			<!-- SEARCH BUTTON FOR CODE -->
			<td><a href="javascript:openSearch()"><img src="../images/findfree.gif" border="0" title="Search" alt="Search"></a></td>

			<!-- DESCRIPTION -->
			<td><span id="description" style="font-size:11pt;font-family:Verdana,sans-serif;font-weight:bold;width:350px;">&nbsp;</span></td>

			<!-- QTY -->
			<td><input type="text" name="qty" value="1" class="inputbox60" autocomplete="OFF" onkeypress="return focusNext(this, 'code', event)"></td>

			<!-- MEASUREMENT UNIT -->
			<td align="left"><span id="measurement_unit" class="headertext">&nbsp;</span></td>
		</tr>
	</table>
        
    </form>
    
</body>
</html>