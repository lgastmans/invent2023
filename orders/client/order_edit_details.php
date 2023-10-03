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
<body bgcolor="lightgrey"  leftmargin="2" topmargin="2" marginwidth="2" marginheight="2">

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
		
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				strRetVal = requester.responseText;
				arrRetVal = strRetVal.split('|');
				
				if (arrRetVal[0] == 'OK') {
					oSpanAddress.innerHTML = arrRetVal[1];
					oSpanCity.innerHTML = arrRetVal[2];
				}
				else {
					oSpanAddress.innerHTML = '';
					oSpanCity.innerHTML = '';
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
//				alert('test: '+strRetVal);
			}
			else {
				alert("failed to load page... please click the button again.");
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
		top.frames['frame_list'].document.location = 'order_edit_list.php';
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
		var oDiscount = document.order_details.discount;
		var oSelectInvoiceIsDebit = document.order_details.invoice_is_debit;
		var oSelectStatus = document.order_details.select_status;
		var oTextSupplyDateTime = document.order_details.supply_date_time;
		var oTextSupplyPlace = document.order_details.supply_place;
		
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
			"&order_discount="+oDiscount.value+
			"&order_invoice_is_debit="+oSelectInvoiceIsDebit.value+
			"&order_status="+oSelectStatus.value+
			"&order_supply_date_time="+oTextSupplyDateTime.value+
			"&order_supply_place="+oTextSupplyPlace.value;
			
//		alert(str_pass);
		
		requester_sessions.onreadystatechange = stateHandler_sessions;
		requester_sessions.open("GET", str_pass);
		requester_sessions.send(null);
		
		parent.frames['frame_list'].document.location = 'order_edit_list.php';
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
		var oTextSupplyDateTime = document.order_details.supply_date_time;
		var oTextSupplyPlace = document.order_details.supply_place;

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
			else if (focusElem == 'supply_date_time') {
				oTextSupplyDateTime.focus();
			}
			else if (focusElem == 'supply_place') {
				oTextSupplyPlace.focus();
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
						<td align='right' class="normaltext" width="60px">Reference</td>
						<td>
							<input type='text' value='<?echo $_SESSION['order_reference']?>' name='order_reference' onblur='set_session_variables()' class='input_100' onkeypress="focusNext(this, 'select_status', event)" autocomplete="OFF">
						</td>
						<td align='right' class="normaltext">Status</td>
						<td>
							<select name='select_status' class="select_100" onblur='set_session_variables()' disabled onkeypress="focusNext(this, 'order_date', event)">
								<option value='<?echo BILL_STATUS_PROCESSING;?>' <?if ($_SESSION['order_status'] == BILL_STATUS_PROCESSING) echo "selected";?>>Processing</option>
								<option value='<?echo BILL_STATUS_DISPATCHED;?>' <?if ($_SESSION['order_status'] == BILL_STATUS_DISPATCHED) echo "selected";?>>Dispatched</option>
								<option value='<?echo BILL_STATUS_DELIVERED;?>' <?if ($_SESSION['order_status'] == BILL_STATUS_DELIVERED) echo "selected";?>>Delivered</option>
							</select>
						</td>
						<td align='right' class="normaltext">Dated</td>
						<td>
							<input type="text" name="order_date" value="<?echo $_SESSION['order_date']?>" class="input_100" onblur='set_session_variables()' onkeypress="focusNext(this, 'select_client', event)" autocomplete="OFF">
							<a href="javascript:cal1.popup();"><img src="../../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
						</td>
					</tr>
				</table>
				
				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align='right' class="normaltext">
							Client:&nbsp;
						</td>
						<td>
							<select name="select_client" onchange="getClient();" class='select_300' onkeypress="focusNext(this, 'order_handling', event)">
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
						<td align='right' class="normaltext">
							Type:&nbsp;
						</td>
						<td>
							<select name='invoice_is_debit' class='select_100' onblur='set_session_variables()'>
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
						<td align='right' class="normaltext">Note</td>
						<td colspan="3"><input type='text' name='note' value='<?echo $_SESSION['order_note']?>' class='input_400' onblur='set_session_variables()' onkeypress="focusNext(this, 'code', event)" autocomplete="OFF"></td>
						<td align='right' class="normaltext">Payment Type:</td>
						<td>
							<select name='select_payment_type' class='select_150' onblur='set_session_variables()' onkeypress="focusNext(this, 'supply_date_time', event)">
								<option value="1" <?if ($_SESSION['order_payment_type']==BILL_CASH) echo 'selected';?>>Cash</option>
								<option value="4" <?if ($_SESSION['order_payment_type']==BILL_CREDIT_CARD) echo 'selected';?>>Credit Card</option>
								<option value="2" <?if ($_SESSION['order_payment_type']==BILL_ACCOUNT) echo 'selected';?>>Account</option>
							</select>
						</td>
					</tr>
				</table>

				<table width="100%" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align='right' class="normaltext">
							Date &amp; Time of Supply:
						</td>
						<td colspan="3">
							<input type='text' name='supply_date_time' value='<?echo $_SESSION['order_supply_date_time']?>' class='input_200' onblur='set_session_variables()' onkeypress="focusNext(this, 'supply_place', event)" autocomplete="OFF">
							<a href="javascript:cal2.popup();"><img src="../../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
						</td>

						<td align='right' class="normaltext">
							Place of Supply:
						</td>
						<td>
							<input type='text' name='supply_place' value='<?echo $_SESSION['order_supply_place']?>' class='input_200' onblur='set_session_variables()' onkeypress="focusNext(this, 'code', event)" autocomplete="OFF">
						</td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
</form>

<script language="javascript">
	var oTextOrderDate = document.order_details.order_date;

	var oTextSupplyDateTime = document.order_details.supply_date_time;

	if (oTextOrderDate) {
		var cal1 = new calendar1(oTextOrderDate);
		cal1.year_scroll = true;
		cal1.time_comp = false;
	}

	if (oTextSupplyDateTime) {
		var cal2 = new calendar1(oTextSupplyDateTime);
		cal2.year_scroll = true;
		cal2.time_comp = true;
	}

</script>

</body>
</html>