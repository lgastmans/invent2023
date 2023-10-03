<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");


	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");


	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;

	$qry = new Query("
		SELECT stock_show_returned
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$str_show_returned = 'Y';
	if ($qry->RowCount() > 0)
		$str_show_returned = $qry->FieldByName('stock_show_returned');

	$str_product_code = '';
	if (IsSet($_GET["product_code"]))
		$str_product_code = $_GET["product_code"];

	if (IsSet($_GET["display_price"])) {
		if ($_GET["display_price"] == "B") {
			$str_price = "sb.buying_price";
			$str_price_header = 'B Price';
		}
		else {
			$str_price = "sb.selling_price";
			$str_price_header = 'S Price';
		}
	}
	else {
		$str_price = "sb.buying_price";
		$str_price_header = 'B Price';
	}

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	$int_eject_lines = 12;
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	$qry_supplier = new Query("
		SELECT supplier_name
		FROM stock_supplier
		WHERE supplier_id = $int_supplier_id
	");

	$str_type_clause = '';
	if ($str_show_returned == 'Y')
		$str_type_clause = "
				AND (
					(st.transfer_type = ".TYPE_RECEIVED.") AND (st.storeroom_id_to = ".$_SESSION["int_current_storeroom"].")
					OR
					(st.transfer_type = ".TYPE_RETURNED.") AND (st.storeroom_id_from = ".$_SESSION["int_current_storeroom"].")
				)";
	else
		$str_type_clause = "
				AND (st.transfer_type = ".TYPE_RECEIVED.") AND (st.storeroom_id_to = ".$_SESSION["int_current_storeroom"].")";
	
	if ($str_product_code <> '')
		$str_qry = "
			SELECT
				DAYOFMONTH(st.date_created) as date_created,
				sp.product_code,
				sp.product_description,
				".$str_price.",
				st.transfer_quantity,
				st.transfer_type,
				ROUND(st.transfer_quantity * ".$str_price.", 2) AS amount,
				smu.is_decimal,
				u.username
			FROM 
				".Monthalize("stock_transfer")." st
			INNER JOIN user u ON (u.user_id = st.user_id)
			INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.supplier_id = ".$int_supplier_id.")
			INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (sb.batch_id = st.batch_id) 
				AND (sp.product_code = '".$str_product_code."')
				AND (sp.deleted = 'N')
				$str_type_clause
		";
	else
		$str_qry = "
			SELECT
				DAYOFMONTH(st.date_created) as date_created,
				sp.product_code,
				sp.product_description,
				".$str_price.",
				st.transfer_quantity,
				st.transfer_type,
				ROUND(st.transfer_quantity * ".$str_price.", 2) AS amount,
				smu.is_decimal,
				u.username
			FROM 
				".Monthalize("stock_transfer")." st
			INNER JOIN user u ON (u.user_id = st.user_id)
			INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.supplier_id = ".$int_supplier_id.")
			INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (sb.batch_id = st.batch_id) 
				AND (sp.deleted = 'N')
				$str_type_clause
		";
//echo $str_qry;
	$qry = new Query($str_qry);
?>






<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
$str_supplier = "Statement of received stock for ".$qry_supplier->FieldByName('supplier_name')." for the month ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];

$str_top = "";
$str_top = PadWithCharacter($str_top, '=', 108);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', 108);

$str_header =
  StuffWithCharacter('Date', ' ', 9)." ".
  StuffWithCharacter('Code', ' ', 10)." ".
  PadWithCharacter('Description', ' ', 30)." ".
  StuffWithCharacter($str_price_header, ' ', 8)." ".
  StuffWithCharacter('Quantity', ' ', 10)." ".
  StuffWithCharacter('Amount', ' ', 10)." ".
  StuffWithCharacter('User', ' ', 15);

$date_current = 0;
$total = 0;
$total_qty = 0;

$str_data = "";
for ($i=0;$i<$qry->RowCount();$i++) {
  if ($date_current < $qry->FieldByName('date_created')) {
  	$str_data .= StuffWithCharacter($qry->FieldByName('date_created'), ' ', 10);
  	$date_current = $qry->FieldByName('date_created');
  }
  else
  	$str_data .= StuffWithCharacter("", ' ', 10);

	if ($qry->FieldByName('is_decimal') == 'Y')
		if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
			$str_quantity = number_format(($qry->FieldByName('transfer_quantity') * -1),2,',','');
		else
			$str_quantity = sprintf("%01.2f", $qry->FieldByName('transfer_quantity'));
	else
		if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
			$str_quantity = number_format(($qry->FieldByName('transfer_quantity') * -1),0,',','');
		else
			$str_quantity = number_format($qry->FieldByName('transfer_quantity'),0,',','');
	
	if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
		$str_amount = number_format(($qry->FieldByName('amount') * -1),2,'.','');
	else
		$str_amount = number_format($qry->FieldByName('amount'),2,'.','');
  	
	if ($str_price == "sb.buying_price")
		$str_print_price = StuffWithCharacter(sprintf("%01.2f", $qry->FieldByName('buying_price')), ' ', 8)." ";
	else
		$str_print_price = StuffWithCharacter(sprintf("%01.2f", $qry->FieldByName('selling_price')), ' ', 8)." ";
  
  $str_data .= StuffWithCharacter($qry->FieldByName('product_code'), ' ', 10)." ".
  PadWithCharacter($qry->FieldByName('product_description'), ' ', 30)." ".
  $str_print_price.
  StuffWithCharacter($str_quantity, ' ', 10)." ".
  StuffWithCharacter($str_amount, ' ', 10)." ".
  StuffWithCharacter($qry->FieldByName('username'), ' ', 15)."\n";

  if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
	$total -= $qry->FieldByName('amount');
  else
	$total += $qry->FieldByName('amount');
	
  if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
	$total_qty -= $qry->FieldByName('transfer_quantity');
  else
	$total_qty += $qry->FieldByName('transfer_quantity');
  
  $qry->Next();
}

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "%c
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_supplier."
".$str_top."
".$str_header."
".$str_bottom."
".$str_data."
".$str_top."
Total : ".sprintf("%01.2f", $total_qty)."
Value : Rs. ".sprintf("%01.2f", $total).$str_eject_lines."%n";

$str_statement = replaceSpecialCharacters($str_statement);

?>

<PRE>
<?
 echo $str_statement;
?>
</PRE>


<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing Statement</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>

	  <input type="hidden" name="os" value="<? echo $os;?>"><br>
	  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
	  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>

    </td>
  </tr>
  <tr>
    <td class='normaltext'>
      <textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea>
    </td>
  </tr>
  <tr>
    <td align='center'>
      <br><input type='submit' name='doaction' value="Print">
      <input type='button' onclick="window.close();" name='doaction' value="Close">
    </td>
  </tr>
</table>

</form>

<script language="JavaScript">
	printerForm.submit();
</script>

</body>
</html>