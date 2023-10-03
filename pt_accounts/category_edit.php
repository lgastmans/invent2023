<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("common_funcs.inc.php");
	
	$str_message = '';

	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
	 
			$can_save = true;
			
			if (IsSet($_POST['id'])) {
				//=====
				// edit
				//-----
				$int_id = $_POST["id"];
				
				if ($can_save) {
					$str_query ="
						UPDATE account_pt_category
						SET category = '".$_POST["category"]."',
							description = '".$_POST["description"]."',
							amount = ".$_POST['amount']."
						WHERE (category_id = $int_id)";
					$qry_save = new Query($str_query);
					
					if ($qry_save->b_error == true) {
						$str_message = "error updating: ".mysql_error();
					}
					else {
						echo "<script language='javascript'>";
						echo "window.opener.document.location=window.opener.document.location.href;";
						echo "window.close();";
						echo "</script>";
					}
				}
			}
			else {
				//====
				// new
				//----
				if ($can_save) {
					$str_query ="
						INSERT INTO account_pt_category
						(
							account_name,
							account_number,
							enabled
						)
						VALUES (
							'".$_POST["category"]."',
							'".$_POST["description"]."',
							".$_POST["amount"]."
						)";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = "error inserting: ".$str_query;
					}
					else {
						echo "<script language='javascript'>";
						echo "window.opener.document.location=window.opener.document.location.href;";
						echo "window.close();";
						echo "</script>";
					}
					$int_account_id = $qry->getInsertedID();
				}
			}
		}
	}
	
	$str_category = '';
	$str_description = '';
	$flt_amount = 0;
	
	$int_id = -1;
	if (IsSet($_GET["id"])) {
		$int_id = $_GET['id'];
		
		$qry = new Query("
			SELECT *
			FROM account_pt_category apc
			WHERE (category_id = $int_id)
		");
		if ($qry->RowCount() == 0) {
			$str_message = "ERROR: Category not found.";
		}
		
		$str_category = $qry->FieldByName('category');
		$str_description = $qry->FieldByName('description');
		$flt_amount = $qry->FieldByName('amount');
	}
	
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
		document.account_edit.submit();
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
			<td class='normaltext' align="right" width="120">Category</td>
			<td><input type="text" name="category" class='input_200' value="<? echo $str_category;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_number', event)" onkeyup="setUppercase(this)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Description</td>
			<td><input type="text" name="description" class='input_200' value="<? echo $str_description;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td class='normaltext' align="right">Amount</td>
			<td><input type="text" name="amount" class='input_200' value="<? echo $flt_amount;?>" autocomplete="OFF" onkeypress="focusNext(this, 'account_status', event)"></td>
		</tr>
		<tr>
			<td align="right">
				<input type="button" name="Save" value="Save" class='settings_button' onclick="save_data()">
			</td>
			<td>
				<input type="button" name="Close" value="Close" class='settings_button' onclick="CloseWindow()">
			</td>
		</tr>
	</table>
	
	<? if ($int_id > -1) { ?>
		<input type="hidden" name="id" value="<?echo $int_id;?>">
	<? } ?>
	<input type="hidden" name="action" value="save">
</form>

<script language="javascript">
  document.account_edit.category.focus();
</script>

</body>
</html>