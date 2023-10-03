<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");

	$str_display_quantity = $_SESSION['order_sheet_display_quantity'];
	$str_sheet_date = $_SESSION['order_sheet_date'];
	$str_sheet_date_to = $_SESSION['order_sheet_date_to'];
	$str_include_delivered = $_SESSION['order_sheet_include_delivered'];

	//====================
	// get the user defined settings
	//====================
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	//====================
	// process GET parameters
	//====================
	$str_print_condensed = "";
	if (IsSet($_GET['print_condensed']))
		if ($_GET['print_condensed'] == 'Y')
			$str_print_condensed = "%c";

	//====================
	// generate mysql string for the products to be included
	//====================
	$str_products_clause = '';
	$str_tmp_clause = '';
//	if (count($arr_selected) > 0)
		$str_products_clause = ' AND (bi.product_id IN (';
	for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
		if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y')
			$str_tmp_clause .= $_SESSION['arr_order_sheet_products'][$i][0].", ";
	}
	$str_products_clause .= substr($str_tmp_clause, 0, strlen($str_tmp_clause)-2);
//	if (count($arr_selected) > 0)
		$str_products_clause .= "))";
		

	function getMySQLDate($str_date) {
		if ($str_date == '')
			$str_date = date('d-m-Y');
		$arr_date = explode('-', $str_date);
		return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
	}

	function get_product_index($arr_search, $int_product_id) {
		$int_retval = -1;
		for ($i=0; $i<count($arr_search); $i++) {
			if ($arr_search[$i][0] == $int_product_id) {
				$int_retval = $i;
				break;
			}
		}
		return $int_retval;
	}
	
	$str_print_day_of_week = '';
	$arr_date = getdate(strtotime(set_mysql_date($str_sheet_date,'-')));
	$str_print_day_of_week = $arr_date['weekday'];

	//====================
	// load all the products for the given date
	// into an array
	//====================
	$arr_products = array();

	if ($str_include_delivered == 'Y')
		$str_query = "
			SELECT
				sp.product_id, sp.product_code, sp.product_description, sp.product_abbreviation,
				smu.is_decimal
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi
			INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE  (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (
					DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
				)
				".$str_products_clause."
			GROUP BY bi.product_id
			ORDER BY sp.product_code";
	else
		$str_query = "
			SELECT
				sp.product_id, sp.product_code, sp.product_description, sp.product_abbreviation,
				smu.is_decimal
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi
			INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (
					DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
				)
				AND (b.is_pending = 'Y')
				AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
				".$str_products_clause."
			GROUP BY bi.product_id
			ORDER BY sp.product_code";

	$qry_sheet = new Query($str_query);

	for ($i=0; $i<$qry_sheet->RowCount(); $i++) {
		$arr_products[$i][0] = $qry_sheet->FieldByName('product_id');
		$arr_products[$i][1] = $qry_sheet->FieldByName('product_code');
		$arr_products[$i][2] = $qry_sheet->FieldByName('product_abbreviation');
		$arr_products[$i][3] = $qry_sheet->FieldByName('product_description');
		$arr_products[$i][4] = $qry_sheet->FieldByName('is_decimal');
		$qry_sheet->Next();
	}

	//====================
	// get all the order bills for the given date
	//====================
	if ($str_include_delivered == 'Y')
		$str_query = "
			SELECT
				*
			FROM ".Monthalize('bill')." b, ".Monthalize('orders')." o
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (b.module_id = 7)
				AND (
					DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
				)
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'Y')
			ORDER BY account_name";
	else
		$str_query = "
			SELECT
				*
			FROM ".Monthalize('bill')." b, ".Monthalize('orders')." o
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (b.module_id = 7)
				AND (
					DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
				)
				AND (b.is_pending = 'Y')
				AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'Y')
			ORDER BY account_name";

	$qry_bills = new Query($str_query);

	//====================
	// for each bill, load the quantities into an array
	//====================
	$arr_data = array();
	$qry_products = new Query("SELECT * FROM ".Monthalize('bill_items')." LIMIT 1");

	for ($i=0; $i<$qry_bills->RowCount(); $i++) {
		$arr_data[$i][0] = $qry_bills->FieldByName('account_number');
		if ($qry_bills->FieldByName('payment_type') == BILL_CASH)
			$arr_data[$i][1] = substr($qry_bills->FieldByName('note'), 0, 15);
		else
			$arr_data[$i][1] = $qry_bills->FieldByName('account_name');

		$str_products = "
			SELECT *
			FROM ".Monthalize('bill_items')." bi
			WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').")
				".$str_products_clause;
		$qry_products->Query($str_products);

		for ($j=0; $j<$qry_products->RowCount(); $j++) {
			$int_index = get_product_index($arr_products, $qry_products->FieldByName('product_id'));
			if ($int_index > -1) {
				if ($str_display_quantity == 'delivered')
					$arr_data[$i][$int_index+2] = $qry_products->FieldByName('quantity') + $qry_products->FieldByName('adjusted_quantity');
				else
					$arr_data[$i][$int_index+2] = $qry_products->FieldByName('quantity_ordered');
			}
			$qry_products->Next();
		}
		
		$qry_bills->Next();
	}

	//====================
	// calculate the totals for each column
	//====================
	$int_last_row = count($arr_data);
	$arr_data[$int_last_row][0] = '';
	$arr_data[$int_last_row][1] = '';
	for ($j=0; $j<count($arr_products); $j++)
		$arr_data[$int_last_row][$j+2] = 0;

	for ($i=0; $i<count($arr_data)-1; $i++) {
		for ($j=0; $j<count($arr_products); $j++) {
			if (IsSet($arr_data[$i][$j+2])) 
				$arr_data[$int_last_row][$j+2] += number_format($arr_data[$i][$j+2],2,'.','');
		}
	}

	//====================
	// check whether the decimal part of a number is greater than zero
	//====================
        function hasDecimalPart($aNumber) {
		$decimal_part = $aNumber - intval($aNumber);
		$decimal_part = $decimal_part * 1000;
		$decimal_part = intval($decimal_part);
		return ($decimal_part > 0);
	}
	
	//====================
	// get the totals per product per community
	//====================
	if ($str_include_delivered == 'Y')
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description,
				c.community_name,
				sp.product_abbreviation,
				smu.is_decimal,
				SUM(bi.quantity + bi.adjusted_quantity) AS quantity,
				SUM(bi.quantity_ordered) AS quantity_ordered
			FROM ".Monthalize('orders')." o
			INNER JOIN ".Monthalize('bill')." b ON (b.module_id = 7)
			INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
			INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."')
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'N')
			GROUP BY bi.product_id, c.community_id
			ORDER BY c.community_name, sp.product_code";
	else
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description,
				c.community_name,
				sp.product_abbreviation,
				smu.is_decimal,
				SUM(bi.quantity + bi.adjusted_quantity) AS quantity,
				SUM(bi.quantity_ordered) AS quantity_ordered
			FROM ".Monthalize('orders')." o
			INNER JOIN ".Monthalize('bill')." b ON (b.module_id = 7)
			INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
			INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."')
				AND (b.is_pending = 'Y') 
				AND (b.bill_status = 1)
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'N')
			GROUP BY bi.product_id, c.community_id
			ORDER BY c.community_name, sp.product_code";

	$qry_orders = new Query($str_query);

	$arr_communities = array();
	$str_community = '';
	$int_row = -1;
	for ($i=0; $i<$qry_orders->RowCount(); $i++) {

		if ($str_community <> $qry_orders->FieldByName('community_name')) {
			$int_row++;
			$arr_communities[$int_row][0] = $qry_orders->FieldByName('community_name');
		}

		$int_index = get_product_index($arr_products, $qry_orders->FieldByName('product_id'));
		if ($int_index > -1) {
			if ($str_display_quantity == 'delivered')
				$arr_communities[$int_row][$int_index+1] = $qry_orders->FieldByName('quantity');
			else
				$arr_communities[$int_row][$int_index+1] = $qry_orders->FieldByName('quantity_ordered');
		}

		$str_community = $qry_orders->FieldByName('community_name');
		$qry_orders->Next();
	}

	//====================
	// calculate the grand totals
	//====================
	$int_last = count($arr_communities);
	$arr_communities[$int_last][0] = '';
	for ($j=0; $j<count($arr_products); $j++)
		$arr_communities[$int_last][$j+1] = $arr_data[$int_last_row][$j+2];

	for ($i=0; $i<count($arr_communities)-1; $i++) {
		for ($j=0; $j<count($arr_products); $j++) {
			if (IsSet($arr_communities[$i][$j+1])) 
				$arr_communities[$int_last][$j+1] += number_format($arr_communities[$i][$j+1],2,'.','');
		}
	}

?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
$str_double = "";
$str_double = PadWithCharacter($str_double, '=', 120);

$str_single = "";
$str_single = PadWithCharacter($str_single, '-', 120);

$str_info = $str_print_day_of_week."\nOrder Sheet for ".makeHumanDate(getMySQLDate($str_sheet_date));

//====================
// generate the header
//====================
$str_header = 
PadWithCharacter('Name', ' ', 20)."|";
for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
	if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y') {
		if ($_SESSION['arr_order_sheet_products'][$i][2] == '')
			$str_header .= StuffWithCharacter($_SESSION['arr_order_sheet_products'][$i][1], ' ', 4)."|";
		else
			$str_header .= StuffWithCharacter($_SESSION['arr_order_sheet_products'][$i][2], ' ', 4)."|";
	}
}

//====================
// generate the individual data
//====================
$str_data = '';
for ($i=0; $i<count($arr_data)-1; $i++) {
	$str_data .= PadWithCharacter($arr_data[$i][1], ' ', 20)."|";
	
	for ($j=0; $j<count($arr_products); $j++) {
		if (IsSet($arr_data[$i][$j+2])) {
			if ($arr_products[$j][4] == 'Y')
				if (hasDecimalPart($arr_data[$i][$j+2]))
					$str_data .= StuffWithCharacter(number_format($arr_data[$i][$j+2], 2, '.', ''), ' ', 4)."|";
				else
					$str_data .= StuffWithCharacter(number_format($arr_data[$i][$j+2], 0, '.', ''), ' ', 4)."|";
			else
				$str_data .= StuffWithCharacter(number_format($arr_data[$i][$j+2], 0, '.', ''), ' ', 4)."|";
		}
		else
			$str_data .= "    |";
	}
	$str_data .= "\n";
}

//====================
// generate the totals
//====================
$str_totals = StuffWithCharacter('Totals ', ' ', 20)."|";
for ($i=0; $i<count($arr_products); $i++) {
	if (hasDecimalPart($arr_data[$int_last_row][$i+2]))
		$str_totals .= StuffWithCharacter(number_format($arr_data[$int_last_row][$i+2], 2, '.', ''), ' ', 4)."|";
	else
		$str_totals .= StuffWithCharacter(number_format($arr_data[$int_last_row][$i+2], 0, '.', ''), ' ', 4)."|";
}

//====================
// generate the community totals data
//====================
$str_communities = "";
for ($i=0; $i<count($arr_communities)-1; $i++) {
	$str_communities .= StuffWithCharacter($arr_communities[$i][0], ' ', 20)."|";
	for ($j=0; $j<count($arr_products); $j++) {
		if (IsSet($arr_communities[$i][$j+1])) {
			if (hasDecimalPart($arr_communities[$i][$j+1]))
				$str_communities .= StuffWithCharacter(number_format($arr_communities[$i][$j+1],2,'.',''), ' ',4)."|";
			else
				$str_communities .= StuffWithCharacter(number_format($arr_communities[$i][$j+1],0,'.',''), ' ',4)."|";
		}
		else
			$str_communities .= "    |";
	}
	$str_communities .= "\n";
}

//====================
// generate the grand totals
//====================
$str_grand_totals = StuffWithCharacter('Grand Totals ', ' ', 20)."|";
for ($i=0; $i<count($arr_products); $i++) {
	if (hasDecimalPart($arr_communities[$int_last][$i+1]))
		$str_grand_totals .= StuffWithCharacter(number_format($arr_communities[$int_last][$i+1],2,'.',''), ' ',4)."|";
	else
		$str_grand_totals .= StuffWithCharacter(number_format($arr_communities[$int_last][$i+1],0,'.',''), ' ',4)."|";
}

//====================
// generate the number spaces after
//====================
$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = $str_print_condensed."
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_info."
".$str_double."
".$str_header."
".$str_single."
".$str_data."
".$str_single."
".$str_totals."
".$str_single."
".$str_communities."
".$str_single."
".$str_grand_totals."
".$str_double."
".$str_eject_lines;

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
	echo $str_statement;
?>
</PRE>


<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/print.php">
<? } else { ?>
<form name="printerForm" onsubmit="return false;">
<? } ?>


<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing Statement</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <? if (browser_detection("os") === "lin") { ?>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
      <? } else { ?>
      <input type="hidden" name="output" value="<? echo htmlentities($str_statement); ?>">
      <? } ?>
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

<? if (browser_detection( 'os' ) === 'lin') { ?>

<script language="JavaScript">
	printerForm.submit();
</script>

<? } else { ?>

<script language="JavaScript">
	writedata();
</script>

<? } ?>

</body>
</html>
