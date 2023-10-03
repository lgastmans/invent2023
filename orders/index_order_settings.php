<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$_SESSION["int_orders_menu_selected"] = 6;

	$str_message = '';

	if (IsSet($_POST["action"])) {

		if ($_POST["action"] == "save") {
				
			if (IsSet($_POST['cb_print_bill']))
				$str_order_print_bill = 'Y';
			else
				$str_order_print_bill = 'N';
				
			if (IsSet($_POST['cb_show_bills']))
				$str_show_bills = 'Y';
			else
				$str_show_bills = 'N';
				
			$str_update = "
				UPDATE user_settings
				SET order_global_message = '".addslashes($_POST["order_message"])."',
					order_print_bill = '".$str_order_print_bill."',
					order_show_bills = '".$str_show_bills."'
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
    order_settings.submit();
  }
</script>

<body bgcolor="#E9ECF1">
<form name="order_settings" method="POST">

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
		<td>&nbsp;</td>
		<td class="<?echo $str_class_header?>">
			<input type='checkbox' name='cb_show_bills' <?if ($sql->FieldByName('order_show_bills') == 'Y') echo "checked";?>>Show order bills in the Bills grid
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
		<td class="<?echo $str_class_header?>">
			<input type='checkbox' name='cb_print_bill' <?if ($sql->FieldByName('order_print_bill') == 'Y') echo "checked";?>>Print bill when delivering orders
		</td>
	</tr>

	<tr>
		<td class="<?echo $str_class_header?>" align='right' valign='top'>
			Global message: 
		</td>
		<td>
			<textarea name='order_message' rows=5 cols='70'><?echo $sql->FieldByname('order_global_message');?></textarea>
		</td>
		<td>&nbsp;</td>
	</tr>
    
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="button" class="v3button" name="Save" value="Save" onclick="saveSettings()">
			<input type="hidden" name="action" value="save">
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