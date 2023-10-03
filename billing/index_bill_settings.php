<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$_SESSION["int_bills_menu_selected"] = 9;

	// list of tax categories
	$qry_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);

	$str_message = '';

	if (IsSet($_POST["action"])) {

		if ($_POST["action"] == "save") {
		
			if (IsSet($_POST["bill_print_batch"]))
				$str_print_batch = 'Y';
			else
				$str_print_batch = 'N';
			
			if (IsSet($_POST["bill_print_supplier_abbreviation"]))
				$str_print_abbreviation = 'Y';
			else
				$str_print_abbreviation = 'N';
			
			if (IsSet($_POST['bill_enable_batches']))
				$str_enable_batches = 'Y';
			else
				$str_enable_batches = 'N';
		
			if (IsSet($_POST['calculate_tax_before_discount']))
				$str_calculate_before = 'Y';
			else
				$str_calculate_before = 'N';

			if (IsSet($_POST['display_messages']))
				$str_display_messages = 'Y';
			else
				$str_display_messages = 'N';

			$int_default_discount = intval($_POST['default_discount']);
		
			$str_update = "
				UPDATE user_settings
				SET bill_print_note = '".addslashes($_POST["bill_note"])."',
					bill_print_note_2 = '".addslashes($_POST["bill_note_2"])."',
					bill_print_note_3 = '".addslashes($_POST["bill_note_3"])."',
					bill_print_lines_to_eject = ".$_POST["bill_lines"].",
					bill_print_address = '".addslashes($_POST["bill_address"])."',
					bill_print_phone = '".addslashes($_POST["bill_phone"])."',
					bill_print_batch = '".$str_print_batch."',
					bill_closing_time = '".$_POST['select_time']."',
					bill_print_supplier_abbreviation = '".$str_print_abbreviation."',
					bill_enable_batches = '".$str_enable_batches."',
					bill_default_discount = ".$int_default_discount.",
					bill_display_messages = '".$str_display_messages."',
					bill_transfer_tax = ".$_POST['select_transfer_tax']."
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			
			
			$qry_update = new Query($str_update);
			
			$str_message = 'Settings saved';
		}
	}

	$sql = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
  
?>
<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>

<script language="javascript">
  function saveSettings() {
    bill_settings.submit();
  }
</script>

<body bgcolor="#E9ECF1">
<form name="bill_settings" method="POST">
  <br>
  <br>
  <table border="0" cellpadding="5" cellspacing="0" width="80%">
    <tr>
      <td>
	&nbsp;
      </td>
      <td class="headertext">
	<font style="color:olive;font-weight:bold;"><span id='message' name='message'></span></font>
      </td>
      <td>&nbsp;</td>
    </tr>

    <tr>
      <td class="<?echo $str_class_header?>" align='right'>
        Address to print on bill: 
      </td>
      <td>
        <input type="text" name="bill_address" value="<?echo $sql->FieldByName('bill_print_address')?>" class="<?echo $str_class_input300?>" autocomplete="OFF">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="<?echo $str_class_header?>" align='right'>
        Phone number to print on bill: 
      </td>
      <td>
        <input type="text" name="bill_phone" value="<?echo $sql->FieldByName('bill_print_phone')?>" class="<?echo $str_class_input300?>" autocomplete="OFF">
      </td>
      <td>&nbsp;</td>
    </tr>

    <tr>
      <td class="<?echo $str_class_header?>" align='right'>
        Note to print at end of bill: 
      </td>
      <td>
        <input type="text" name="bill_note" value="<?echo $sql->FieldByName('bill_print_note')?>" class="<?echo $str_class_input300?>" autocomplete="OFF">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="<?echo $str_class_header?>" align='right'>
        Note 2: 
      </td>
      <td>
        <input type="text" name="bill_note_2" value="<?echo $sql->FieldByName('bill_print_note_2')?>" class="<?echo $str_class_input300?>" autocomplete="OFF">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="<?echo $str_class_header?>" align='right'>
        Note 3: 
      </td>
      <td>
        <input type="text" name="bill_note_3" value="<?echo $sql->FieldByName('bill_print_note_3')?>" class="<?echo $str_class_input300?>" autocomplete="OFF">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="<?echo $str_class_header?>" align='right'>
        Closing Time: 
      </td>
      <td>
	<select name="select_time" class="<?echo $str_class_select?>">>
            <option value="11:00:00" <?if ($sql->FieldByName('bill_closing_time') == '11:00:00') echo "selected"; ?>>11:00 AM
            <option value="11:30:00" <?if ($sql->FieldByName('bill_closing_time') == '11:30:00') echo "selected"; ?>>11:30 AM
            <option value="12:00:00" <?if ($sql->FieldByName('bill_closing_time') == '12:00:00') echo "selected"; ?>>12:00 PM
            <option value="12:30:00" <?if ($sql->FieldByName('bill_closing_time') == '12:30:00') echo "selected"; ?>>12:30 PM
            <option value="13:00:00" <?if ($sql->FieldByName('bill_closing_time') == '13:00:00') echo "selected"; ?>>1:00 PM
            <option value="13:30:00" <?if ($sql->FieldByName('bill_closing_time') == '13:30:00') echo "selected"; ?>>1:30 PM
            <option value="13:55:00" <?if ($sql->FieldByName('bill_closing_time') == '13:55:00') echo "selected"; ?>>1:55 PM
            <option value="14:00:00" <?if ($sql->FieldByName('bill_closing_time') == '14:00:00') echo "selected"; ?>>2:00 PM
	</select>
      </td>
      <td>&nbsp;</td>
    </tr>
    
<? //========== blank lines to print ==========?>
	<tr>
		<td class="<?echo $str_class_header?>" align='right'>
			Number of blank lines after bill:
		</td>
		<td>
			<input type="text" name="bill_lines" value="<? echo $sql->FieldByName('bill_print_lines_to_eject')?>" class="<?echo $str_class_input?>" autocomplete="OFF">
		</td>
		<td>&nbsp;</td>
	</tr>

<? //========== default discount ==========?>
	<tr>
		<td class="<?echo $str_class_header?>" align='right'>
			Default discount when billing:
		</td>
		<td>
			<input type="text" name="default_discount" value="<?echo $sql->FieldByName('bill_default_discount')?>" class="<?echo $str_class_input?>" autocomplete="OFF">
		</td>
		<td>&nbsp;</td>
	</tr>

<? //========== print batches ==========?>
	<tr>
		<td class="<?echo $str_class_header?>">
			&nbsp;
		</td>
		<td>
			<input type="checkbox" name="bill_print_batch" <? if ($sql->FieldByName('bill_print_batch') == 'Y') echo "checked";?> autocomplete="OFF"><font class="headertext"> Print the batch code in the bill</font>
		</td>
		<td>&nbsp;</td>
	</tr>


<? //========== print supplier abbreviation ==========?>
	<tr>
		<td class="<?echo $str_class_header?>">
			&nbsp;
		</td>
		<td>
			<input type="checkbox" name="bill_print_supplier_abbreviation" <? if ($sql->FieldByName('bill_print_supplier_abbreviation') == 'Y') echo "checked";?> autocomplete="OFF"><font class="headertext"> Print the supplier abbreviation in the bill</font>
		</td>
		<td>&nbsp;</td>
	</tr>

<? //========== enable batches ==========?>
	<tr>
		<td class="<?echo $str_class_header?>">
			&nbsp;
		</td>
		<td>
			<input type="checkbox" name="bill_enable_batches" <? if ($sql->FieldByName('bill_enable_batches') == 'Y') echo "checked";?> autocomplete="OFF"><font class="headertext"> Enable batches when billing</font>
		</td>
		<td>&nbsp;</td>
	</tr>

<? //========== tax before discount ==========?>
	<tr>
		<td class="<?echo $str_class_header?>">
			&nbsp;
		</td>
		<td>
			<input disabled type="checkbox" name="calculate_tax_before_discount" <? if ($sql->FieldByName('calculate_tax_before_discount') == 'Y') echo "checked";?> autocomplete="OFF"><font class="headertext"> Calculate tax before discount</font>
		</td>
		<td>&nbsp;</td>
	</tr>

<? //========== display warning messages when billing ==========?>
	<tr>
		<td class="<?echo $str_class_header?>">
			&nbsp;
		</td>
		<td>
			<input type="checkbox" name="display_messages" <? if ($sql->FieldByName('bill_display_messages') == 'Y') echo "checked";?> autocomplete="OFF"><font class="headertext"> Display warning messages when billing</font>
		</td>
		<td>&nbsp;</td>
	</tr>

<? //========== tax to save for Transfer of Goods ==========?>
	<tr>
		<td class="<?echo $str_class_header?>" align='right'>
			Tax for Transfer of Goods:
		</td>
		<td>
			<select name="select_transfer_tax">
			<?
				for ($i=0; $i<$qry_tax->RowCount(); $i++) {
					if ($sql->FieldByName('bill_transfer_tax') == $qry_tax->FieldByName('tax_id'))
						echo "<option value=".$qry_tax->FieldByName('tax_id')." selected>".$qry_tax->FieldByName('tax_description');
					else
						echo "<option value=".$qry_tax->FieldByName('tax_id').">".$qry_tax->FieldByName('tax_description');
					$qry_tax->Next();
				}
			?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>

    <tr>
      <td>&nbsp;</td>
      <td>
        <? if ($_SESSION["int_user_type"] > 1) { ?>
		<input type="button" class="v3button" name="Save" value="Save" onclick="saveSettings()">
		<input type="hidden" name="action" value="save">
	<? } ?>
      </td>
      <td>&nbsp;</td>
    </tr>
    
  </table>

<? if ($str_message <> '') { ?>
<script language='javascript'>
  var oSpan = document.getElementById('message');
  oSpan.innerHTML = '<? echo $str_message; ?>';
  setTimeout("oSpan.innerHTML = ''", 5000);
</script>
<? } ?>

</form>  
</body>
</html>