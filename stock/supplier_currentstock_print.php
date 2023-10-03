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

	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	$int_eject_lines = 12;
	$int_decimal_places = 0;
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
		$int_decimal_places = $sql_settings->FieldByName('bill_decimal_places');
	}

	$str_storeroom_info = '';
	$sql_settings->Query("
		SELECT description
		FROM stock_storeroom
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]);
	if ($sql_settings->RowCount() > 0)
		$str_storeroom_info = " for storeroom ".$sql_settings->FieldByName('description');


		

	if (IsSet($_GET["include_tax"]))
		$str_include_tax = $_GET["include_tax"];
	else
		$str_include_tax = 'Y';
	

	if (IsSet($_GET["include_value"]))
		$str_include_value = $_GET["include_value"];
	else
		$str_include_value = 'Y';
		
		
	if (IsSet($_GET['include_bprice']))
		$str_include_bprice = $_GET['include_bprice'];
	else
		$str_include_bprice = 'N';
		




	/*
		the following include file expects variable
		$int_decimal_places
	*/
	require_once("supplier_currentstock_data.php");


	$qry_supplier = new Query("SELECT * FROM stock_supplier WHERE supplier_id=".$int_supplier_id);



?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
if ($_SESSION["int_month_loaded"] <> date('m'))
	$int_day = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
else
	$int_day = date('j');

if ($str_is_filtered == 'Y')
$str_supplier = "Statement of current stock as on ".$int_day." ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]." for ".$qry_supplier->FieldByName('supplier_name')." \nShowing ".$str_display_stock." stock".$str_storeroom_info."\nFiltered on ".$str_filter_field." for ".$str_filter_text;
else
$str_supplier = "Statement of current stock as on ".$int_day." ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]." for ".$qry_supplier->FieldByName('supplier_name')." \nShowing ".$str_display_stock." stock".$str_storeroom_info;

$str_top = "";
$str_top = PadWithCharacter($str_top, '=', 120);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', 120);

if ($str_include_bprice == 'Y')
	$str_bprice_header = StuffWithCharacter('B Price', ' ', 8);
else
	$str_bprice_header = "";
	
if ($str_include_tax == 'Y')
	$str_tax_header = StuffWithCharacter('P/Tax', ' ', 8)." ".StuffWithCharacter('M.R.P.', ' ', 8)." ".StuffWithCharacter('Tax%', ' ', 4)." ";
else
	$str_tax_header = StuffWithCharacter('M.R.P.', ' ', 8)." ";
	
if ($str_include_value == 'Y')
	if ($str_include_bprice == 'Y')
		$str_value_header = StuffWithCharacter('B Value', ' ', 10)." ".StuffWithCharacter('S Value', ' ', 10);
	else
		$str_value_header = StuffWithCharacter('S Value', ' ', 10);
else
	$str_value_header = "";


$str_header = StuffWithCharacter('Code', ' ', 8)." ".
  PadWithCharacter('Description', ' ', 23)." ".
  PadWithCharacter('Category', ' ', 15)." ".
  $str_bprice_header." ".
  StuffWithCharacter('S Price', ' ', 8)." ".
  $str_tax_header.
  StuffWithCharacter('Stock', ' ', 8)." ".
  $str_value_header;


$str_data = "";


foreach ($data['data'] as $row) {

			
	if ($i % 5 == 0)
		$str_data .= $str_bottom."\n";

	if ($str_include_bprice == 'Y')
		$str_bprice = StuffWithCharacter(sprintf("%01.2f",$row['buying_price'],3), ' ', 8);
	else
		$str_bprice = "";
		
	if ($str_include_tax == 'Y')
		$str_tax_columns = StuffWithCharacter(sprintf("%01.2f",$row['price_tax'],3), ' ', 8)." ".
			StuffWithCharacter($row['mrp'], ' ', 8)." ".
			StuffWithCharacter($row['tax_description'], ' ', 4)." ";
	else
		$str_tax_columns = StuffWithCharacter($row['mrp'], ' ', 8)." ";

	if ($str_include_value == 'Y')
		if ($str_include_bprice == 'Y')
			$str_value_columns = StuffWithCharacter(sprintf("%01.2f",$row['buying_value'],3), ' ', 10)." ".
				StuffWithCharacter(sprintf("%01.2f",$row['selling_value'],3), ' ', 10)."\n";
		else
			$str_value_columns = StuffWithCharacter(sprintf("%01.2f",$row['selling_value'],3), ' ', 10)."\n";
	else
		$str_value_columns = "\n";
	
	if ($row['is_decimal'] == 'Y') {

		if ($row['stock_adjusted'] > 0)
			$str_quantity = "(-".$row['stock_adjusted'].")";
		else
			$str_quantity = $row['stock_current'];

	} else {

		if ($row['is_decimal'] > 0)
			$str_quantity = "(-".$row['stock_adjusted'].")";
		else
			$str_quantity = $row['stock_current'];
	}
	
	$str_data .= StuffWithCharacter($row['code'], ' ', 8)." ".
		PadWithCharacter($row['description'], ' ', 23)." ".
		PadWithCharacter($row['category_description'], ' ', 15)." ".
		$str_bprice." ".
		StuffWithCharacter(sprintf("%01.2f",$row['selling_price'],3), ' ', 8)." ".
		$str_tax_columns.
		StuffWithCharacter($str_quantity, ' ', 8)." ".
		$str_value_columns;

}


/*
	remove trailing line break
*/
$str_data = substr($str_data, 0, strlen($data)-1);


$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

if ($str_include_value == 'Y')
	if ($str_include_bprice == 'Y')
		$str_value_totals = "Buying Value : Rs. ".sprintf("%01.2f", $data['total_b_value'])."\nSelling Value : Rs. ".sprintf("%01.2f", $data['total_s_value']).$str_eject_lines."%n";
	else
		$str_value_totals = "Selling Value : Rs. ".sprintf("%01.2f", $data['total_s_value']).$str_eject_lines."%n";
else
	$str_value_totals = $str_eject_lines."%n";

$str_statement = "%c".
	$str_application_title."\n".
	$str_print_address."\n".
	$str_print_phone."\n\n".
	$str_supplier."\n".
	$str_top."\n".
	$str_header."\n".
	$str_bottom."\n".
	$str_data."\n".
	$str_top."\n".
	"Total Stock: ".sprintf("%01.3f", $data['total_stock'])."\n".
	"Total Adjusted: ".sprintf("%01.3f", $data['total_adjusted'])."\n".
	$str_value_totals;

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
    <td>
      <br>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>

	  <input type="hidden" name="os" value="<? echo $os;?>"><br>
	  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
	  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>

    </td>
  </tr>

</table>

</form>

<script language="JavaScript">
	printerForm.submit();
</script>

</body>
</html>