<?
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");
	
	$str_code = "";
	if (IsSet($_GET["product_code"]))
		$str_code = $_GET["product_code"];

	//======================
	// top summary
	//----------------------
	$qry_product = new Query("
		SELECT *
		FROM stock_product sp, stock_measurement_unit mu, ".Monthalize('stock_storeroom_product')." ssp
		WHERE product_code = '".$str_code."'
			AND (sp.deleted = 'N')
			AND (sp.measurement_unit_id = mu.measurement_unit_id)
			AND (ssp.product_id = sp.product_id)
	");
	$flt_adjusted_stock = $qry_product->FieldByName('stock_adjusted');

	if ($qry_product->RowCount() > 0) {
		$str_unit = $qry_product->FieldByName('measurement_unit');
		
		$str_query = "
		SELECT *
		FROM ".Yearalize('stock_balance')."
		WHERE (product_id = ".$qry_product->FieldByName('product_id').")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (balance_month = ".$_SESSION["int_month_loaded"].")
			AND (balance_year = ".$_SESSION["int_year_loaded"].")
		";
		$qry_summary = new Query($str_query);
	}


	//======================
	// top summary
	//----------------------
	$str_filter = "N";
	if (IsSet($_GET["filter"])) {
		$str_filter = $_GET["filter"];
		$str_from = $_GET["from"];
		$str_to = $_GET["to"];
	}
	
	$str_filter_type = "N";
	if (IsSet($_GET["filter_type"])) {
		$str_filter_type = $_GET["filter_type"];
		$str_filter_type_value = $_GET["filter_type_value"];
	}
	
	$qry_product = new Query("
		SELECT *
		FROM stock_product sp, stock_measurement_unit mu
		WHERE product_code = '".$str_code."'
			AND (sp.measurement_unit_id = mu.measurement_unit_id)
	");
	
	if ($qry_product->RowCount() > 0) {
		$str_unit = $qry_product->FieldByName('measurement_unit');
		
		$str_where = "";
		if ($str_filter == 'Y') {
			$str_where .= "
				AND (DATE(st.date_created)
					BETWEEN '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_from)."'
					AND '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_to)."'
				)
			";
		}
		
		if ($str_filter_type == "Y") {
			$str_where .= "
				AND (stt.transfer_type = $str_filter_type_value)
			";
		}
		
		$str_query = "
			SELECT *
			FROM ".Monthalize('stock_transfer')." st, stock_transfer_type stt, user
			WHERE (product_id = ".$qry_product->FieldByName('product_id').")
				AND (st.transfer_type = stt.transfer_type)
		                AND ((st.storeroom_id_from = ".$_SESSION['int_current_storeroom'].") OR (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))
				AND (st.user_id = user.user_id)".$str_where."
			ORDER BY date_created";
		$qry_details = new Query($str_query);
	}

?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
$str_statement = 
	"%c%b".$qry_product->FieldByName('product_description')." (".$str_unit.")%b\n\n".
	"Opening Balance: ".number_format($qry_summary->FieldByName('stock_opening_balance'),3,'.',',')."\n".
	"Closing Balance: ".number_format($qry_summary->FieldByName('stock_closing_balance'),3,'.',',')."\n".
	"Stock Mismatch Additions: ".number_format($qry_summary->FieldByName('stock_mismatch_addition'),3,'.',',')."\n".
	"Stock Mismatch Deductions:".number_format($qry_summary->FieldByName('stock_mismatch_deduction'),3,'.',',')."\n".
	"Stock Sold:".number_format($qry_summary->FieldByName('stock_sold'),3,'.',',')."\n".
	"Stock Returned:".number_format($qry_summary->FieldByName('stock_returned'),3,'.',',')."\n".
	"Stock Received:".number_format($qry_summary->FieldByName('stock_received'),3,'.',',')."\n".
	"Stock Adjusted:".number_format($flt_adjusted_stock,3,'.',',')."\n".
	"Stock Cancelled:".number_format($qry_summary->FieldByName('stock_cancelled'),3,'.',',')."\n".
	"Storeroom Received:".number_format($qry_summary->FieldByName('stock_in'),3,'.',',')."\n".
	"Storeroom Dispatched:".number_format($qry_summary->FieldByName('stock_out'),3,'.',',')."\n\n";

	$str_top = "";
	$str_top .= PadWithCharacter($str_top, '=', 105);
	$str_statement .= $str_top."\n";

	$str_statement .= PadWithCharacter('Date', ' ', 12)." ".
		PadWithCharacter('Type', ' ', 20)." ".
		StuffWithCharacter('Quantity', ' ', 12)." ".
		PadWithCharacter('Description', ' ', 30)." ".
		PadWithCharacter('User', ' ', 10)." ".
		PadWithCharacter('Status', ' ', 10)."\n";

	$str_top = "";
	$str_top .= PadWithCharacter($str_top, '-', 105);
	$str_statement .= $str_top."\n";

	$flt_total_quantity = 0;
	for ($i = 0; $i < $qry_details->RowCount(); $i++) {
		$str_statement .=
			PadWithCharacter(makeHumanTime($qry_details->FieldByName('date_created')), ' ', 12)." ".
			PadWithCharacter($qry_details->FieldByName('transfer_type_description'), ' ', 20)." ".
			StuffWithCharacter(number_format($qry_details->FieldByName('transfer_quantity'),3,'.',',')." ".$str_unit, ' ', 12)." ".
			PadWithCharacter($qry_details->FieldByName('transfer_description'), ' ', 30)." ".
			PadWithCharacter($qry_details->FieldByName('username'), ' ', 10)." ";

		if ($qry_details->FieldByName('transfer_status') == 1)
			$str_statement .= "Requested\n";
		else if ($qry_details->FieldByName('transfer_status') == 2)
			$str_statement .= "Dispatched\n";
		else if ($qry_details->FieldByName('transfer_status') == 3)
			$str_statement .= "Completed\n";
		else if ($qry_details->FieldByName('transfer_status') == 4)
			$str_statement .= "Cancelled\n";

		$flt_total_quantity = $flt_total_quantity + number_format($qry_details->FieldByName('transfer_quantity'),3,'.','');

		$qry_details->Next();
	}

	$str_top = "";
	$str_top .= PadWithCharacter($str_top, '=', 105);
	$str_statement .= $str_top."\n";

	$str_statement .= "Total Quantity: ".number_format($flt_total_quantity,3,'.',',')." ".$str_unit;
	$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
//	echo $str_statement;
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