<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
  $str_message = '';
  
	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
		
			$can_save = true;
			
			if (!empty($_POST["account_number"]))
				$cur_account_number = $_POST["account_number"];
			else {
				$cur_account_number = "0";
				$can_save = false;
				$str_message = "Account number cannot be blank";
			}
			
			if (IsSet($_POST["account_enabled"]))
				$bool_enabled = 'Y';
			else
				$bool_enabled = 'N';
				
			// verify account number
			$qry = new Query("
				SELECT *
				FROM account_pt
				WHERE (account_number = ".$cur_account_number.")
			");
			if ($qry->RowCount() > 0) {
				$can_save = false;
				$str_message = "account number already exists for ".$qry->FieldByName('account_name');
			}
			
			if ($can_save) {
				$str_query ="
					INSERT INTO account_pt
					(
						account_name,
						account_number,
						account_status,
						enabled,
						community_id
					)
					VALUES ('".
						$_POST["account_name"]."', '".
						$_POST["account_number"]."', '".
						$_POST["account_status"]."', '".
						$bool_enabled."', ".
						$_POST["list_community"].
					")";
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					$str_message = "error inserting into account_pt ".$str_query;
				}
				$int_account_id = $qry->getInsertedID();
				
				$qry->Query("
					INSERT INTO ".Monthalize('account_pt_balances')."
					(
						account_id,
						opening_balance,
						closing_balance
					)
					VALUES(".
						$int_account_id.", ".
						$_POST["opening_balance"].", ".
						$_POST["opening_balance"]."
					)
				");
				if ($qry->b_error == true) {
					$str_message = "error creating opening balance record";
				}
			}
		}
	}
	
	// list of communities
	$result_community = new Query("
		SELECT *
		FROM communities
		ORDER BY community_name
	");
	
?>

<script language="javascript">

	function setUppercase(aField) {
		aField.value = aField.value.toUpperCase();
	}

	function CloseWindow() {
    window.opener.document.location=window.opener.document.location.href;
    window.close();
	}

  function save_data() {
    account_new.submit();
  }
  
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxName = document.account_new.account_name;
		var oTextBoxNumber = document.account_new.account_number;
		var oTextBoxStatus = document.account_new.account_status;
		var oTextBoxEnabled = document.account_new.account_enabled;
		var oListBoxCommunity = document.account_new.list_community;
		var oTextBoxOpBal = document.account_new.opening_balance;
		var oButtonSave = document.account_new.Save;
		
	
		if (charCode == 13 || charCode == 3) {
			if (focusElem == 'account_number') {
				oTextBoxNumber.select();
			}
			else if (focusElem == 'account_status') {
			 oTextBoxStatus.select();
			}
			else if (focusElem == 'account_enabled') {
			 oTextBoxEnabled.focus();
			}
			else if (focusElem == 'list_community') {
			 oListBoxCommunity.focus();
			}
			else if (focusElem == 'opening_balance') {
        oTextBoxOpBal.focus();
			}
			else if (focusElem == 'button_save') {
			 oButtonSave.focus();
			}
		} else if (charCode == 27) {
			oTextBoxName.select();
			clearValues;
		}
		return false;
	}  
	
	function clearValues() {
		var oTextBoxName = document.account_new.account_name;
		var oTextBoxNumber = document.account_new.account_number;
		var oTextBoxStatus = document.account_new.account_status;
		var oListBoxCommunity = document.account_new.list_community;
		var oTextBoxOpBal = document.account_new.opening_balance;
    
    oTextBoxName.value = '';
    oTextBoxNumber.value = '';
    oTextBoxStatus.value = '';
    oListBoxCommunity.selectedIndex = 0;
    oTextBoxOpBal.value = '0';
	}
</script>

<html>
<body leftmargin=5 topmargin=5 marginwidth=0 marginheight=0 bgcolor="#DADADA">

<form name="account_new" method="POST" onsubmit="return false">

<? if ($str_message != '') echo "<font color=\"red\">".$str_message."</font>";?>

	<table width="100%" height="30" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td align="right" width="120">Name</td>
			<td><input type="text" name="account_name" value="" autocomplete="OFF" onkeypress="focusNext(this, 'account_number', event)" onkeyup="setUppercase(this)"></td>
		</tr>
		<tr>
		  <td align="right">Number</td>
		  <td><input type="text" name="account_number" value="" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
		  <td align="right">Status</td>
		  <td><input type="text" name="account_status" value="" autocomplete="OFF" onkeypress="focusNext(this, 'account_enabled', event)"></td>
		</tr>
		<tr>
		  <td align="right">Enabled</td>
		  <td><input type="checkbox" name="account_enabled" checked="checked" onkeypress="focusNext(this, 'list_community', event)"></td>
		</tr>
		<tr>
		  <td align="right">Community</td>
		  <td><select name="list_community" onkeypress="focusNext(this, 'opening_balance', event)">
		    <?
		      for ($i=0;$i<$result_community->RowCount();$i++) {
		        echo "<option value=".$result_community->FieldByName("community_id").">".$result_community->FieldByName('community_name');
		        $result_community->Next();
		      }
		    ?>
		    </select>
      </td>
		</tr>
		<tr>
		  <td align="right">Opening Balance</td>
		  <td><input type="text" name="opening_balance" value="0" autocomplete="OFF" onkeypress="focusNext(this, 'button_save', event)"></td>
		</tr>
		<tr>
		  <td align="right">
		    <input type="hidden" name="action" value="save">
	     <input type="button" name="Save" value="Save" onclick="save_data()">
	    </td>
	     <td>
      <input type="button" name="Close" value="Close" onclick="CloseWindow()">
      </td>
    </tr>
	</table>
</form>

<script language="javascript">
  document.account_new.account_name.focus();
</script>

</body>
</html>