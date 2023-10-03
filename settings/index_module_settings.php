<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$int_access_level = (getModuleAccessLevel('Admin'));
	
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	} 
	
	$_SESSION['int_settings_menu_selected']=3;
	
	$bool_can_update = false;
	if (($int_access_level > 2) && ($_SESSION["int_user_type"] > 1))
		$bool_can_update = true;
	
	$qry_module = new Query("SELECT * FROM module LIMIT 1");
	
	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "Save Settings") {
			/*
				the following settings get saved in the config.ini file
				located in the "include" folder
			*/
			$templateSection = $arrConfig->getItem("section", 'billing');
			
			if (IsSet($_POST["display_abbreviation"]))
				$str_display_abbreviation = 'Y';
			else
				$str_display_abbreviation = 'N';
				

			if ($templateSection === false) {
				// create section
				$settingsSection = $arrConfig->createSection('billing');
				
				// create variables/values
				$settingsSection->createDirective("connect_method", intval($_POST['connect_method']));
				$settingsSection->createDirective("print_copies", intval($_POST['print_copies']));
				$settingsSection->createDirective("display_abbreviation", $str_display_abbreviation);
			}
			else {
				$connect_method_directive =& $templateSection->getItem("directive", "connect_method");
				$connect_method_directive->setContent(intval($_POST['connect_method']));
				
				$print_copies_directive =& $templateSection->getItem("directive", "print_copies");
				$print_copies_directive->setContent(intval($_POST['print_copies']));
				
				$display_abbreviation_directive =& $templateSection->getItem("directive", "display_abbreviation");
				$display_abbreviation_directive->setContent($str_display_abbreviation);

			}
			$res = $config->writeConfig($str_root."include/config.ini", "IniFile");
			if ($res!=1)
				print_r($res);
			
			/*
				the following settings get written to the database
			*/
			if (IsSet($_POST["is_equal_prices"]))
				$str_is_equal_prices = 'Y';
			else
				$str_is_equal_prices = 'N';
			
			if (IsSet($_POST['cb_show_returned']))
				$str_show_returned = 'Y';
			else
				$str_show_returned = 'N';
			
			if (IsSet($_POST['cb_show_available']))
				$str_show_available = 'Y';
			else
				$str_show_available = 'N';
			
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
			
			if (IsSet($_POST['display_messages']))
				$str_display_messages = 'Y';
			else
				$str_display_messages = 'N';
			
			if (IsSet($_POST['cb_print_bill']))
				$str_order_print_bill = 'Y';
			else
				$str_order_print_bill = 'N';
			
			if (IsSet($_POST['cb_show_bills']))
				$str_show_bills = 'Y';
			else
				$str_show_bills = 'N';
				
			if (IsSet($_POST['cb_print_bill_header']))
				$str_print_bill_header = 'Y';
			else
				$str_print_bill_header = 'N';
	
			if (IsSet($_POST['bill_edit_price']))
				$str_edit_price = 'Y';
			else
				$str_edit_price = 'N';
		
			if (IsSet($_POST['bill_adjusted_enabled']))
				$str_adjusted_enabled = 'Y';
			else
				$str_adjusted_enabled = 'N';
		
			if (IsSet($_POST['bill_print_tax_totals']))
				$str_print_tax_totals = 'Y';
			else
				$str_print_tax_totals = 'N';
					
			$int_default_discount = intval($_POST['default_discount']);

			$low_balance = intval($_POST['low_balance']);
					
			$qry_module->Query("SELECT * FROM module WHERE module_id = 1");
			if ($qry_module->RowCount() > 0) {
				$qry_module->Query("
				UPDATE user_settings
				SET stock_is_equal_prices = '".$str_is_equal_prices."',
					stock_bulk_unit = ".$_POST['select_bulk'].",
					stock_packaged_unit = ".$_POST['select_packaged'].",
					stock_show_returned = '".$str_show_returned."',
					stock_show_available = '".$str_show_available."'
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
				);
			}
			
			$qry_module->Query("SELECT * FROM module WHERE module_id = 2");
			if ($qry_module->RowCount() > 0) {
				$qry_module->Query("
				UPDATE user_settings
				SET bill_print_note = '".addslashes($_POST["bill_note"])."',
					bill_print_note_2 = '".addslashes($_POST["bill_note_2"])."',
					bill_print_note_3 = '".addslashes($_POST["bill_note_3"])."',
					bill_print_address = '".addslashes($_POST["bill_address"])."',
					bill_print_phone = '".addslashes($_POST["bill_phone"])."',
					bill_print_batch = '".$str_print_batch."',
					bill_print_supplier_abbreviation = '".$str_print_abbreviation."',
					bill_enable_batches = '".$str_enable_batches."',
					bill_default_discount = ".$int_default_discount.",
					bill_fs_low_balance = '".$low_balance."',
					bill_display_messages = '".$str_display_messages."',
					bill_transfer_tax = ".$_POST['select_transfer_tax'].",
					bill_print_header = '".$str_print_bill_header."',
					bill_header = '".addslashes($_POST['bill_header'])."',
					bill_edit_price = '".$str_edit_price."',
					bill_adjusted_enabled = '".$str_adjusted_enabled."',
					bill_print_tax_totals = '".$str_print_tax_totals."',
					bill_fs_discount = '".$_POST['fs_default_discount']."'
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
				);
			}
			
			$qry_module->Query("SELECT * FROM module WHERE module_id = 7");
			if ($qry_module->RowCount() > 0) {
				$qry_module->Query("
				UPDATE user_settings
				SET order_global_message = '".addslashes($_POST["order_message"])."',
					order_print_bill = '".$str_order_print_bill."',
					order_show_bills = '".$str_show_bills."'
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
				);
			}
		}
	}
	
	$qry_units = new Query("
		SELECT *
		FROM stock_measurement_unit
		ORDER BY measurement_unit
	");
	
	$qry_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);
	
	$qry_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	
	$templateSection = $arrConfig->getItem("section", 'billing');

	if ($templateSection === false) {
		$int_connect_method = CONNECT_ONLINE;
		$int_print_copies = 2;
		$str_display_abbreviation = 'Y';
	}
	else {
		$connect_method_directive =& $templateSection->getItem("directive", "connect_method");
		if ($connect_method_directive === false) {
			$templateSection->createDirective("connect_method", CONNECT_ONLINE);
			$connect_method_directive =& $templateSection->getItem("directive", "connect_method");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		$int_connect_method = $connect_method_directive->getContent();
		
		$print_copies_directive =& $templateSection->getItem("directive", "print_copies");
		if ($print_copies_directive === false) {
			$templateSection->createDirective("print_copies", 2);
			$print_copies_directive =& $templateSection->getItem("directive", "print_copies");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		$int_print_copies = $print_copies_directive->getContent();
		
		$display_abbreviation_directive =& $templateSection->getItem("directive", "display_abbreviation");
		if ($display_abbreviation_directive === false) {
			$templateSection->createDirective("display_abbreviation", 'Y');
			$display_abbreviation_directive =& $templateSection->getItem("directive", "display_abbreviation");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		$str_display_abbreviation = $display_abbreviation_directive->getContent();

	}
?>

<html>
<head>
    <link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin='30px'>

<?
    if ($int_access_level != ACCESS_ADMIN) {
        die('You do not have rights to access this module');
    }
?>

<form name='module_settings' method='post'>
<br>



<?
    //======================
    // Stock module settings
    //----------------------
    $qry_module->Query("SELECT * FROM module WHERE module_id = 1");
    if ($qry_module->RowCount() > 0) {
        boundingBoxStartLabel("600", "Stock&nbsp;Module", 497); ?>
        <br>
        <table border='0' cellpadding='2' cellspacing='0'>
        <tr>
            <td class='normaltext'>Bulk product measurement unit:</td>
            <td>
                <select name='select_bulk' class='v3select'>
                <?
                    for ($i=0;$i<$qry_units->RowCount();$i++) {
                        if ($qry_settings->FieldByName('stock_bulk_unit') == $qry_units->FieldByName('measurement_unit_id'))
                            echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."' selected>".$qry_units->FieldByName('measurement_unit');
                        else
                            echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."'>".$qry_units->FieldByName('measurement_unit');
                        $qry_units->Next();
                    }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class='normaltext'>Packaged product measurement unit:</td>
            <td>
                <select name='select_packaged' class='v3select'>
                <?
                    $qry_units->First();
                    for ($i=0;$i<$qry_units->RowCount();$i++) {
                        if ($qry_settings->FieldByName('stock_packaged_unit') == $qry_units->FieldByName('measurement_unit_id'))
                            echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."' selected>".$qry_units->FieldByName('measurement_unit');
                        else
                            echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."'>".$qry_units->FieldByName('measurement_unit');
                        $qry_units->Next();
                    }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class='normaltext' colspan='2'>
                <input type="checkbox" name="is_equal_prices" <? if ($qry_settings->FieldByName('stock_is_equal_prices') == 'Y') echo "checked";?>>Buying price and selling price are equal
            </td>
        </tr>
        <tr>
            <td class='normaltext' colspan='2' valign='middle'>
                <input type="checkbox" name="cb_show_returned" <? if ($qry_settings->FieldByName('stock_show_returned') == 'Y') echo "checked";?>>Display returned stock in Supplier Received statement
            </td>
        </tr>
        <tr>
            <td class='normaltext' colspan='2'>
                <input type="checkbox" name="cb_show_available" <? if ($qry_settings->FieldByName('stock_show_available') == 'Y') echo "checked";?>>Display stock marked as 'available' in the Stock|Products grid
            </td>
        </tr>
        <tr>
            <td colspan='2'>
                <br>
                <? if ($bool_can_update) { ?>
                <input type="submit" class="settings_button" name="action" value="Save Settings">
		<? } ?>
            </td>
        </tr>
        </table>
<?
        boundingBoxEndLabel("600");
    }
?>
<br><br>

<?
    /*
    	Billing module settings
    */
    $qry_module->Query("SELECT * FROM module WHERE module_id = 2");
    if ($qry_module->RowCount() > 0) {
        boundingBoxStartLabel("600", "Billing&nbsp;Module", 495); ?>
	<br>
        <table border='0' cellpadding='2' cellspacing='0'>
        <tr>
        	<td class="normaltext">
        		Default connection mode:
        	</td>
        	<td>
        		<select name="connect_method" class="select_200">
        			<option value="<?php echo CONNECT_ONLINE;?>" <?php if ($int_connect_method == CONNECT_ONLINE) echo "selected";?>>Online</option>
        			<option value="<?php echo CONNECT_OFFLINE_LIMITED_ACCESS;?>" <?php if ($int_connect_method == CONNECT_OFFLINE_LIMITED_ACCESS) echo "selected";?>>Offline</option>
        		</select>
        	</td>
        </tr>
        <tr>
	    <td class='normaltext'>
		Address to print on bill:
	    </td>
	    <td>
		<input type="text" name="bill_address" value="<?echo $qry_settings->FieldByName('bill_print_address')?>" class='input_settings' autocomplete="OFF">
	    </td>
        </tr>
        <tr>
	    <td class='normaltext'>
		Phone number to print on bill:
	    </td>
	    <td>
	    <input type="text" name="bill_phone" value="<?echo $qry_settings->FieldByName('bill_print_phone')?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>
        <tr>
	    <td class='normaltext'>
		Note to print at end of bill:
	    </td>
	    <td>
	        <input type="text" name="bill_note" value="<?echo $qry_settings->FieldByName('bill_print_note')?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>
        <tr>
	    <td class='normaltext'>
		Note 2:
	    </td>
	    <td>
		<input type="text" name="bill_note_2" value="<?echo $qry_settings->FieldByName('bill_print_note_2')?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>
        <tr>
	    <td class='normaltext'>
		Note 3:
	    </td>
	    <td>
		<input type="text" name="bill_note_3" value="<?echo $qry_settings->FieldByName('bill_print_note_3')?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>
        
		<tr>
	    <td class='normaltext'>
		Default discount when billing:
	    </td>
	    <td>
		<input type="text" name="default_discount" value="<?echo $qry_settings->FieldByName('bill_default_discount')?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>
		
		<tr>
	    <td class='normaltext'>
		Limit for Low Balance :
	    </td>
	    <td>
		<input type="text" name="low_balance" value="<?echo $qry_settings->FieldByName('bill_fs_low_balance')?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>

		<tr>
	    <td class='normaltext'>
		Default FS discount:
	    </td>
	    <td>
		<input type="text" name="fs_default_discount" value="<?echo $qry_settings->FieldByName('bill_fs_discount'); ?>" class="input_settings" autocomplete="OFF">
	    </td>
        </tr>


        <tr>
	    <td class='normaltext'>
		Tax for Transfer of Goods:
	    </td>
	    <td>
		<select name="select_transfer_tax" class='v3select'>
		<?
		    for ($i=0; $i<$qry_tax->RowCount(); $i++) {
			if ($qry_settings->FieldByName('bill_transfer_tax') == $qry_tax->FieldByName('tax_id'))
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
		<td class='normaltext'>
			Copies of bill to print:
		</td>
		<td>
			<select name="print_copies" id="print_copies" class='select_200'>
				<option value="1" <?php if ($int_print_copies == 1) echo "selected"?>>1</option>
				<option value="2" <?php if ($int_print_copies == 2) echo "selected"?>>2</option>
			</select>
		</td>
        </tr>

	<tr>
		<td class='normaltext'>
			<input type='checkbox' name='cb_print_bill_header' <? if ($qry_settings->FieldByName('bill_print_header') == 'Y') echo "checked";?>>&nbsp;Print header :
		</td>
		<td>
			<input type="text" name="bill_header" value="<?echo $qry_settings->FieldByName('bill_header')?>" class="input_settings" autocomplete="OFF">
		</td>
        </tr>
		
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="bill_print_batch" <? if ($qry_settings->FieldByName('bill_print_batch') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Print the batch code in the bill</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="bill_print_supplier_abbreviation" <? if ($qry_settings->FieldByName('bill_print_supplier_abbreviation') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Print the supplier abbreviation in the bill</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="bill_enable_batches" <? if ($qry_settings->FieldByName('bill_enable_batches') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Enable batches when billing</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="display_messages" <? if ($qry_settings->FieldByName('bill_display_messages') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Display warning messages when billing</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="bill_edit_price" <? if ($qry_settings->FieldByName('bill_edit_price') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Can edit price when billing</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="bill_adjusted_enabled" <? if ($qry_settings->FieldByName('bill_adjusted_enabled') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Stock can go negative when billing</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="bill_print_tax_totals" <? if ($qry_settings->FieldByName('bill_print_tax_totals') == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Print the tax totals on the bill</font>
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type="checkbox" name="display_abbreviation" <? if ($str_display_abbreviation == 'Y') echo "checked";?> autocomplete="OFF"><font class="normaltext"> Display the abbreviation of the supplier when billing</font>
	    </td>
        </tr>

        <tr>
            <td colspan='2'>
                <br>
                <? if ($bool_can_update) { ?>
                <input type="submit" class="settings_button" name="action" value="Save Settings">
				<input type='button' name='action' value='Reset FS Login' onclick='javascript:set_fs_login();' class='settings_button'>

		<? } ?>
            </td>
        </tr>
        </table>
<?
        boundingBoxEndLabel("600");
    }
?>
<br><br>


<?
    //=======================
    // Orders module settings
    //-----------------------
    $qry_module->Query("SELECT * FROM module WHERE module_id = 7");
    if ($qry_module->RowCount() > 0) {
        boundingBoxStartLabel("600", "Orders&nbsp;Module", 497); ?>
	<br>
        <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
	    <td class='normaltext'>
		Global message:<br>
		<textarea class='normaltext' name='order_message' rows=5 cols='70'><?echo $qry_settings->FieldByname('order_global_message');?></textarea>
	    </td>
	</tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type='checkbox' name='cb_show_bills' <?if ($qry_settings->FieldByName('order_show_bills') == 'Y') echo "checked";?>>Show order bills in the Bills grid
	    </td>
        </tr>
        <tr>
	    <td colspan='2' class='normaltext'>
		<input type='checkbox' name='cb_print_bill' <?if ($qry_settings->FieldByName('order_print_bill') == 'Y') echo "checked";?>>Print bill when delivering orders
	    </td>
        </tr>
        <tr>
            <td colspan='2'>
                <br>
                <? if ($bool_can_update) { ?>
                <input type="submit" class="settings_button" name="action" value="Save Settings">
		<? } ?>
            </td>
        </tr>
        </table>
<?
        boundingBoxEndLabel("600");
    }
?>
<br><br>

</form>

<script language='javascript'>

	function set_fs_login() {
		myWin = window.open("set_fs_login.php",'set_fs_login','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=450,top=0');
		myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 350/2));
		myWin.focus();
	}

</script>

</body>
</html>


