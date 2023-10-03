<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_message = '';
	
	$int_id = 0;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			$str_is_taxed = 'N';
			if (IsSet($_POST['is_taxed']))
				$str_is_taxed = 'Y';

			$str_is_cash_taxed = 'N';
			if (IsSet($_POST['is_cash_taxed']))
				$str_is_cash_taxed = 'Y';

			$str_is_account_taxed = 'N';
			if (IsSet($_POST['is_account_taxed']))
				$str_is_account_taxed = 'Y';

			$str_can_bill_cash = 'N';
			if (IsSet($_POST['can_bill_cash']))
				$str_can_bill_cash = 'Y';

			$str_can_bill_fs_account = 'N';
			if (IsSet($_POST['can_bill_fs_account']))
				$str_can_bill_fs_account = 'Y';

			$str_can_bill_pt_account = 'N';
			if (IsSet($_POST['can_bill_pt_account']))
				$str_can_bill_pt_account = 'Y';

			$str_can_bill_creditcard = 'N';
			if (IsSet($_POST['can_bill_creditcard']))
				$str_can_bill_creditcard = 'Y';
				
			$str_can_bill_aurocard = 'N';
			if (IsSet($_POST['can_bill_aurocard']))
				$str_can_bill_aurocard = 'Y';

			$str_enabled_table_billing = 'N';
			if (IsSet($_POST['enabled_table_billing']))
				$str_enabled_table_billing = 'Y';
				
			if (IsSet($_POST['id'])) {
				$int_id = $_POST['id'];
				
				//***
				// check for duplicate storeroom code
				//***
				$qry_check = new Query("
					SELECT *
					FROM stock_storeroom
					WHERE (storeroom_code = '".addslashes($_POST['storeroom_code'])."')
						AND (storeroom_id <> $int_id)
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate storeroom code found';
				}
				else {
					$str_query = "
						UPDATE stock_storeroom
						SET
							storeroom_code = '".addslashes($_POST['storeroom_code'])."',
							description = '".addslashes($_POST['description'])."',
							location = '".addslashes($_POST['location'])."',
							bill_description = '".addslashes($_POST['bill_description'])."',
							bill_order_description = '".addslashes($_POST['bill_order_description'])."',
							bill_credit_account = '".addslashes($_POST['bill_credit_account'])."',
							default_tax_id = ".$_POST['default_tax_id'].",
							default_supplier_id = ".$_POST['default_supplier_id'].",
							default_category_id = ".$_POST['default_category_id'].",
							default_currency_id = ".$_POST['default_currency_id'].",
							default_unit_id = ".$_POST['default_unit_id'].",
							is_taxed = '$str_is_taxed',
							is_cash_taxed = '$str_is_cash_taxed',
							is_account_taxed = '$str_is_account_taxed',
							can_bill_cash = '$str_can_bill_cash',
							can_bill_fs_account = '$str_can_bill_fs_account',
							can_bill_pt_account = '$str_can_bill_pt_account',
							can_bill_creditcard = '$str_can_bill_creditcard',
							can_bill_aurocard = '$str_can_bill_aurocard',
							enabled_table_billing = '$str_enabled_table_billing'
						WHERE (storeroom_id = $int_id)
					";
					$qry = new Query($str_query);
					
					if ($qry->b_error == true) {
						$str_message = 'Error updating storeroom information';
						echo $str_query;
						die();
					}
					else {
						echo "<script language='javascript'>\n;";
						echo "if (top.window.opener)\n";
						echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
						echo "top.window.close();\n";
						echo "</script>";
					}
				}
			}
			else {
				//==================================
				// check for duplicate supplier code
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_storeroom
					WHERE (storeroom_code = '".addslashes($_POST['storeroom_code'])."')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate storeroom code found';
				}
				else {
					$str_query = "
						INSERT INTO stock_storeroom
						(
							storeroom_code,
							description,
							location, 
							bill_description,
							bill_order_description,
							bill_credit_account,
							default_tax_id,
							default_supplier_id,
							default_category_id,
							default_currency_id,
							default_unit_id,
							is_taxed,
							is_cash_taxed,
							is_account_taxed,
							can_bill_cash,
							can_bill_fs_account,
							can_bill_pt_account,
							can_bill_creditcard,
							can_bill_aurocard,
							enabled_table_billing
						)
						VALUES (
							'".addslashes($_POST['storeroom_code'])."',
							'".addslashes($_POST['description'])."',
							'".addslashes($_POST['location'])."',
							'".addslashes($_POST['bill_description'])."',
							'".addslashes($_POST['bill_order_description'])."',
							'".addslashes($_POST['bill_credit_account'])."',
							".$_POST['default_tax_id'].",
							".$_POST['default_supplier_id'].",
							".$_POST['default_category_id'].",
							".$_POST['default_currency_id'].",
							".$_POST['default_unit_id'].",
							'$str_is_taxed',
							'$str_is_cash_taxed',
							'$str_is_account_taxed',
							'$str_can_bill_cash',
							'$str_can_bill_fs_account',
							'$str_can_bill_pt_account',
							'$str_can_bill_creditcard',
							'$str_can_bill_aurocard',
							'$str_enabled_table_billing'
						)
					";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = 'Error inserting new storeroom';
						echo $str_query;
					}
					else {
						$int_storeroom_id = $qry->getInsertedID();
						
						//***
						// add a corresponding entry in the user_settings table
						//**
						$qry_user_settings = new Query("SELECT * FROM user_settings ORDER BY storeroom_id");
						$qry->Query("
							INSERT INTO user_settings
							(
								storeroom_id,
								bill_print_note,
								bill_print_lines_to_eject,
								bill_transfer_tax,
								bill_print_address,
								bill_print_phone,
								application_pid,
								application_pin,
								admin_product_type,
								bill_adjusted_enabled
							)
							VALUES (
								$int_storeroom_id,
								'".$qry_user_settings->FieldByName('bill_print_note')."',
								".$qry_user_settings->FieldByName('bill_print_lines_to_eject').",
								".$qry_user_settings->FieldByName('bill_transfer_tax').",
								'".$qry_user_settings->FieldByName('bill_print_address')."',
								'".$qry_user_settings->FieldByName('bill_print_phone')."',
								'".$qry_user_settings->FieldByName('application_pid')."',
								'".$qry_user_settings->FieldByName('application_pin')."',
								".$qry_user_settings->FieldByName('admin_product_type').",
								'".$qry_user_settings->FieldByName('bill_adjusted_enabled')."'
							)
						");
						
						echo "<script language='javascript'>\n;";
						echo "if (top.window.opener)\n";
						echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
						echo "top.window.close();\n";
						echo "</script>";
					}
				}
			}
		}
	}
	
	$str_storeroom_code = '';
	$str_description = '';
	$str_location = '';
	$str_is_taxed = 'Y';
	$str_bill_description = '';
	$str_bill_order_description = '';
	$str_bill_credit_account = '';
	$str_is_cash_taxed = 'Y';
	$str_is_account_taxed = 'Y';
	$str_can_bill_cash = 'Y';
	$str_can_bill_fs_account = 'Y';
	$str_can_bill_pt_account = 'N';
	$str_can_bill_creditcard = 'N';
	$str_can_bill_aurocard = 'N';
	$int_default_tax_id = 1;
	$int_default_supplier_id = 1;
	$int_default_category_id = 1;
	$int_default_currency_id = 1;
	$int_default_unit_id = 1;
	$str_enabled_table_billing = 'N';

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_storeroom
			WHERE storeroom_id = $int_id
		";
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_storeroom_code = $qry->FieldByName('storeroom_code');
			$str_description = $qry->FieldByName('description');
			$str_location = $qry->FieldByName('location');
			$str_bill_description = $qry->FieldByName('bill_description');
			$str_bill_order_description = $qry->FieldByName('bill_order_description');
			$str_bill_credit_account = $qry->FieldByName('bill_credit_account');
			$str_is_taxed = $qry->FieldByName('is_taxed');
			$str_is_cash_taxed = $qry->FieldByName('is_cash_taxed');
			$str_is_account_taxed = $qry->FieldByName('is_account_taxed');
			$str_can_bill_cash = $qry->FieldByName('can_bill_cash');
			$str_can_bill_fs_account = $qry->FieldByName('can_bill_fs_account');
			$str_can_bill_pt_account = $qry->FieldByName('can_bill_pt_account');
			$str_can_bill_creditcard = $qry->FieldByName('can_bill_creditcard');
			$str_can_bill_aurocard = $qry->FieldByName('can_bill_aurocard');
			$int_default_tax_id = $qry->FieldByName('default_tax_id');
			$int_default_supplier_id = $qry->FieldByName('default_supplier_id');
			$int_default_category_id = $qry->FieldByName('default_category_id');
			$int_default_currency_id = $qry->FieldByName('default_currency_id');
			$int_default_unit_id = $qry->FieldByName('default_unit_id');
			$str_enabled_table_billing = $qry->FieldByName('enabled_table_billing');
		}
	}
	
	/*
		list of taxes
	*/
	$qry_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);
	
	/*
		list of suppliers
	*/
	$qry_supplier = new Query("
		SELECT *
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");
	
	/*
		list of categories
	*/
	$qry_category = new Query("
		SELECT *
		FROM stock_category
		WHERE parent_category_id = 0
		ORDER BY category_description
	");
	
	/*
		list of currencies
	*/
	$qry_currency = new Query("
		SELECT *
		FROM stock_currency
		ORDER BY currency_name
	");
	
	/*
		list of measurement units
	*/
	$qry_unit = new Query("
		SELECT *
		FROM stock_measurement_unit
		ORDER BY measurement_unit
	");
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		
		function saveData() {1
			var can_save = true;
			
			if (can_save)
				document.storeroom_edit.submit();
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>

<body id='body_bgcolor' marginwidth=5 marginheight=5>

<form name='storeroom_edit' method='POST'>

<?
	if ($int_id > 0)
		echo "<input type='hidden' name='id' value='".$int_id."'>";
		
	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?	}

//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("600", "../images/blank.gif");
	
//===================
?>


<table width='100%' cellpadding="5" cellspacing="0">
	<tr>
		<td width='160px' align="right" class='normaltext_bold'>Code:</td>
		<td>
			<input type='text' name='storeroom_code' value='<?echo $str_storeroom_code?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Description:</td>
		<td>
			<input type='text' name='description' value='<?echo $str_description?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Location:</td>
		<td>
			<input type='text' name='location' value='<?echo $str_location?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Bill description:</td>
		<td>
			<input type='text' name='bill_description' value='<?echo $str_bill_description?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Bill Order Description:</td>
		<td>
			<input type='text' name='bill_order_description' value='<?echo $str_bill_order_description?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Credit FS Account:</td>
		<td>
			<input type='text' name='bill_credit_account' value='<?echo $str_bill_credit_account?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Default tax:</td>
		<td>
			<select name='default_tax_id' class='select_200'>
			<?
				for ($i=0; $i<$qry_tax->RowCount(); $i++) {
					if ($qry_tax->FieldByName('tax_id') == $int_default_tax_id)
						echo "<option value=".$qry_tax->FieldByName('tax_id')." selected>".$qry_tax->FieldByName('tax_description');
					else
						echo "<option value=".$qry_tax->FieldByName('tax_id').">".$qry_tax->FieldByName('tax_description');
					$qry_tax->Next();
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Default supplier:</td>
		<td>
			<select name='default_supplier_id' class='select_200'>
			<?
				for ($i=0; $i<$qry_supplier->RowCount(); $i++) {
					if ($qry_supplier->FieldByName('supplier_id') == $int_default_supplier_id)
						echo "<option value=".$qry_supplier->FieldByName('supplier_id')." selected>".$qry_supplier->FieldByName('supplier_name');
					else
						echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name');
					$qry_supplier->Next();
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Default category:</td>
		<td>
			<select name='default_category_id' class='select_200'>
			<?
				for ($i=0; $i<$qry_category->RowCount(); $i++) {
					if ($qry_category->FieldByName('category_id') == $int_default_category_id)
						echo "<option value=".$qry_category->FieldByName('category_id')." selected>".$qry_category->FieldByName('category_description');
					else
						echo "<option value=".$qry_category->FieldByName('category_id').">".$qry_category->FieldByName('category_description');
					$qry_category->Next();
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Default currency:</td>
		<td>
			<select name='default_currency_id' class='select_200'>
			<?
				for ($i=0; $i<$qry_currency->RowCount(); $i++) {
					if ($qry_currency->FieldByName('currency_id') == $int_default_currency_id)
						echo "<option value=".$qry_currency->FieldByName('currency_id')." selected>".$qry_currency->FieldByName('currency_name');
					else
						echo "<option value=".$qry_currency->FieldByName('currency_id').">".$qry_currency->FieldByName('currency_name');
					$qry_currency->Next();
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Default Unit:</td>
		<td>
			<select name='default_unit_id' class='select_200'>
			<?
				for ($i=0; $i<$qry_unit->RowCount(); $i++) {
					if ($qry_unit->FieldByName('measurement_unit_id') == $int_default_unit_id)
						echo "<option value=".$qry_unit->FieldByName('measurement_unit_id')." selected>".$qry_unit->FieldByName('measurement_unit');
					else
						echo "<option value=".$qry_unit->FieldByName('measurement_unit_id').">".$qry_unit->FieldByName('measurement_unit');
					$qry_unit->Next();
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='is_taxed' <?if ($str_is_taxed == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Is taxed</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='is_cash_taxed' <?if ($str_is_cash_taxed == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Tax cash transactions</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='is_account_taxed' <?if ($str_is_account_taxed == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Tax FS Account transactions</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='can_bill_cash' <?if ($str_can_bill_cash == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Enable Cash bills</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='can_bill_fs_account' <?if ($str_can_bill_fs_account == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Enable FS Account bills</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='can_bill_pt_account' <?if ($str_can_bill_pt_account == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Enable PT Account bills</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='can_bill_creditcard' <?if ($str_can_bill_creditcard == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Enable Credit Card bills</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='can_bill_aurocard' <?if ($str_can_bill_aurocard == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Enable Aurocard bills</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='checkbox' name='enabled_table_billing' <?if ($str_enabled_table_billing == 'Y') echo "checked";?>>
		</td>
		<td class='normaltext'>Enable table field at billing</td>
	</tr>
</table>

<table cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td>
			<input type='hidden' name='action' value='save'>
			<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
		</td>
		<td>
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="closeWindow()">
		</td>
		<td>&nbsp;</td>
	</tr>
</table>

<?
//=================
// bounding box end
//-----------------
    boundingBoxEnd("600", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//===================
?>

</form>
</body>
</html>