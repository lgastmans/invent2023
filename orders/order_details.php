<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	//====================
	// AV BAKERY should have only weekly active
	//====================
	$active_daily = 'Y';
	$active_weekly = 'Y';
	$active_monthly = 'Y';
	$active_once = 'Y';
	
	//====================
	// get the list of communities
	//====================
	$qry_communities = new Query("
		SELECT *
		FROM communities
		ORDER BY is_individual DESC, community_name
	");

        //====================
	// get which types that can be billed
        //====================
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y')
		$bool_cash = true;
	if ($qry->FieldByName('can_bill_fs_account') == 'Y')
		$bool_fs = true;
	if ($qry->FieldByName('can_bill_pt_account') == 'Y')
		$bool_pt = true;
?>

<html>
<head><TITLE></TITLE>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=0 topmargin=5>

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
		oTextBoxNumber = document.order_details.account_number;
		oTextBoxName = document.getElementById('account_name');
		var oListOrderType = document.order_details.list_order_type;
		
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				strRetVal = requester.responseText;
				arrRetVal = strRetVal.split('|');

				if (arrRetVal[0] == 'OK') {
					oTextBoxName.innerHTML = arrRetVal[1];
					set_session_variables();
					oListOrderType.focus();
				}
				else {
					oTextBoxName.innerHTML = arrRetVal[0];
				}
			}
			else {
				alert("1. failed to load page... please click the button again.");
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
				alert("2. failed to load page... please click the button again.");
			}
			requester_sessions = null;
			requester_sessions = createRequest();
		}
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
    
		var oListPaymentType = document.order_details.list_payment_type;
		var oTextBoxAccount = document.order_details.account_number;
		var oListOrderType = document.order_details.list_order_type;
		var oListDay = document.order_details.list_day;
		var oListMonth = document.order_details.list_month;
		var oListWeek = document.order_details.list_week;
		var oCheckBillOrder = document.order_details.checkbox_bill;
		var oListCommunity = document.order_details.list_community;
		
		var oTextBoxCode = parent.frames['frame_enter'].document.order_enter.code;
		
		if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'account_number') {
				if (oTextBoxAccount.disabled == true)
					oListWeek.focus();
				else
					oTextBoxAccount.focus();
			}
			else if (focusElem == 'list_order_type') {
				oListOrderType.focus();
			}
                        else if (focusElem == 'list_community') {
				if (oListPaymentType.value == 2) 
					getAccountNumber(aField);
				else
					getPTAccountNumber(aField);
                                oListCommunity.focus();
                        }
			else if (focusElem == 'list_day') {
				if (oListDay.disabled == true)
					oTextBoxCode.focus();
				else
					oListDay.focus();
			}
			else if (focusElem == 'list_week') {
				if (oListWeek.disabled == true)
					oTextBoxCode.focus();
				else
					oListWeek.focus();
			}
			else if (focusElem == 'list_month') {
				if (oListMonth.disabled == true)
					oTextBoxCode.focus();
				else
					oListMonth.focus();
			}
			else if (focusElem == 'code') {
				oTextBoxCode.focus();
			}
			
		} else if (charCode == 27) {
			oTextBoxAccount.select();
		}
		
		return false;
	}

	function getAccountNumber(strAccountNumber) {
		requester.onreadystatechange = stateHandler;
		requester.open("GET", "fs_account.php?live=1&account_number="+strAccountNumber.value);
		requester.send(null);
	}

	function getPTAccountNumber(strAccountNumber) {
		requester.onreadystatechange = stateHandler;
		if (strAccountNumber.value.substring(0,2) != '10')
			strAccountNumber.value = '10' + strAccountNumber.value;
		if (strAccountNumber.value.indexOf('PT') == -1)
			strAccountNumber.value = strAccountNumber.value + ' PT';
		requester.open("GET", "pt_account.php?live=1&account_number="+strAccountNumber.value);
		requester.send(null);
	}

	function set_session_variables() {
		var oListPaymentType = document.order_details.list_payment_type;
		var oTextBoxAccount = document.order_details.account_number;
		var oListOrderType = document.order_details.list_order_type;
		var oListDay = document.order_details.list_day;
		var oListMonth = document.order_details.list_month;
		var oListWeek = document.order_details.list_week;
		var oCheckBillOrder = document.order_details.checkbox_bill;
                var oListCommunity = document.order_details.list_community;
		var oTextNote = document.order_details.order_note;
		
		if (oCheckBillOrder.checked)
			str_bill_order = 'Y';
		else
			str_bill_order = 'N';
		
		str_pass = "order_sessions.php?live=1"+
			"&order_bill_type="+oListPaymentType.value +
			"&order_type="+oListOrderType.value+
			"&order_day="+oListDay.value+
			"&order_week="+oListWeek.value+
			"&order_month="+oListMonth.value+
			"&order_bill_order="+str_bill_order+
			"&order_account_number="+oTextBoxAccount.value+
			"&order_community_id="+oListCommunity.value+
			"&order_note="+oTextNote.value;
			
//		alert(str_pass);
		
		requester_sessions.onreadystatechange = stateHandler_sessions;
		requester_sessions.open("GET", str_pass);
		requester_sessions.send(null);

		parent.frames['frame_list'].document.location = 'order_list.php';
	}
	
	function openSearch(aBillType) {
		var oListPaymentType = document.order_details.list_payment_type;

		if (oListPaymentType.value == 2 || oListPaymentType.value == 3) {
			var myWin = window.open("../common/account_search.php?bill_type="+oListPaymentType.value+"&formname=order_details&fieldname=account_number",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,top=0');
			myWin.focus();
		}
	}

	function setPaymentType() {
		var oListPaymentType = document.order_details.list_payment_type;
		var oTextBoxAccount = document.order_details.account_number;
                var oListCommunity = document.order_details.list_community;
		var oTextBoxNote = document.order_details.order_note;

//		set_session_variables();

		if (oListPaymentType.value == 2 || oListPaymentType.value == 3) {
			oTextBoxAccount.disabled = false;
                        oListCommunity.disabled = false;
		}
		else {
			oTextBoxAccount.disabled = true;
                        oListCommunity.disabled = true;
		}
		oTextBoxNote.focus();
	}

	function setOrderType() {
		var oListOrderType = document.order_details.list_order_type;
		var oListDay = document.order_details.list_day;
		var oListMonth = document.order_details.list_month;
		var oListWeek = document.order_details.list_week;

//		set_session_variables();

		if (oListOrderType.value == 0) { // daily: disable list_day, list_month and list_week
			oListDay.disabled = true;
			oListMonth.disabled = true;
			oListWeek.disabled = true;
		}
		else if (oListOrderType.value == 1) { // weekly: disable list_month and list_week
			oListDay.disabled = false;
			oListMonth.disabled = true;
			oListWeek.disabled = true;
		}
		else if (oListOrderType.value == 2) { // monthly:
			oListDay.disabled = false;
			oListMonth.disabled = true;
			oListWeek.disabled = false;
		}
		else if (oListOrderType.value == 3) { // once:
			oListDay.disabled = false;
			oListMonth.disabled = false;
			oListWeek.disabled = false;
		}
	}

</script>

<form name="order_details" method="GET" onsubmit='return false'>

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr><td align='center'>
<?
	boundingBoxStart("750", "../images/blank.gif");
?>
	<table width="100%" border="0" cellpadding="3" cellspacing="0">

		<tr>
			<td align='right' class="normaltext">
				Payment Type:&nbsp;
			</td>
			<td>
				<select name="list_payment_type" onchange="setPaymentType();" onblur='set_session_variables()' class='select_100'>
				<? if ($bool_cash == true) { ?>
					<option value="<?echo BILL_CASH?>" <? if ($_SESSION['order_bill_type'] == BILL_CASH) echo "selected=\"selected\""?>>Cash</option>
				<? } ?>
				<? if ($bool_fs == true) { ?>
					<option value="<?echo BILL_ACCOUNT?>" <? if ($_SESSION['order_bill_type'] == BILL_ACCOUNT) echo "selected=\"selected\""?>>Account</option>
				<? } ?>
				<? if ($bool_pt == true) { ?>
					<option value="<?echo BILL_PT_ACCOUNT?>" <? if ($_SESSION['order_bill_type'] == BILL_PT_ACCOUNT) echo "selected=\"selected\""?>>Pour Tous</option>
				<? } ?>
				</select>
				&nbsp;
				<input type="checkbox" <?if ($_SESSION['order_bill_order'] == 'Y') echo "checked";?> name="checkbox_bill" onblur='set_session_variables()' onchange="javascript:setOrderType();" class="normaltext">&nbsp;
				<font class="normaltext">Make a transfer for this order</font>
			</td>
		</tr>

		<tr>
			<td align='right' class="normaltext">
				Note:&nbsp;
			</td>
			<td>
				<input type='text' name='order_note' class='input_400' value="<?echo $_SESSION['order_note']?>"  onblur='set_session_variables()' onkeypress="focusNext(this, 'account_number', event)" autocomplete="OFF">
			</td>
		</tr>

		<tr valign='center'>
			<td align='right' class="normaltext">
				Account:&nbsp;
			</td>
			<td>
				<input type="text" name="account_number" class='input_100' <?if ($_SESSION['order_bill_type']==BILL_CASH) echo "disabled";?> value="<?echo $_SESSION['order_account_number']?>" onblur='set_session_variables()' onkeypress="focusNext(this, 'list_community', event)" autocomplete="OFF">
				&nbsp;<a href="javascript:openSearch('2')"><img src="../images/findfree.gif" border="0" title="Search" alt="Search" width='23px' height='22px'></a>&nbsp;&nbsp;<span id="account_name" class='normaltext'><b><?echo $_SESSION['order_account_name'];?></b></span>
			</td>
		</tr>
		
		<tr>
			<td align='right' class="normaltext">
				Community:&nbsp;
			</td>
			<td>
				<select name="list_community" onchange="javascript:setOrderType()" onblur='set_session_variables()' class='select_200' <?if ($_SESSION['order_bill_type']==BILL_CASH) echo "disabled";?> onkeypress="focusNext(this, 'list_order_type', event)">
					<?
						for ($i=0; $i<$qry_communities->RowCount(); $i++) {
							if ($qry_communities->FieldByName('community_id') == $_SESSION['order_community_id'])
								echo "<option value='".$qry_communities->FieldByName('community_id')."' selected>".$qry_communities->FieldByName('community_name')."\n";
							else
								echo "<option value='".$qry_communities->FieldByName('community_id')."'>".$qry_communities->FieldByName('community_name')."\n";
							$qry_communities->Next();
						}
					?>
				</select>
			</td>
		</tr>

		<tr>
			<td align='right' class="normaltext">
				Type:&nbsp;
			</td>
			<td>
				<select name="list_order_type" onchange="javascript:setOrderType()" class='select_100' onblur='set_session_variables()' onkeypress="focusNext(this, 'list_day', event)">
                                        <? if ($active_daily == 'Y') { ?>
                                            <option value="<? echo ORDER_TYPE_DAILY ?>" <?if ($_SESSION['order_type']==ORDER_TYPE_DAILY) echo "selected";?>>Daily</option>
                                        <? } ?>
                                        <? if ($active_weekly == 'Y') { ?>
                                            <option value="<? echo ORDER_TYPE_WEEKLY ?>" <?if ($_SESSION['order_type']==ORDER_TYPE_WEEKLY) echo "selected";?>>Weekly</option>
                                        <? } ?>
                                        <? if ($active_monthly == 'Y') { ?>
                                            <option value="<? echo ORDER_TYPE_MONTHLY ?>" <?if ($_SESSION['order_type']==ORDER_TYPE_MONTHLY) echo "selected";?>>Monthly</option>
                                        <? } ?>
                                        <? if ($active_once == 'Y') { ?>
                                            <option value="<? echo ORDER_TYPE_ONCE ?>" <?if ($_SESSION['order_type']==ORDER_TYPE_ONCE) echo "selected";?>>Once</option>
                                        <? } ?>
				</select>
				<font class="normaltext">
				
				&nbsp;Day:
				</font>
				<select name="list_day" <?if ($_SESSION['order_type']==ORDER_TYPE_DAILY) echo "disabled";?> class='select_100' onblur='set_session_variables()' onchange="javascript:setOrderType()" onkeypress="focusNext(this, 'list_week', event)">
					<option value="<? echo ORDER_DAY_MONDAY?>" <?if ($_SESSION['order_day']==ORDER_DAY_MONDAY) echo "selected";?>>Monday</option>
					<option value="<? echo ORDER_DAY_TUESDAY?>" <?if ($_SESSION['order_day']==ORDER_DAY_TUESDAY) echo "selected";?>>Tuesday</option>
					<option value="<? echo ORDER_DAY_WEDNESDAY?>" <?if ($_SESSION['order_day']==ORDER_DAY_WEDNESDAY) echo "selected";?>>Wednesday</option>
					<option value="<? echo ORDER_DAY_THURSDAY?>" <?if ($_SESSION['order_day']==ORDER_DAY_THURSDAY) echo "selected";?>>Thursday</option>
					<option value="<? echo ORDER_DAY_FRIDAY?>" <?if ($_SESSION['order_day']==ORDER_DAY_FRIDAY) echo "selected";?>>Friday</option>
					<option value="<? echo ORDER_DAY_SATURDAY?>" <?if ($_SESSION['order_day']==ORDER_DAY_SATURDAY) echo "selected";?>>Saturday</option>
				</select>
				<font class="normaltext">
				&nbsp;Week:
				</font>
				<select name="list_week" <?if ($_SESSION['order_type']<=ORDER_TYPE_WEEKLY) echo "disabled";?> class='select_100' onblur='set_session_variables()' onchange="javascript:setOrderType()" onkeypress="focusNext(this, 'list_month', event)">
					<option value="<? echo ORDER_WEEK1?>" <?if ($_SESSION['order_week']==ORDER_WEEK1) echo "selected";?>>Week 1</option>
					<option value="<? echo ORDER_WEEK2?>" <?if ($_SESSION['order_week']==ORDER_WEEK2) echo "selected";?>>Week 2</option>
					<option value="<? echo ORDER_WEEK3?>" <?if ($_SESSION['order_week']==ORDER_WEEK3) echo "selected";?>>Week 3</option>
					<option value="<? echo ORDER_WEEK4?>" <?if ($_SESSION['order_week']==ORDER_WEEK4) echo "selected";?>>Week 4</option>
					<option value="<? echo ORDER_WEEK5?>" <?if ($_SESSION['order_week']==ORDER_WEEK5) echo "selected";?>>Week 5</option>
				</select>
				<font class="normaltext">
				&nbsp;Month:
				</font>
				<select name="list_month" <?if ($_SESSION['order_type']<=ORDER_TYPE_MONTHLY) echo "disabled";?> class='select_100' onblur='set_session_variables()' onchange="javascript:setOrderType()" onkeypress="focusNext(this, 'code', event)">
					<option value="1" <?if ($_SESSION['order_month']==1) echo "selected";?>>January</option>
					<option value="2" <?if ($_SESSION['order_month']==2) echo "selected";?>>February</option>
					<option value="3" <?if ($_SESSION['order_month']==3) echo "selected";?>>March</option>
					<option value="4" <?if ($_SESSION['order_month']==4) echo "selected";?>>April</option>
					<option value="5" <?if ($_SESSION['order_month']==5) echo "selected";?>>May</option>
					<option value="6" <?if ($_SESSION['order_month']==6) echo "selected";?>>June</option>
					<option value="7" <?if ($_SESSION['order_month']==7) echo "selected";?>>July</option>
					<option value="8" <?if ($_SESSION['order_month']==8) echo "selected";?>>August</option>
					<option value="9" <?if ($_SESSION['order_month']==9) echo "selected";?>>September</option>
					<option value="10" <?if ($_SESSION['order_month']==10) echo "selected";?>>October</option>
					<option value="11" <?if ($_SESSION['order_month']==11) echo "selected";?>>November</option>
					<option value="12" <?if ($_SESSION['order_month']==12) echo "selected";?>>December</option>
				</select>
			</td>
		</tr>
	</table>

<?
    boundingBoxEnd("750", "../images/blank.gif");
?>
</td></tr>
</table>

</form>

</body>
</html>