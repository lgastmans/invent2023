<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("pt_accounts_funcs.inc.php");
	
	$str_message = '';

	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
	 
			$can_save = true;
			
			if (!empty($_POST["account_number"]))
				$cur_account_number = $_POST["account_number"];
			else {
				$can_save = false;
				$str_message = "Account number cannot be blank";
			}
			
			if (IsSet($_POST["account_enabled"])) {
				if ($_POST["account_enabled"] == 'on') {
					$bool_enabled = 'Y';
				}
				else
					$bool_enabled = 'N';
				}
			else
				$bool_enabled = 'N';
			
			if (IsSet($_POST["opening_balance"])) {
				if (empty($_POST["opening_balance"]))
					$_POST["opening_balance"] = 0;
			}
			else
				$_POST["opening_balance"] = 0;
			
			if (IsSet($_POST['id'])) {
				//=====
				// edit
				//----------------------
				// verify account number
				//----------------------
				if ($_POST["flt_prev_account_number"] != $_POST["account_number"]) {
					$qry = new Query("
						SELECT *
						FROM account_pt
						WHERE (account_number = ".$_POST["account_number"].")
					");
					if ($qry->RowCount() > 0) {
						$can_save = false;
						$str_message = "Account number already exists for ".$qry->FieldByName('account_name');
					}
				}
				
				if ($can_save) {
					$str_query ="
						UPDATE account_pt
						SET account_name = '".$_POST["account_name"]."',
							account_number = '".$_POST["account_number"]."',
							enabled = '".$bool_enabled."',
							community_id = ".$_POST["list_community"].",
							partner = '".$_POST['partner']."',
							gender = '".$_POST['gender']."',
							notes = '".$_POST['notes']."',
							category_id = ".$_POST['category'].",
							DOB = '".set_mysql_date($_POST['DOB'], "-")."',
							family_name = '".$_POST['family_name']."',
							mothers_name = '".$_POST['mothers_name']."',
							nationality_id = ".$_POST['nationality'].",
							status_id = ".$_POST['status']."
						WHERE (account_id = ".$_POST["id"].")";
					$qry_save = new Query($str_query);
					
					if ($qry_save->b_error == true) {
						$str_message = "error updating account_pt: ".mysql_error();
					}
				
					if ($_POST["flt_prev_opening_balance"] != $_POST["opening_balance"]) {
						
						$flt_closing_balance = getClosingBalance($_POST["id"], $_POST["opening_balance"]);
						
						// first check if an entry exists in the account_pt_transfers
						$str_save = "
							SELECT *
							FROM ".Monthalize('account_pt_balances')."
							WHERE (account_id = ".$_POST["id"].")";
						$qry_save->Query($str_save);
						
						if ($qry_save->RowCount() > 0) {
							$str_save = "
								UPDATE ".Monthalize('account_pt_balances')."
								SET opening_balance = ".$_POST["opening_balance"].", 
								closing_balance = ".$flt_closing_balance."
								WHERE (account_id = ".$_POST["id"].")";
							$qry_save->Query($str_save);
							if ($qry_save->b_error == true) {
								$str_message = "error updating opening balance record ".$str_save;
							}
						} else {
							$str_save = "
								INSERT INTO ".Monthalize('account_pt_balances')."
								(account_id,
								opening_balance,
								closing_balance)
								VALUES(".
								$_POST["id"].", ".
								$_POST["opening_balance"].", ".
								$_POST["opening_balance"].")";
							$qry_save->Query($str_save);
							if ($qry_save->b_error == true) {
								$str_message = "error creating opening balance record ".$str_save;
							}
						}
					}
				}
			}
			else {
				//====
				// new
				//----
				// verify account number
				$qry = new Query("
					SELECT *
					FROM account_pt
					WHERE (account_number = '".$cur_account_number."')
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
							enabled,
							community_id,
							partner,
							gender,
							notes,
							category_id,
							DOB,
							family_name,
							mothers_name,
							nationality_id,
							status_id
						)
						VALUES (
							'".$_POST["account_name"]."',
							'".$_POST["account_number"]."',
							'".$bool_enabled."',
							".$_POST["list_community"].",
							'".$_POST['partner']."',
							'".$_POST['gender']."',
							'".$_POST['notes']."',
							".$_POST['category'].",
							'".set_mysql_date($_POST['DOB'], '-')."',
							'".$_POST['family_name']."',
							'".$_POST['mothers_name']."',
							".$_POST['nationality'].",
							".$_POST['status']."
						)";
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
	}
	
	$str_name = '';
	$str_number = '';
	$str_partner = '';
	$str_gender = '';
	$str_notes = '';
	$int_category_id = -1;
	$str_DOB = date('d-m-Y', time());
	$str_family_name = '';
	$str_mothers_name = '';
	$int_nationality_id = -1;
	$int_status_id = -1;
	$bool_enabled = true;
	$int_community_id = -1;
	$flt_opening_balance = 0;
	
	$int_id = -1;
	if (IsSet($_GET["id"])) {
		$int_id = $_GET['id'];
		
		$qry = new Query("
			SELECT *
			FROM
				account_pt ac
			LEFT JOIN communities c ON (ac.community_id = c.community_id)
			LEFT JOIN account_pt_category apc ON (apc.category_id = ac.category_id)
			LEFT JOIN account_pt_status aps ON (aps.status_id = ac.status_id)
			LEFT JOIN account_pt_nationality apn ON (apn.nationality_id = ac.nationality_id)
			LEFT JOIN ".Monthalize('account_pt_balances')." ab ON (ac.account_id = ab.account_id)
			WHERE (ac.account_id = $int_id)
		");
		if ($qry->RowCount() == 0) {
			$str_message = "ERROR: Account not found.";
		}
		
		$str_name = $qry->FieldByName('account_name');
		$str_number = $qry->FieldByName('account_number');
		$str_partner = $qry->FieldByName('partner');
		$str_gender = $qry->FieldByName('gender');
		$str_notes = $qry->FieldByName('notes');
		$int_category_id = $qry->FieldByName('category_id');
		$str_DOB = set_formatted_date($qry->FieldByName('DOB'), "-");
		$str_family_name = $qry->FieldByName('family_name');
		$str_mothers_name = $qry->FieldByName('mothers_name');
		$int_nationality_id = $qry->FieldByName('nationality_id');
		$int_status_id = $qry->FieldByName('status_id');
		if ($qry->FieldByName('enabled') == 'Y')
			$bool_enabled = true;
		else
			$bool_enabled = false;
		$int_community_id = $qry->FieldByName('community_id');
		$flt_opening_balance = $qry->FieldByName('opening_balance');
		
		$flt_prev_account_number = $qry->FieldByName('account_number');
		$flt_prev_opening_balance = $qry->FieldByName('opening_balance');
		if (empty($flt_prev_opening_balance))
			$flt_prev_opening_balance = 0;
	}
	
	// list of communities
	$result_community = new Query("
		SELECT *
		FROM communities
		ORDER BY community_name
	");
	
	// list of categories
	$qry_categories = new Query("
		SELECT category_id, category, description, amount
		FROM account_pt_category
		ORDER BY category
	");
	
	// list of statuses
	$qry_status = new Query("
		SELECT *
		FROM account_pt_status
		ORDER BY status
	");
	
	// list of nationalities
	$qry_nationality = new Query("
		SELECT *
		FROM account_pt_nationality
		ORDER BY nationality
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
		var oDOB = document.account_edit.DOB;
		var can_save = true;
		
		if (!isDate(oDOB.value)) {
			can_save = false;
			alert('invalid date');
			oDOB.focus;
		}
		
		if (can_save)
			document.account_edit.submit();
	}

	// returns true if the string is a valid date formatted as...
	// mm dd yyyy, mm/dd/yyyy, mm.dd.yyyy, mm-dd-yyyy
	function isDate(str){
		if (str == '00-00-0000')
			return true;
			
		var re = /^(\d{1,2})[\s\.\/-](\d{1,2})[\s\.\/-](\d{4})$/
		if (!re.test(str)) return false;
		var result = str.match(re);
		var d = parseInt(result[1],10);
		var m = parseInt(result[2],10);
		var y = parseInt(result[3],10);
		if(m < 1 || m > 12 || y < 1900 || y > 2100) return false;
		if(m == 2){
			var days = ((y % 4) == 0) ? 29 : 28;
		}else if(m == 4 || m == 6 || m == 9 || m == 11){
			var days = 30;
		}else{
			var days = 31;
		}
		return (d >= 1 && d <= days);
	}
	
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxName = document.account_edit.account_name;
		var oTextBoxNumber = document.account_edit.account_number;
		var oTextBoxStatus = document.account_edit.account_status;
		var oTextBoxEnabled = document.account_edit.account_enabled;
		var oListBoxCommunity = document.account_edit.list_community;
		var oTextBoxOpBal = document.account_edit.opening_balance;
		var oButtonSave = document.account_edit.Save;
	
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
		var oTextBoxName = document.account_edit.account_name;
		var oTextBoxNumber = document.account_edit.account_number;
		var oTextBoxStatus = document.account_edit.account_status;
		var oListBoxCommunity = document.account_edit.list_community;
		var oTextBoxOpBal = document.account_edit.opening_balance;
    
    oTextBoxName.value = '';
    oTextBoxNumber.value = '';
    oTextBoxStatus.value = '';
    oListBoxCommunity.selectedIndex = 0;
    oTextBoxOpBal.value = '0';
	}
</script>

<body>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=10 topmargin=10>

<form name="account_edit" method="POST" onsubmit="return false">

<? if ($str_message != '') echo "<font color=\"red\">".$str_message."</font>";?>

	<table width="100%" height="30" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td class='normaltext' align="right" width="120"><font color='red'>*</font> Name</td>
			<td><input type="text" name="account_name" class='input_200' value="<? echo $str_name;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_number', event)" onkeyup="setUppercase(this)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Number</td>
			<td><input type="text" name="account_number" class='input_200' value="<? echo $str_number;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Partner</td>
			<td><input type="text" name="partner" class='input_200' value="<? echo $str_partner;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Gender</td>
			<td><input type="text" name="gender" class='input_200' value="<? echo $str_gender;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' valign='top' align="right">Notes</td>
			<td>
				<textarea class='textarea' name='notes' rows='3' cols='25'><?echo addslashes($str_notes);?></textarea>
			</td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Category</td>
			<td>
				<select name='category' class='select_200'>
				<?
					for ($i=0;$i<$qry_categories->RowCount();$i++) {
						if ($int_category_id == $qry_categories->FieldByName('category_id'))
							echo "<option value=".$qry_categories->FieldByName('category_id')." selected>".$qry_categories->FieldByName('category').", ".$qry_categories->FieldByName('description').", ".$qry_categories->FieldByName('amount');
						else
							echo "<option value=".$qry_categories->FieldByName('category_id').">".$qry_categories->FieldByName('category').", ".$qry_categories->FieldByName('description').", ".$qry_categories->FieldByName('amount');
						$qry_categories->Next();
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class='normaltext' align="right"><font color='red'>*</font> Date of birth<br>(dd-mm-YYYY)</td>
			<td><input type="text" name="DOB" class='input_200' value="<? echo $str_DOB;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Family Name</td>
			<td><input type="text" name="family_name" class='input_200' value="<? echo $str_family_name;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Mother's Name</td>
			<td><input type="text" name="mothers_name" class='input_200' value="<? echo $str_mothers_name;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Nationality</td>
			<td>
				<select name='nationality' class='select_200'>
				<?
					for ($i=0;$i<$qry_nationality->RowCount();$i++) {
						if ($int_nationality_id == $qry_nationality->FieldByName('nationality_id'))
							echo "<option value=".$qry_nationality->FieldByName('nationality_id')." selected>".$qry_nationality->FieldByName('nationality');
						else
							echo "<option value=".$qry_nationality->FieldByName('nationality_id').">".$qry_nationality->FieldByName('nationality');
						$qry_nationality->Next();
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Status</td>
			<td>
				<select name='status' class="select_200">
				<?
					for ($i=0;$i<$qry_status->RowCount();$i++) {
						if ($int_status_id == $qry_status->FieldByName('status_id'))
							echo "<option value=".$qry_status->FieldByName('status_id')." selected>".$qry_status->FieldByName('status');
						else
							echo "<option value=".$qry_status->FieldByName('status_id').">".$qry_status->FieldByName('status');
						$qry_status->Next();
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Enabled</td>
			<? if ($bool_enabled == 'Y') { ?>
				<td><input type="checkbox" name="account_enabled" checked="checked" onkeypress="focusNext(this, 'list_community', event)"></td>
			<? } else { ?>
				<td><input type="checkbox" name="account_enabled" onkeypress="focusNext(this, 'list_community', event)"></td>
			<? } ?>
		</tr>
		<tr>
			<td class='normaltext' align="right">Community</td>
			<td>
				<select name="list_community" class='select_200' onkeypress="focusNext(this, 'opening_balance', event)">
				<?
				for ($i=0;$i<$result_community->RowCount();$i++) {
					if ($int_community_id == $result_community->FieldByName('community_id'))
						echo "<option value=".$result_community->FieldByName("community_id")." selected=\"selected\">".$result_community->FieldByName('community_name');
					else
						echo "<option value=".$result_community->FieldByName("community_id").">".$result_community->FieldByName('community_name');
					$result_community->Next();
				}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Opening Balance</td>
			<td>
				<input type="text" name="opening_balance" class='input_100' value="<? echo $flt_opening_balance;?>" autocomplete="OFF" onkeypress="focusNext(this, 'button_save', event)">
			</td>
		</tr>
		<tr>
			<td align="right">
				<input type="button" name="Save" value="Save" class='settings_button' onclick="save_data()">
			</td>
			<td>
				<input type="button" name="Close" value="Close" class='settings_button' onclick="CloseWindow()">
			</td>
		</tr>
		<tr>
			<TD colspan='2' class='normaltext'><font color='red'><br>*</font> required fields</TD>
		</tr>
	</table>
	
	<? if ($int_id > -1) { ?>
		<input type="hidden" name="id" value="<?echo $int_id;?>">
	<? } ?>
	<input type="hidden" name="action" value="save">
	<input type="hidden" name="flt_prev_account_number" value="<?echo $flt_prev_account_number;?>">
	<input type="hidden" name="flt_prev_opening_balance" value="<?echo $flt_prev_opening_balance;?>">
</form>

<script language="javascript">
  document.account_edit.account_name.focus();
</script>

</body>
</html>