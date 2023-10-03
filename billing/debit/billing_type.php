<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../get_bill_number.php");

	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == 'cancel') {
			unset($_SESSION["arr_item_batches"]);	
			unset($_SESSION["arr_total_qty"]);
			if (IsSet($_GET["type"]))
				$_SESSION['current_bill_type'] = $_GET["type"];
			else
				$_SESSION['current_bill_type'] = 1;
//			$_SESSION['current_bill_day'] = date('j');
			$_SESSION['current_account_number'] = "";
			$_SESSION['bill_total'] = 0;
			$_SESSION['sales_promotion'] = 0;

			echo "<script language=\"javascript\">;";
			echo "parent.frames[\"frame_list\"].document.location = \"billing_list.php\";";
			echo "</script>";
		}
	}
	
	$int_access_level = (getModuleAccessLevel('Billing'));
	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	if (IsSet($_SESSION['current_bill_day'])) {
		$int_current_bill_day = $_SESSION['current_bill_day'];
	}
	else
		$int_current_bill_day = date('j');

	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_creditcard, can_bill_fs_account, can_bill_pt_account
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_credit = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y')
		$bool_cash = true;
	if ($qry->FieldByName('can_bill_creditcard') == 'Y')
		$bool_credit = true;
	if ($qry->FieldByName('can_bill_fs_account') == 'Y')
		$bool_fs = true;
	if ($qry->FieldByName('can_bill_pt_account') == 'Y')
		$bool_pt = true;

	// get the next bill number
        $int_bill_number = get_bill_number_no_update($_SESSION['current_bill_type']);

?>

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
	var requester_method = createRequest();

	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				oSpanBillNumber = document.getElementById('bill_number');

				str_retval = requester.responseText;

				oSpanBillNumber.innerHTML = 'Bill: ' + str_retval;
			}
			else {
				alert("failed to get bill number... please try again.");
			}
			requester = null;
			requester = createRequest();
		}
	}

	function statehandler_method() {
		if (requester_method.readyState == 4) {
			if (requester_method.status == 200) {
				var oSpanStatus = document.getElementById('connect_status');
				var oButtonStatus = document.billing_type.connect_method;

				str_retval = requester_method.responseText;

				if (str_retval == 2) { 		// CONNECT_OFFLINE_LIMITED_ACCESS
					oSpanStatus.innerHTML = '<b>[ offline ]&nbsp;</b>';
					oButtonStatus.value = 'go online';
				}
				else if (str_retval == 3) {	// CONNECT_ONLINE
					oSpanStatus.innerHTML = '<b>[ online ]&nbsp;</b>';
					oButtonStatus.value = 'go offline';
				}
			}
			requester_method = null;
			requester_method = createRequest();
		}
	}


	function setAccountType() {
		requester.onreadystatechange = stateHandler;

		var oTextBoxType = document.billing_type.bill_type;
		var oTextBoxDay = document.billing_type.bill_day;

		if (oTextBoxType.value == 1) { // cash
			setTimeout("parent.frames['frame_enter'].document.billing_enter.code.focus();", 500);
			
			parent.document.body.rows="45,0,0,80,*,70,40";
			oListBox = parent.frames["frame_list"].document.billing_list.item_list;
			oListBox.size = 20;
		}
		else if ((oTextBoxType.value == 2) || (oTextBoxType.value == 3) || (oTextBoxType.value == 6))  { // account OR PT account OR transfer
			setTimeout("parent.frames['frame_account'].document.billing_account.account_number.focus();", 500);
			
			parent.document.body.rows="45,80,0,80,*,70,40";
			oListBox = parent.frames["frame_list"].document.billing_list.item_list;
			oListBox.size = 15;
		}
		else if (oTextBoxType.value == 4)  { // Credit Card
			setTimeout("parent.frames['frame_creditcard'].document.billing_creditcard.card_name.focus();", 500);

			parent.document.body.rows="45,0,80,80,*,70,40";
			oListBox = parent.frames["frame_list"].document.billing_list.item_list;
			oListBox.size = 15;
		}
		parent.frames["frame_account"].document.location = "billing_account.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
		parent.frames["frame_action"].document.location = "billing_action.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
		parent.frames["frame_list"].document.location = "billing_list.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;

		requester.open("GET", "../get_bill_number.php?live=2&bill_type="+oTextBoxType.value);
		requester.send(null);
	}

	function setBillDay() {
		var oTextBoxType = document.billing_type.bill_type;
		var oTextBoxDay = document.billing_type.bill_day;
		var oTextAccountName = parent.frames['frame_account'].document.getElementById('account_name');

		parent.frames["frame_account"].document.location = "billing_account.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value+"&account_name="+oTextAccountName.innerHTML;
		parent.frames["frame_action"].document.location = "billing_action.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
		parent.frames["frame_list"].document.location = "billing_list.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
	}

	function set_connect_method() {
		var oButtonStatus = document.billing_type.connect_method;

		if (oButtonStatus.value == 'go online')
			connect_method = 3;
		else
			connect_method = 2;
		requester_method.onreadystatechange = statehandler_method;
		requester_method.open("GET", "set_connect_method.php?live=1&connect_method="+connect_method);
		requester_method.send(null);
	}

</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
	</head>
<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

<form name="billing_type" method="GET">
  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
  <td>
	<font class='<?echo $str_class_header?>'>Type:&nbsp;</font>
	<select name="bill_type" class="<?echo $str_class_select?>" <?if ($_SESSION['bill_id'] > -1) echo "disabled";?> onchange="javascript:setAccountType()">
		<? if ($bool_cash == true) { ?>
			<option value="1" <? if ($_SESSION['current_bill_type'] == BILL_CASH) echo "selected=\"selected\""?>>Cash</option>
		<? } ?>
		<? if ($bool_credit == true) { ?>
			<option value="4" <? if ($_SESSION['current_bill_type'] == BILL_CREDIT_CARD) echo "selected=\"selected\""?>>Credit Card</option>
		<? } ?>
		<? if ($bool_fs == true) { ?>
			<option value="2" <? if ($_SESSION['current_bill_type'] == BILL_ACCOUNT) echo "selected=\"selected\""?>>Account</option>
		<? } ?>
		<? if ($bool_pt == true) { ?>
			<option value="3" <? if ($_SESSION['current_bill_type'] == BILL_PT_ACCOUNT) echo "selected=\"selected\""?>>Pour Tous</option>
		<? } ?>
		<? if (CAN_BILL_TRANSFER_GOOD === 1) { ?>
			<option value="6" <? if ($_SESSION['current_bill_type'] == BILL_TRANSFER_GOOD) echo "selected=\"selected\""?>>Transfer of Goods</option>
		<? } ?>
	</select>
	</td>

	<td><font style='font-family:Verdana,sans-serif;color:red;font-weight:bold;'>REVERSE BILL</font></td>
	<? if ($_SESSION['bill_id'] > -1) { ?>
	<td>
		<font class='<?echo $str_class_header?>'>Bill:&nbsp;<b><?echo $_SESSION['bill_number']?></b></font>
	</td>
	<? } else { ?>
	<td align='left'>
		<span id='bill_number' class='<?echo $str_class_header?>'>Bill:&nbsp <?echo $int_bill_number;?></span>
	</td>
	<? } ?>
	
	<td>
		<font class="<?echo $str_class_header?>">
		<?
			if ($bool_pt == false) {
				if ($_SESSION['connect_mode'] == CONNECT_ONLINE) { ?>
					<span id='connect_status'><b>[ online ]</b>&nbsp;</span><input type='button' name='connect_method' value='go offline' onclick='set_connect_method()'>
				<? } else { ?>
					<span id='connect_status'><b>[ offline ]</b>&nbsp;</span><input type='button' name='connect_method' value='go online' onclick='set_connect_method()'>
		<? 		}
			} 
		?>
		</font>
	</td>

	<td align="right" class="headertext">
	
		<select name="bill_day" width='50px' class="<?echo $str_class_select?>" <?if ($_SESSION['bill_id'] > -1) echo "disabled";?> onchange="javascript:setBillDay()" <? if ($_SESSION['str_user_can_change_bill_date'] == 'N') { echo "disabled";} ?> >
		<?
			$int_num_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
			for ($i=1; $i<=date('j'); $i++) {
			if ($i == $int_current_bill_day)
				echo "<option value=".$i." selected=\"selected\">".$i;
			else
				echo "<option value=".$i.">".$i;
			}
		?>
		</select>

	<font style="font-size:16px;font-weight:bold;">
	<? echo "&nbsp;".getMonthName($_SESSION["int_month_loaded"])."&nbsp;&nbsp;".$_SESSION["int_year_loaded"]."&nbsp;"; ?>
	</font>
	</td>
	</tr>
	</table>

	<script language='javascript'>
		var oTextBoxType = document.billing_type.bill_type;
		if (oTextBoxType.value == 1)
			setTimeout("parent.frames['frame_enter'].document.billing_enter.code.focus();", 500);
		else if ((oTextBoxType.value == 2) || (oTextBoxType.value == 3))
			parent.frames['frame_account'].document.billing_account.account_number.focus();
		else if (oTextBoxType.value == 4)
			parent.frames['frame_creditcard'].document.billing_creditcard.card_name.focus();
	</script>

</form>
</body>
</html>
