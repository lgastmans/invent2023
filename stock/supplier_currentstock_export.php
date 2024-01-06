<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");


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





	$filename = "order_".$qry_supplier->FieldByName('supplier_name').".csv";

	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$delimiter = "|"; //"\t";	



if ($_SESSION["int_month_loaded"] <> date('m'))
	$int_day = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
else
	$int_day = date('j');

if ($str_is_filtered == 'Y')
$str_supplier = "Statement of current stock as on ".$int_day." ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]." for ".$qry_supplier->FieldByName('supplier_name')." \nShowing ".$str_display_stock." stock".$str_storeroom_info."\nFiltered on ".$str_filter_field." for ".$str_filter_text;
else
$str_supplier = "Statement of current stock as on ".$int_day." ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]." for ".$qry_supplier->FieldByName('supplier_name')." \nShowing ".$str_display_stock." stock".$str_storeroom_info;



if ($str_include_bprice == 'Y')
	$str_bprice_header = 'B Price';
else
	$str_bprice_header = "";
	
if ($str_include_tax == 'Y')
	$str_tax_header = 'P/Tax'.$delimiter.'M.R.P.'.$delimiter.'Tax%';
else
	$str_tax_header = 'M.R.P.'." ";
	
if ($str_include_value == 'Y')
	if ($str_include_bprice == 'Y')
		$str_value_header = 'B Value'.$delimiter.'S Value';
	else
		$str_value_header = 'S Value';
else
	$str_value_header = "";


$str_header = 'Code'.$delimiter.
  'Description'.$delimiter.
  'Category'.$delimiter.
  $str_bprice_header.$delimiter.
  'S Price'.$delimiter.
  $str_tax_header.$delimiter.
  'Stock'.$delimiter.
  $str_value_header;


$str_data = "";


foreach ($data['data'] as $row) {

			
	if ($i % 5 == 0)
		$str_data .= $str_bottom."\n";

	if ($str_include_bprice == 'Y')
		$str_bprice = sprintf("%01.2f",$row['buying_price'],3);
	else
		$str_bprice = "";
		
	if ($str_include_tax == 'Y')
		$str_tax_columns = sprintf("%01.2f",$row['price_tax'],3).$delimiter.
			$row['mrp'].$delimiter.
			$row['tax_description'];
	else
		$str_tax_columns = $row['mrp'].$delimiter;

	if ($str_include_value == 'Y')
		if ($str_include_bprice == 'Y')
			$str_value_columns = sprintf("%01.2f",$row['buying_value'],3).$delimiter.
				sprintf("%01.2f",$row['selling_value'],3)."\n";
		else
			$str_value_columns = sprintf("%01.2f",$row['selling_value'],3)."\n";
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
	
	$str_data .= $row['code'].$delimiter.
		$row['description'].$delimiter.
		$row['category_description'].$delimiter.
		$str_bprice.$delimiter.
		sprintf("%01.2f",$row['selling_price'],3).$delimiter.
		$str_tax_columns.$delimiter.
		$str_quantity.$delimiter.
		$str_value_columns;

}


if ($str_include_value == 'Y')
	if ($str_include_bprice == 'Y')
		$str_value_totals = "Buying Value : Rs. ".$delimiter.sprintf("%01.2f", $data['total_b_value']).$delimiter."\nSelling Value : Rs. ".$delimiter.sprintf("%01.2f", $data['total_s_value']);
	else
		$str_value_totals = "Selling Value : Rs. ".$delimiter.sprintf("%01.2f", $data['total_s_value']);
else
	$str_value_totals = "";

$str_statement = 
	"sep=|"."\n".
	$str_application_title."\n".
	$str_print_address."\n".
	$str_print_phone."\n\n".
	$str_supplier."\n".
	$str_top."\n".
	$str_header."\n".
	$str_bottom."\n".
	$str_data."\n".
	$str_top."\n".
	"Total Stock: ".$delimiter.sprintf("%01.3f", $data['total_stock'])."\n".
	"Total Adjusted: ".$delimiter.sprintf("%01.3f", $data['total_adjusted'])."\n".
	$str_value_totals;

//$str_statement = replaceSpecialCharacters($str_statement);



 echo $str_statement;



?>