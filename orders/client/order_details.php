<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	//====================
	// get the list of clients
	//====================
	$qry = new Query("
		SELECT id, company, address, city, zip
		FROM customer
		ORDER BY company
	");
	
	$qry_customer = new Query("
		SELECT *
		FROM customer
		WHERE id = ".$_SESSION['order_client_id']
	);
?>

<html>
<head><TITLE></TITLE>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	<script language="JavaScript" src="../../include/calendar1.js"></script>
</head>
<body bgcolor="lightgrey" leftmargin="2" topmargin="2" marginwidth="2" marginheight="2">

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
	var requester_sessions = createRequest();

	function stateHandler() {
		oSpanAddress = document.getElementById('client_address');
		oSpanCity = document.getElementById('client_city');
		oTextDiscount = document.order_details.discount;
		
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				strRetVal = requester.responseText;
				arrRetVal = strRetVal.split('|');
				
				if (arrRetVal[0] == 'OK') {
					oSpanAddress.innerHTML = arrRetVal[1];
					oSpanCity.innerHTML = arrRetVal[2];
					oTextDiscount.value = arrRetVal[3];
				}
				else {
					oSpanAddress.innerHTML = '';
					oSpanCity.innerHTML = '';
					oTextDiscount.value = 0;
				}
				set_session_variables();
			}
			else {
				alert("failed to load page... please click the button again.");
			}
			requester = null;
			requester = createRequest();
		}
	}
	
	function stateHandler_sessions() {
		if (requester_sessions.readyState == 4) {
			if (requester_sessions.status == 200)  {
				strRetVal = requester_sessions.responseText;
				//alert('test: '+strRetVal);
			}
			else {
				alert("failed to load page");
			}
			requester_sessions = null;
			requester_sessions = createRequest();
		}
	}

	function getClient() {
		var oSelectClient = document.order_details.select_client;
		var oTextHandling = document.order_details.handling_charge;
		
		requester.onreadystatechange = stateHandler;
		requester.open("GET", "get_client_details.php?live=1&client_id="+oSelectClient.value);
		requester.send(null);
		
		oTextHandling.focus();
		
		top.frames['frame_list'].document.location = 'order_list.php';
	}

	function set_session_variables() {
		var oSelectClient = document.order_details.select_client;
		var oTextRef = document.order_details.order_reference;
		var oTextDate = document.order_details.order_date;
		var oTextHandling = document.order_details.handling_charge;
		var oCheckHandling = document.order_details.handling_is_percentage;
		var oTextCourier = document.order_details.courier_charge;
		var oCheckCourier = document.order_details.courier_is_percentage;
		var oTextAdvance = document.order_details.advance_paid;
		var oTextNote = document.order_details.note;
		var oSelectPaymentType = document.order_details.select_payment_type;
		var oTextDiscount = document.order_details.discount;
		var oSelectInvoiceIsDebit = document.order_details.invoice_is_debit;
		var oSelectStatus = document.order_details.select_status;
		
		strHandlingChecked = 'N';
		if (oCheckHandling.checked)
			strHandlingChecked = 'Y';
		
		strCourierChecked = 'N';
		if (oCheckCourier.checked)
			strCourierChecked = 'Y';
		
		str_pass = "order_sessions.php?live=1"+
			"&order_client_id="+oSelectClient.value+
			"&order_reference="+oTextRef.value+
			"&order_date="+oTextDate.value+
			"&order_handling="+oTextHandling.value+
			"&order_handling_percentage="+strHandlingChecked+
			"&order_courier="+oTextCourier.value+
			"&order_courier_percentage="+strCourierChecked+
			"&order_advance="+oTextAdvance.value+
			"&order_note="+oTextNote.value+
			"&order_payment_type="+oSelectPaymentType.value+
			"&order_discount="+oTextDiscount.value+
			"&order_invoice_is_debit="+oSelectInvoiceIsDebit.value+
			"&order_status="+oSelectStatus.value;
			
//		alert(str_pass);
		
		requester_sessions.onreadystatechange = stateHandler_sessions;
		requester_sessions.open("GET", str_pass);
		requester_sessions.send(null);
		
		parent.frames['frame_list'].document.location = 'order_list.php';
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		
		var oSelectClient = document.order_details.select_client;
		var oTextRef = document.order_details.order_reference;
		var oTextDate = document.order_details.order_date;
		var oTextHandling = document.order_details.handling_charge;
		var oTextAdvance = document.order_details.advance_paid;
		var oTextNote = document.order_details.note;
		var oSelectPaymentType = document.order_details.select_payment_type;
		var oSelectStatus = document.order_details.select_status;
		
		var oTextBoxCode = parent.frames['frame_enter'].document.order_enter.code;
		
		if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'order_date') {
				oTextDate.focus();
			}
			else if (focusElem == 'select_status') {
				oSelectStatus.focus();
			}
			else if (focusElem == 'select_client') {
				oSelectClient.focus();
			}
			else if (focusElem == 'order_handling') {
					oTextHandling.focus();
			}
			else if (focusElem == 'order_advance') {
				oTextAdvance.focus();
			}
			else if (focusElem == 'note') {
				oTextNote.focus();
			}
			else if (focusElem == 'payment_type') {
				oSelectPaymentType.focus();
			}
			else if (focusElem == 'code') {
				oTextBoxCode.focus();
			}
			
			set_session_variables();
			
		} else if (charCode == 27) {
			oTextRef.select();
		}
		
		return false;
	}

</script>

<form name="order_details" method="GET" onsubmit='return false'>

	<table class="edit" width="100%" border="0" cellpadding="1" cellspacing="0">
		<tr>
			<td>
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align='right' class="normaltext" width="60px">Reference:</td>
						<td>
							<input type='text' value='<?echo $_SESSION['order_reference']?>' name='order_reference' class="input_200" onblur='set_session_variables()' class='input_100' onkeypress="focusNext(this, 'select_status', event)" autocomplete="OFF">
						</td>
						<td align='right' class="normaltext">Status:</td>
						<td>
							<select name='select_status' class="select_100" <?if ($_SESSION['order_status'] == ORDER_STATUS_PENDING) echo "disabled";?> onblur='set_session_variables()' onkeypress="focusNext(this, 'order_date', event)">
								<option value='<?echo ORDER_STATUS_PENDING;?>' <?if ($_SESSION['order_status'] == ORDER_STATUS_PENDING) echo "selected";?>>Pending</option>
								<option value='<?echo ORDER_STATUS_RECEIVED;?>' <?if ($_SESSION['order_status'] == ORDER_STATUS_RECEIVED) echo "selected";?>>Received</option>
							</select>
						</td>
						<td align='right' class="normaltext">Dated:</td>
						<td>
							<input type="text" name="order_date" value="<?echo $_SESSION['order_date'];?>" class="input_100" onblur='set_session_variables()' onkeypress="focusNext(this, 'select_client', event)" autocomplete="OFF">
							<a href="javascript:cal1.popup();"><img src="../../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
						</td>
					</tr>
				</table>
				
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align='right' class="normaltext" width="60px">
							Client:&nbsp;
						</td>
						<td width="320px">
							<select name="select_client" onchange="getClient();" class="select_300" onkeypress="focusNext(this, 'order_handling', event)">
								<option value="0">--select client--</option>
							<?
								for ($i=0; $i<$qry->RowCount(); $i++) {
									if ($qry->FieldByName('id') == $_SESSION['order_client_id'])
										echo "<option value=".$qry->FieldByName('id')." selected>".$qry->FieldByName('company');
									else
										echo "<option value=".$qry->FieldByName('id').">".$qry->FieldByName('company');
									$qry->Next();
								}
							?>
							</select>
						</td>
						<td>
							<input type="button" id="btn-import-dc" value="Import DC">
						</td>
						<td align='right' class="normaltext">
							Type:&nbsp;
						</td>
						<td>
							<select name='invoice_is_debit' class="select_100" onblur='set_session_variables()'>
								<option value="N" <?if ($_SESSION['order_invoice_is_debit']=='N') echo "selected";?>>Credit</option>
								<option value="Y" <?if ($_SESSION['order_invoice_is_debit']=='Y') echo "selected";?>>Debit</option>
							</select>
						</td>
					</tr>
				</table>
		
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td>&nbsp;</td>
						<td class="normaltext_bold">
							<span id='client_address'><?echo $qry_customer->FieldByName('address');?></span>
						</td>
					</tr>
				</table>
				
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td>&nbsp;</td>
						<td class="normaltext_bold">
							<span id='client_city'><?echo $qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip');?></span>
						</td>
					</tr>
				</table>
				
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align='right' class="normaltext" width="60px">Handling</td>
						<td width="120px">
							<input type='text' name='handling_charge' value='<?echo $_SESSION['order_handling']?>' class='input_50' onblur='set_session_variables()' onkeypress="focusNext(this, 'order_advance', event)" autocomplete="OFF">
							<input type="checkbox" name="handling_is_percentage" <?if ($_SESSION['order_handling_percentage']=='Y') echo "checked";?> onblur='set_session_variables()'>%
						</td>
						<td align='right' class="normaltext">Courier</td>
						<td width="120px">
							<input type='text' name='courier_charge' value='<?echo $_SESSION['order_courier']?>' class='input_50' onblur='set_session_variables()' onkeypress="focusNext(this, 'order_advance', event)" autocomplete="OFF">
							<input type="checkbox" name="courier_is_percentage" <?if ($_SESSION['order_courier_percentage']=='Y') echo "checked";?> onblur='set_session_variables()'>%
						</td>
						<td align='right' class="normaltext">Advance</td>
						<td><input type='text' name='advance_paid' value='<?echo $_SESSION['order_advance']?>' class='input_100' onblur='set_session_variables()' onkeypress="focusNext(this, 'note', event)" autocomplete="OFF"></td>
						<td align='right' class="normaltext">Discount</td>
						<td><input type='text' name='discount' value='<?echo $_SESSION['order_discount']?>' class='input_100' onblur='set_session_variables()' onkeypress="focusNext(this, 'note', event)" autocomplete="OFF">%</td>
					</tr>
				</table>
				
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align='right' class="normaltext" width="60px">Note</td>
						<td colspan="3"><input type='text' name='note' value='<?echo $_SESSION['order_note']?>' class='input_400' onblur='set_session_variables()' onkeypress="focusNext(this, 'payment_type', event)" autocomplete="OFF"></td>
						<td align='right' class="normaltext">Payment Type:</td>
						<td>
							<select name='select_payment_type' class='select_150' onblur='set_session_variables()' onkeypress="focusNext(this, 'code', event)">
								<option value="1" <?if ($_SESSION['order_payment_type']==BILL_CASH) echo 'selected';?>>Cash</option>
								<option value="4" <?if ($_SESSION['order_payment_type']==BILL_CREDIT_CARD) echo 'selected';?>>Credit Card</option>
								<option value="2" <?if ($_SESSION['order_payment_type']==BILL_ACCOUNT) echo 'selected';?>>Account</option>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script src="../../include/js/jquery-3.4.1.js"></script>

<script>
	$(document).ready(function() {

		$('#btn-import-dc').on('click', function(e) { 
            $.ajax({
              method  : "POST",
              url   : "get_client_dcs.php",
              data  : {}
            })
            .done( function( msg ) {
            	console.log(msg);
			});			
		});

	});
</script>

<script language="javascript">
	var oTextOrderDate = document.order_details.order_date;

	if (oTextOrderDate) {
		var cal1 = new calendar1(oTextOrderDate);
		cal1.year_scroll = true;
		cal1.time_comp = false;
	}
</script>

</body>
</html>