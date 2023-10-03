<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("bill_funcs.inc.php");
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
    $int_eject_lines = 12;
    if ($sql_settings->RowCount() > 0) {
            $int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
            $str_print_address = $sql_settings->FieldByName('bill_print_address');
            $str_print_phone = $sql_settings->FieldByName('bill_print_phone');
    }
    
    if (IsSet($_GET["selected_day"]))
            $int_cur_day = $_GET["selected_day"];
    else
            $int_cur_day = date('j');
    
    if (IsSet($_GET["closing_time"]))
            $str_closing_time = $_GET["closing_time"];
    else
            $str_closing_time = "12:00:00";
            
    $_SESSION["int_bills_menu_selected"] = 6;

    // get which types that can be billed
    $qry = new Query("
            SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account
            FROM stock_storeroom
            WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
    ");
    $bool_cash = false;
    $bool_fs = false;
    $bool_pt = false;
    $int_cols = 1;
    if ($qry->FieldByName('can_bill_cash') == 'Y') {
            $bool_cash = true;
            $int_cols++;
    }
    if ($qry->FieldByName('can_bill_fs_account') == 'Y') {
            $bool_fs = true;
            $int_cols++;
    }
    if ($qry->FieldByName('can_bill_pt_account') == 'Y') {
            $bool_pt = true;
            $int_cols++;
    }
    $int_cols++;
    
    $flt_cash_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_CASH);
    $flt_cash_morning_consignment = get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_CASH);
    $flt_cash_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_CASH);
    $flt_cash_evening_consignment = get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_CASH);
    $flt_cash_morning_total =	$flt_cash_morning_consignment + $flt_cash_morning_direct;
    $flt_cash_evening_total = 	$flt_cash_evening_consignment + $flt_cash_evening_direct;

    $flt_fs_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_ACCOUNT);
    $flt_fs_morning_consignment = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_ACCOUNT);
    $flt_fs_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_ACCOUNT);
    $flt_fs_evening_consignment = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_ACCOUNT);
    $flt_fs_morning_total = 	$flt_fs_morning_direct + $flt_fs_morning_consignment;
    $flt_fs_evening_total = 	$flt_fs_evening_direct + $flt_fs_evening_consignment;

    $flt_pt_morning_direct = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'N', BILL_PT_ACCOUNT);
    $flt_pt_morning_consignment = 	get_item_totals('Y', 'Y', 'Y', $int_cur_day, 'Y', BILL_PT_ACCOUNT);
    $flt_pt_evening_direct = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'N', BILL_PT_ACCOUNT);
    $flt_pt_evening_consignment = 	get_item_totals('Y', 'Y', 'N', $int_cur_day, 'Y', BILL_PT_ACCOUNT);
    $flt_pt_morning_total = 	$flt_pt_morning_consignment + $flt_pt_morning_direct;
    $flt_pt_evening_total = 	$flt_pt_evening_consignment + $flt_pt_evening_direct;
    
    $flt_morning_direct_total = 	$flt_cash_morning_direct + $flt_fs_morning_direct + $flt_pt_morning_direct;
    $flt_morning_consignment_total = $flt_cash_morning_consignment + $flt_fs_morning_consignment + $flt_pt_morning_consignment;
    $flt_evening_direct_total = 	$flt_cash_evening_direct + $flt_fs_evening_direct + $flt_pt_evening_direct;
    $flt_evening_consignment_total = $flt_cash_evening_consignment + $flt_fs_evening_consignment + $flt_pt_evening_consignment;
    
    $flt_sales_promotion =		get_sales_promotion($int_cur_day);

?>    

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
$str_title = "Daily Sales for ".$int_cur_day.", ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];

$str_top = "";
$str_top = PadWithCharacter($str_top, '=', 80);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', 80);


$str_header = '';
$str_header = PadWithCharacter($str_header, ' ', 20);
if ($bool_cash == true)
    $str_header .= StuffWithCharacter('Cash', ' ', 15);
if ($bool_fs == true)
    $str_header .= StuffWithCharacter('F.S.', ' ', 15);
if ($bool_pt == true)
    $str_header .= StuffWithCharacter('P.T.', ' ', 15);
$str_header .= StuffWithCharacter('Total', ' ', 15);

$str_statement = '';
$str_statement .= "Morning Sales\n";
$str_statement .= PadWithCharacter('Direct', ' ', 20);
if ($bool_cash == true)
    $str_statement .= StuffWithCharacter(number_format($flt_cash_morning_direct, 2, '.', ','), ' ', 15);
if ($bool_fs == true)
    $str_statement .= StuffWithCharacter(number_format($flt_fs_morning_direct, 2, '.', ','), ' ', 15);
if ($bool_pt == true)
    $str_statement .= StuffWithCharacter(number_format($flt_pt_morning_direct, 2, '.', ','), ' ', 15);
$str_statement .= StuffWithCharacter(number_format($flt_morning_direct_total, 2, '.', ','), ' ', 15)."\n";

$str_statement .= PadWithCharacter('Consignment', ' ', 20);
if ($bool_cash == true)
    $str_statement .= StuffWithCharacter(number_format($flt_cash_morning_consignment, 2, '.', ','), ' ', 15);
if ($bool_fs == true)
    $str_statement .= StuffWithCharacter(number_format($flt_fs_morning_consignment, 2, '.', ','), ' ', 15);
if ($bool_pt == true)
    $str_statement .= StuffWithCharacter(number_format($flt_pt_morning_consignment, 2, '.', ','), ' ', 15);
$str_statement .= StuffWithCharacter(number_format($flt_morning_consignment_total, 2, '.', ','), ' ', 15)."\n";

$str_statement .= PadWithCharacter("", ' ', 20);
if ($bool_cash == true)
    $str_statement .= StuffWithCharacter(number_format($flt_cash_morning_total, 2, '.', ','), ' ', 15);
if ($bool_fs == true)
    $str_statement .= StuffWithCharacter(number_format($flt_fs_morning_total, 2, '.', ','), ' ', 15);
if ($bool_pt == true)
    $str_statement .= StuffWithCharacter(number_format($flt_pt_morning_total, 2, '.', ','), ' ', 15);
$str_statement .= "\n".$str_bottom."\n";
    
$str_statement .= "Evening Sales\n";
$str_statement .= PadWithCharacter('Direct', ' ', 20);
if ($bool_cash == true)
    $str_statement .= StuffWithCharacter(number_format($flt_cash_evening_direct, 2, '.', ','), ' ', 15);
if ($bool_fs == true)
    $str_statement .= StuffWithCharacter(number_format($flt_fs_evening_direct, 2, '.', ','), ' ', 15);
if ($bool_pt == true)
    $str_statement .= StuffWithCharacter(number_format($flt_pt_evening_direct, 2, '.', ','), ' ', 15);
$str_statement .= StuffWithCharacter(number_format($flt_evening_direct_total, 2, '.', ','), ' ', 15)."\n";

$str_statement .= PadWithCharacter('Consignment', ' ', 20);
if ($bool_cash == true)
    $str_statement .= StuffWithCharacter(number_format($flt_cash_evening_consignment, 2, '.', ','), ' ', 15);
if ($bool_fs == true)
    $str_statement .= StuffWithCharacter(number_format($flt_fs_evening_consignment, 2, '.', ','), ' ', 15);
if ($bool_pt == true)
    $str_statement .= StuffWithCharacter(number_format($flt_pt_evening_consignment, 2, '.', ','), ' ', 15);
$str_statement .= StuffWithCharacter(number_format($flt_evening_consignment_total, 2, '.', ','), ' ', 15)."\n";

$str_statement .= PadWithCharacter("", ' ', 20);
if ($bool_cash == true)
    $str_statement .= StuffWithCharacter(number_format($flt_cash_evening_total, 2, '.', ','), ' ', 15);
if ($bool_fs == true)
    $str_statement .= StuffWithCharacter(number_format($flt_fs_evening_total, 2, '.', ','), ' ', 15);
if ($bool_pt == true)
    $str_statement .= StuffWithCharacter(number_format($flt_pt_evening_total, 2, '.', ','), ' ', 15);
$str_statement .= "\n".$str_bottom."\n";

$str_statement .= PadWithCharacter('Sales Promotion', ' ', 20);
$str_statement .= StuffWithCharacter(number_format($flt_sales_promotion, 2, '.', ','), ' ', 15);

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "%c
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_title."
".$str_top."
".$str_header."
".$str_bottom."
".$str_statement."
".$str_top.$str_eject_lines."%n";

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
      &nbsp;<font class='title'>Printing</font>
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
