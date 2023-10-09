<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");

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
	
	$str_display_quantity = 'delivered';
	if (IsSet($_GET['display_quantity']))
		$str_display_quantity = $_GET['display_quantity'];

	$str_sheet_date = date('d-m-Y');
	if (IsSet($_GET['sheet_date']))
		$str_sheet_date = $_GET['sheet_date'];

	$str_include_delivered = 'N';
	if (IsSet($_GET['include_delivered']))
		$str_include_delivered = $_GET['include_delivered'];

	$str_community = '';
	if (IsSet($_GET['community']))
		$str_community = $_GET['community'];

	$str_print_condensed = "";
	if (IsSet($_GET['print_condensed']))
		if ($_GET['print_condensed'] == 'Y')
			$str_print_condensed = "%c";

	if ($str_community == 'ALL') {
		//====================
		// get the list of communities for the given date
		//====================
		$str_communities = "
			SELECT DISTINCT
				c.community_id, c.community_name
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp,
				".Monthalize('orders')." o
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (bi.product_id = sp.product_id)
				AND (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (DATE(b.date_created) = '".getMySQLDate($str_sheet_date)."')
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'N')
			ORDER BY c.community_name";
		$qry_communities = new Query($str_communities);
	}
	else {
		$qry_communities = new Query("SELECT community_name FROM communities WHERE community_id = $str_community");
		$str_community_name = $qry_communities->FieldByName('community_name');
	}

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
	// load all the products for the given date
	// into an array
	//====================
	function get_order_products($date_selected, $str_community, $str_include_delivered) {

		if ($str_include_delivered == 'Y')
			$str_query = "
				SELECT
					sp.product_id, sp.product_code, sp.product_description, sp.product_abbreviation,
					smu.is_decimal,
					c.community_name
				FROM ".Monthalize('orders')." o
				INNER JOIN ".Monthalize('bill')." b ON (b.module_id = 7)
				INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
				INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN communities c ON (c.community_id = o.community_id)
				WHERE (DATE(b.date_created) = '".getMySQLDate($date_selected)."')
					AND (b.module_record_id = o.order_id)
					AND (c.community_id = ".$str_community.")
				GROUP BY bi.product_id
				ORDER BY sp.product_code";
		else
			$str_query = "
				SELECT
					sp.product_id, sp.product_code, sp.product_description, sp.product_abbreviation,
					smu.is_decimal,
					c.community_name
				FROM ".Monthalize('orders')." o
				INNER JOIN ".Monthalize('bill')." b ON (b.module_id = 7)
				INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
				INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN communities c ON (c.community_id = o.community_id)
				WHERE (DATE(b.date_created) = '".getMySQLDate($date_selected)."')
					AND (b.is_pending = 'Y')
					AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
					AND (b.module_record_id = o.order_id)
					AND (c.community_id = ".$str_community.")
				GROUP BY bi.product_id
				ORDER BY sp.product_code";
	
		$qry_sheet = new Query($str_query);
	
		$arr_products = array();
	
		for ($i=0; $i<$qry_sheet->RowCount(); $i++) {
			$arr_products[$i][0] = $qry_sheet->FieldByName('product_id');
			$arr_products[$i][1] = $qry_sheet->FieldByName('product_code');
			$arr_products[$i][2] = $qry_sheet->FieldByName('product_abbreviation');
			$arr_products[$i][3] = $qry_sheet->FieldByName('product_description');
			$arr_products[$i][4] = $qry_sheet->FieldByName('is_decimal');
			$qry_sheet->Next();
		}

		return $arr_products;
	}

	//====================
	// get all the order bills for the given date
	//====================
	function get_order_details($date_selected, $str_community, $str_include_delivered, $arr_products, $str_display_quantity) {
		
		if ($str_include_delivered == 'Y')
			$str_query = "
				SELECT
					*
				FROM ".Monthalize('bill')." b, ".Monthalize('orders')." o
				LEFT JOIN communities c ON (c.community_id = o.community_id)
				WHERE (b.module_id = 7)
					AND (DATE(b.date_created) = '".getMySQLDate($date_selected)."')
					AND (b.module_record_id = o.order_id)
					AND (c.community_id = ".$str_community.")";
		else
			$str_query = "
				SELECT
					*
				FROM ".Monthalize('bill')." b, ".Monthalize('orders')." o
				LEFT JOIN communities c ON (c.community_id = o.community_id)
				WHERE (b.module_id = 7)
					AND (DATE(b.date_created) = '".getMySQLDate($date_selected)."')
					AND (b.is_pending = 'Y')
					AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
					AND (b.module_record_id = o.order_id)
					AND (c.community_id = ".$str_community.")";
	
		$qry_bills = new Query($str_query);
	
		//====================
		// for each bill, load the quantities into an array
		//====================
		$arr_data = array();
		$qry_products = new Query("SELECT * FROM ".Monthalize('bill_items')." LIMIT 1");
	
		for ($i=0; $i<$qry_bills->RowCount(); $i++) {
			$arr_data[$i][0] = $qry_bills->FieldByName('account_number');
			$arr_data[$i][1] = $qry_bills->FieldByName('account_name');
	
			$qry_products->Query("
				SELECT *
				FROM ".Monthalize('bill_items')." bi
				WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').")
			");
			
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

		return $arr_data;
	}
?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
$str_double = "";
$str_double = PadWithCharacter($str_double, '=', 80);

$str_single = "";
$str_single = PadWithCharacter($str_single, '-', 80);

if ($str_community == 'ALL') {
	//====================
	// A L L   C O M M U N I T I E S
	//====================
	$str_list = '';
	
	for ($k=0; $k<$qry_communities->RowCount(); $k++) {
		
		$arr_products = get_order_products($str_sheet_date, $qry_communities->FieldByName('community_id'), $str_include_delivered);
		$arr_data = get_order_details($str_sheet_date, $qry_communities->FieldByName('community_id'), $str_include_delivered, $arr_products, $str_display_quantity);
		
		//====================
		// generate the header
		//====================
                $arr_date = getdate(strtotime(getMySQLDate($str_sheet_date)));
		$str_header_community = "%n%b%w".$arr_date['weekday']."\n";
		$str_header_community .= "Order Sheet for ".$qry_communities->FieldByName('community_name')." ".makeHumanDate(getMySQLDate($str_sheet_date))."%n%c";
		$str_header = StuffWithCharacter('Number', ' ', 10)." ".PadWithCharacter('Name', ' ', 20)."|";
		for ($i=0; $i<count($arr_products); $i++) {
			if ($arr_products[$i][2] == '')
				$str_header .= StuffWithCharacter($arr_products[$i][1], ' ', 4)."|";
			else
				$str_header .= StuffWithCharacter($arr_products[$i][2], ' ', 4)."|";
		}
		
		//====================
		// generate the content
		//====================
		$str_data = "";
		for ($i=0; $i<count($arr_data)-1; $i++) {
			$str_data .= StuffWithCharacter($arr_data[$i][0], ' ', 10)." ".PadWithCharacter($arr_data[$i][1], ' ', 20)."|";
			
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
		// generate the content
		//====================
		$str_totals = StuffWithCharacter("Totals", " ", 30)." |";
		$int_last_row = count($arr_data)-1;
		for ($i=0; $i<count($arr_products); $i++) {
			if (hasDecimalPart($arr_data[$int_last_row][$i+2]))
				$str_totals .= StuffWithCharacter(number_format($arr_data[$int_last_row][$i+2],2,'.',''), ' ', 4)."|";
			else
				$str_totals .= StuffWithCharacter(number_format($arr_data[$int_last_row][$i+2],0,'.',''), ' ', 4)."|";
		}
		
$str_list .= "
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_header_community."
".$str_double."
".$str_header."
".$str_single."
".$str_data."
".$str_single."
".$str_totals."
".$str_double."\n\n\n";

		$qry_communities->Next();
	}
}
else {
	//====================
	// SELECTED COMMUNITY
	//====================
	$arr_products = get_order_products($str_sheet_date, $str_community, $str_include_delivered);
	$arr_data = get_order_details($str_sheet_date, $str_community, $str_include_delivered, $arr_products, $str_display_quantity);
	
	//====================
	// generate the header
	//====================
        $arr_date = getdate(strtotime(getMySQLDate($str_sheet_date)));
	$str_header_community = $arr_date['weekday'];
	$str_header_community .= "\nOrder sheet for ".$str_community_name." ".makeHumanDate(getMySQLDate($str_sheet_date));
	$str_header = StuffWithCharacter('Number', ' ', 10)." ".PadWithCharacter('Name', ' ', 20)."|";
	for ($i=0; $i<count($arr_products); $i++) {
		if ($arr_products[$i][2] == '')
			$str_header .= StuffWithCharacter($arr_products[$i][1], ' ', 4)."|";
		else
			$str_header .= StuffWithCharacter($arr_products[$i][2], ' ', 4)."|";
	}

	//====================
	// generate the content
	//====================
	$str_data = "";
	for ($i=0; $i<count($arr_data)-1; $i++) {
		$str_data .= StuffWithCharacter($arr_data[$i][0], ' ', 10)." ".PadWithCharacter($arr_data[$i][1], ' ', 20)."|";
		
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
	// generate the content
	//====================
	$str_totals = StuffWithCharacter("Totals", " ", 30)." |";
	$int_last_row = count($arr_data)-1;
	for ($i=0; $i<count($arr_products); $i++) {
		if (hasDecimalPart($arr_data[$int_last_row][$i+2]))
			$str_totals .= StuffWithCharacter(number_format($arr_data[$int_last_row][$i+2],2,'.',''), ' ', 4)."|";
		else
			$str_totals .= StuffWithCharacter(number_format($arr_data[$int_last_row][$i+2],0,'.',''), ' ', 4)."|";
	}
}




//====================
// generate the number spaces after
//====================
$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

if ($str_community == 'ALL')

$str_statement = $str_print_condensed."
".$str_list."
".$str_eject_lines;

else

$str_statement = $str_print_condensed."
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_header_community."
".$str_double."
".$str_header."
".$str_single."
".$str_data."
".$str_single."
".$str_totals."
".$str_double."
".$str_eject_lines;

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
