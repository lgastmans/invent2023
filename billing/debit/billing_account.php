<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	$qry_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($qry_settings->RowCount() > 0) {
		$str_has_prefix = $qry_settings->FieldByName('pt_account_has_prefix');
		$str_prefix = $qry_settings->FieldByName('pt_account_prefix');
		$str_has_suffix = $qry_settings->FieldByName('pt_account_has_suffix');
		$str_suffix = $qry_settings->FieldByName('pt_account_suffix');
	}

	$str_PTAccounts_enabled = 'N';
	$qry_module = new Query("SELECT * FROM module WHERE module_id = 6 AND active='Y'"); // check for module PT Accounts
	if ($qry_module->RowCount() > 0)
		$str_PTAccounts_enabled = 'Y';
?>

<script language="javascript">

	var arr_retval = new Array();
	var can_view_status = <? echo DOWNLOAD_ALL; ?>;
	var strHasPrefix = '<?echo $str_has_prefix;?>';
	var strPrefix = '<?echo $str_prefix;?>';
	var strHasSuffix = '<?echo $str_has_suffix;?>';
	var strSuffix = '<?echo $str_suffix;?>';
	<? if ($str_PTAccounts_enabled == 'Y') { ?>
		var strPTAccountsEnabled = 'Y';
	<? } else { ?>
		var strPTAccountsEnabled = 'N';
	<? } ?>
	
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
	var requester_status = createRequest();

	function stateHandler() {
		oTextBoxNumber = document.billing_account.account_number;
		oTextBoxName = document.getElementById('account_name');

		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				strRetVal = requester.responseText;
				arrRetVal = strRetVal.split('|');
				if (arrRetVal[0] == 'OK') {
					if (strPTAccountsEnabled == 'Y')
						oTextBoxName.innerHTML = arrRetVal[1] + ' (' + arrRetVal[4] + ')<br>' + arrRetVal[2] + ', ' + arrRetVal[3];
					else
						oTextBoxName.innerHTML = arrRetVal[1];
					parent.frames["frame_action"].document.location="billing_action.php?action=account&account_number="+oTextBoxNumber.value;
					parent.frames["frame_enter"].document.billing_enter.code.focus();
				}
				else if (arrRetVal[0] == '__DISABLED') {
					oTextBoxName.innerHTML = arrRetVal[1];
					parent.frames["frame_action"].document.location="billing_action.php?action=account&account_number="+oTextBoxNumber.value;
					alert('This account has been disabled.');
				}
				else {
					parent.frames["frame_action"].document.location="billing_action.php?action=account&account_number=__NOT_FOUND";
					oTextBoxName.innerHTML = arrRetVal[0];
				}
			}
			else {
				alert("failed to load page... please click the button again.");
			}
			requester = null;
			requester = createRequest();
		}
	}
	
	function state_handler_status() {
		oSpanStatus = document.getElementById('account_status');
		oSpanBalance = document.getElementById('account_balance');
		can_view_status = <?echo DOWNLOAD_ALL;?>

		if (requester_status.readyState == 4) {
			if (requester_status.status == 200)  {
				strRetVal = requester_status.responseText;
				arrRetVal = strRetVal.split('|');
//				alert(strRetVal);
				if (arrRetVal[0] == 'OK') {
					if (arrRetVal[1] == 'Y')
						oSpanStatus.innerHTML = 'ACTIVE';
					else
						oSpanStatus.innerHTML = 'DISABLED';
					oSpanBalance.innerHTML = 'Rs. '+arrRetVal[2];
				}
				else {
					oSpanStatus.innerHTML = 'NOT FOUND';
					oSpanBalance.innerHTML = 'NOT FOUND';
				}
			}
			else {
				alert("failed to load page... please click the button again.");
			}
			requester_status = null;
			requester_status = createRequest();
		}
	}

	function openSearch(aBillType) {
		myWin = window.open("../../common/account_search.php?bill_type="+aBillType+"&formname=billing_account&fieldname=account_number",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,top=0');
		myWin.focus();
	}

	function focusNext(aField, focusElem, evt, account_type) {
            evt = (evt) ? evt : event;
            var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

            var oTextBoxAccount = document.billing_account.account_number;

            if (charCode == 13 || charCode == 3 || charCode == 9) {
                if (focusElem == 'code') {
                    if ((account_type == 2) || (account_type == 6))
                      getAccountNumber(aField);
                    else
                      getPTAccountNumber(aField);
                }
            } else if (charCode == 27) {
		oTextBoxAccount.select();
            }
            
            return false;
	}

	function getAccountNumber(strAccountNumber) {
		requester.onreadystatechange = stateHandler;
		requester.open("GET", "../../common/account.php?live=1&account_number="+strAccountNumber.value);
		requester.send(null);
		
		if (can_view_status > 0) {
			requester_status.onreadystatechange = state_handler_status;
			requester_status.open("GET", "../../common/account.php?live=2&account_number="+strAccountNumber.value);
			requester_status.send(null);
		}
	}
	
	function getPTAccountNumber(strAccountNumber) {
		requester.onreadystatechange = stateHandler;
		if (strHasPrefix == 'Y') {
			strLen = strPrefix.length;
			if (strAccountNumber.value.substring(0,strLen) != strPrefix)
				strAccountNumber.value = strPrefix + strAccountNumber.value;
		}
		if (strHasSuffix == 'Y') {
			if (strAccountNumber.value.indexOf(strSuffix) == -1)
				strAccountNumber.value = strAccountNumber.value + ' ' + strSuffix;
		}
		
		requester.open("GET", "../pt_account.php?live=1&account_number="+strAccountNumber.value);
		requester.send(null);
	}

</script>

<?
$str_account_name = $_SESSION['current_account_name'];
if (IsSet($_GET['action'])) {
	if ($_GET['action'] == "set_type") {
		// save the bill type
		$_SESSION['current_bill_type'] = $_GET['bill_type'];
		$_SESSION['current_bill_day'] = $_GET['bill_day'];
		$_SESSION['current_account_number'] = "";
		if (IsSet($_GET['account_name']))
		    $str_account_name = $_GET["account_name"];
	}
}

?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
	</head>
<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>
<form name="billing_account" method="GET" onsubmit="return false">

<table border="0" cellpadding='0' cellspacing='2'>
	<tr>
		<td class="<?echo $str_class_header?>">Account</td>
		<td>&nbsp;</td>
		<td width='500px' class="<?echo $str_class_header?>">Name</td>
		<? if (DOWNLOAD_ALL > 0) { ?>
			<td width='100px' class="<?echo $str_class_header?>">Status</td>
			<td class="<?echo $str_class_header?>">Balance</td>
		<? } ?>
	</tr>
	<tr>
		<td><input type="text" class="<?echo $str_class_input?>" name="account_number" <?if ($_SESSION['bill_id'] > -1) echo "readonly";?> value="<?echo $_SESSION['current_account_number']?>" onkeypress="focusNext(this, 'code', event, <? echo $_SESSION['current_bill_type']?>)" autocomplete="OFF"></td>
		<td>
			<? if ($_SESSION['bill_id'] == -1) { ?>
				<a href="javascript:openSearch(<?echo $_SESSION['current_bill_type']?>)"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
			<? } ?>
		<td class='<?echo $str_class_span?>'><span id="account_name" class="<?echo $str_class_span?>"><?echo $str_account_name;?></span></td>
		<? if (DOWNLOAD_ALL > 0) { ?>
			<td class='<?echo $str_class_span?>'><span id="account_status" class="<?echo $str_class_span?>"></span></td>
			<td class='<?echo $str_class_span?>'><span id="account_balance" class="<?echo $str_class_span?>"></span></td>
		<? } ?>
	</tr>
</table>

</form>
<script language="javascript">
		document.billing_account.account_number.focus();
</script>
</body>
</html>