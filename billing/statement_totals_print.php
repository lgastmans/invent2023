<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");	
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");

	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($sql_settings->RowCount() > 0) {
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	if (IsSet($_GET["supplier_type"]))
		$str_supplier_type = $_GET["supplier_type"];
	else
		$str_supplier_type = 0;

	$str_include_tax = 'Y';

	$qry_supplier = new Query("
		SELECT ss.supplier_id, ss.supplier_name, ss.account_number, ss.trust, ss.is_active,
			SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) AS amount,
					
			ROUND((SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent / 100), 2) AS commission,
			
			ROUND((SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_2 / 100), 2) AS commission2,
			
			ROUND((SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_3 / 100), 2) AS commission3,
			
			(SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) -
				(
					ROUND((SUM(IF(bi.discount > 0, 
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent / 100), 2)) -
					ROUND((SUM(IF(bi.discount > 0, 
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_2 / 100), 2) -
					ROUND((SUM(IF(bi.discount > 0, 
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_3 / 100), 2)
				)
				AS total
		FROM ".Monthalize('bill')." b, 
			".Monthalize('bill_items')." bi, 
			stock_product sp, 
			".Yearalize('stock_batch')." sb,
			stock_supplier ss
		WHERE (bi.bill_id = b.bill_id)
			AND (b.bill_status = ".BILL_STATUS_RESOLVED.")
			AND (sp.product_id = bi.product_id)
			AND (sb.product_id = bi.product_id)
			AND (sb.supplier_id = ss.supplier_id)
			AND (sb.batch_id = bi.batch_id)
			AND (ss.is_supplier_delivering = '$str_supplier_type')
		GROUP BY sb.supplier_id
		ORDER BY supplier_name
	");

	$company = new Query("SELECT * FROM company WHERE 1");	

?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
if ($str_supplier_type == 'Y')
	$str_title = "Consignment Supplier Statement for ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];
else
	$str_title = "Direct Supplier Statement for ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];


$str_top = "";
$str_top = PadWithCharacter($str_top, '=', 134);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', 134);

$str_header = PadWithCharacter('Supplier', ' ', 30)." ".
  PadWithCharacter('Account No.', ' ', 12)." ".
  StuffWithCharacter('Amount', ' ', 12)." ".
  StuffWithCharacter('Commission', ' ', 12)." ".
  StuffWithCharacter('Commission 2', ' ', 12)." ".
  StuffWithCharacter('Commission 3', ' ', 12)." ".
  StuffWithCharacter('Given', ' ', 12)." ".
  StuffWithCharacter('B.P. Tax', ' ', 12)." ".
  StuffWithCharacter('Total', ' ', 12);

$str_data = "";
	$flt_total_amount = 0;
	$flt_total_commission = 0;
	$flt_grand_total = 0;

	for ($i=0;$i<$qry_supplier->RowCount();$i++) {

	if ($i % 2 == 1) 
		$bgcolor = "#dfdfdf";
	else 
		$bgcolor = "#ffffff"; 

	echo "<tr bgcolor=".$bgcolor.">";


	/*
		get the total tax to be paid to the supplier,
		based on the buying price
	*/
	$sql = "
		SELECT 
			sp.product_id,
			bi.price,
			bi.batch_id,
			bi.tax_id,
			bi.discount,
			ROUND(bi.quantity + bi.adjusted_quantity, 3) AS quantity
		FROM ".Monthalize('bill')." b,
			".Monthalize('bill_items')." bi,
			stock_product sp,
			".Yearalize('stock_batch')." sb,
			".Monthalize('stock_tax')." st
		WHERE (bi.bill_id = b.bill_id)
			AND (
				(b.bill_status = ".BILL_STATUS_RESOLVED.")
				OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
			)
			AND (sp.product_id = bi.product_id)
			AND (sb.product_id = bi.product_id)
			AND (sb.supplier_id = ".$qry_supplier->FieldByName('supplier_id').")
			AND (sb.batch_id = bi.batch_id)
			AND (bi.tax_id = st.tax_id)
	";

	$qry_items = new Query($sql);

	$total_tax = 0;

	for ($j=0;$j<$qry_items->RowCount();$j++) {
		
		$quantity = $qry_items->FieldByName('quantity');
		$flt_price = getBuyingPrice($qry_items->FieldByName('product_id'), $qry_items->FieldByName('batch_id'));
		$flt_price = number_format($flt_price, 2,'.','');
		//$flt_price = number_format($qry_items->FieldByName('bprice'), 2,'.','');
			
		$discount = $qry_items->FieldByName('discount');

		if ($str_include_tax == 'Y') {

			$tax_id = $qry_items->FieldByName('tax_id');
			
			if ($discount > 0) {
				$discount_price = round(($flt_price * (1 - ($discount/100))), 3);
				$tax_amount = calculateTax($quantity * $discount_price, $tax_id);
				$flt_amount = round(($quantity * $discount_price + $tax_amount), 3);
			}
			else {
				$discount_price = $flt_price;
				$tax_amount = calculateTax($flt_price * $quantity, $tax_id);
				$flt_amount = round(($quantity * $flt_price + $tax_amount), 3);
			}
			$flt_amount = number_format($flt_amount, 2, '.', '');
		}
		else {
			if ($discount > 0) {
				$flt_amount = number_format(($flt_price * (1 - ($discount/100)) * $quantity), 2, '.','');
			}
			else {
				$flt_amount = number_format(($flt_price * $quantity), 2, '.','');
			}
		}

		$total_tax += $tax_amount;

		$qry_items->Next();
	}


	/*
		if the supplier is registed under the same trust as the company
		the tax is not applicable
	*/
	if ($qry_supplier->FieldByName('trust') == $company->FieldByName('trust')) {

		$str_data .= PadWithCharacter($qry_supplier->FieldByName('supplier_name'),' ', 30)." ".
			PadWithCharacter($qry_supplier->FieldByName('account_number'),' ', 12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('amount'),' ', 12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('commission'),' ', 12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('commission2'),' ',12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('commission3'),' ',12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('total'),' ', 12)." ".
			StuffWithCharacter("N/A",' ', 12)." ".
			StuffWithCharacter(number_format(($total_tax + $qry_supplier->FieldByName('total')) ,2,'.',','),' ', 12)."\n";
	}
	else {
		$str_data .= PadWithCharacter($qry_supplier->FieldByName('supplier_name'),' ', 30)." ".
			PadWithCharacter($qry_supplier->FieldByName('account_number'),' ', 12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('amount'),' ', 12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('commission'),' ', 12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('commission2'),' ',12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('commission3'),' ',12)." ".
			StuffWithCharacter($qry_supplier->FieldByName('total'),' ', 12)." ".
			StuffWithCharacter(number_format($total_tax,2,'.',','),' ', 12)." ".
			StuffWithCharacter(number_format(($total_tax + $qry_supplier->FieldByName('total')) ,2,'.',','),' ', 12)."\n";
	}

	$flt_total_amount = $flt_total_amount + $qry_supplier->FieldByName('amount');
	$flt_total_commission = $flt_total_commission +
		$qry_supplier->FieldByName('commission') +
		$qry_supplier->FieldByName('commission2') +
		$qry_supplier->FieldByName('commission3');

	$flt_grand_total = $flt_grand_total + $qry_supplier->FieldByName('total');
				
	$qry_supplier->Next();
}

$str_grand_totals = "Amount : ".number_format($flt_total_amount, 2, '.', ',')."\nCommission : ".number_format($flt_total_commission, 2, '.', ',')."\nTotal : ".number_format($flt_grand_total, 2, '.', ',');

$str_statement = "%c
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_title."
".$str_top."
".$str_header."
".$str_bottom."
".$str_data."
".$str_bottom."
".$str_grand_totals."
".$str_top;

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