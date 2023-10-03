<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
    require_once("../include/browser_detection.php");
    require_once("../common/printer.inc.php");
    require_once("../common/print_funcs.inc.php");

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

    $int_supplier_id = 0;
    if (IsSet($_GET['supplier_id']))
        $int_supplier_id = $_GET['supplier_id'];

    $int_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
    
    if ($int_supplier_id == 'ALL') {
        $str_query = "
            SELECT *
            FROM ".Yearalize('purchase_order')." po
            LEFT JOIN stock_supplier ss ON (ss.supplier_id = po.supplier_id)
            WHERE (DATE(po.date_received) BETWEEN '".getMYSQLDate(1)."' AND '".getMYSQLDate($int_days)."')
            ORDER BY supplier_name";
    }
    else {
        $str_query = "
            SELECT *
            FROM ".Yearalize('purchase_order')." po
            LEFT JOIN stock_supplier ss ON (ss.supplier_id = po.supplier_id)
            WHERE po.supplier_id = $int_supplier_id
            ORDER BY supplier_name";
    }

    $qry_supplier = new Query($str_query);

    function getMYSQLDate($int_day) {
        $str_retval = sprintf("%04d-%02d-%02d", $_SESSION['int_year_loaded'], $_SESSION['int_month_loaded'], $int_day);
        return $str_retval;
    }

    function get_tax_amount($int_tax_id, $flt_buying_price) {
        return calculateTax($flt_buying_price, $int_tax_id);
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

    $str_single = '';
    $str_single = PadWithCharacter($str_single, '-', 80);
    
    $str_double = '';
    $str_double = PadWithCharacter($str_double, '=', 80);
    
    $str_header = PadWithCharacter('Supplier', ' ', 29)." ".
        PadWithCharacter('Reference', ' ', 9)." ".
        PadWithCharacter('Received', ' ', 9)." ".
        StuffWithCharacter('Value', ' ', 9)." ".
        StuffWithCharacter('Tax Value', ' ', 9)." ".
        StuffWithCharacter('Total', ' ', 9);

    $str_data = '';

    $qry_total = new Query("SELECT * FROM stock_supplier LIMIT 1");
    
    $flt_total = 0;
    $flt_tax_total = 0;
    $flt_grand_total = 0;
    for ($i=0;$i<$qry_supplier->RowCount();$i++) {
        if ($i % 2 == 0)
            $str_color="#eff7ff";
        else
            $str_color="#deecfb";
        
        $qry_total->Query("
            SELECT pi.tax_id, pi.buying_price, SUM(pi.quantity_received + pi.quantity_bonus) AS total_quantity, (SUM(pi.quantity_received + pi.quantity_bonus) * pi.buying_price) AS amount
            FROM ".Yearalize('purchase_items')." pi
            WHERE pi.purchase_order_id = ".$qry_supplier->FieldByName('purchase_order_id')."
            GROUP BY pi.product_id
        ");
        
        $flt_total_amount = 0;
        $flt_total_tax_amount = 0;
        for ($j=0;$j<$qry_total->RowCount();$j++) {
            $flt_total_amount += $qry_total->FieldByName('amount');
            $flt_total_tax_amount += get_tax_amount($qry_total->FieldByName('tax_id'), $qry_total->FieldByName('buying_price')) * $qry_total->FieldByName('total_quantity');
            
            $qry_total->Next();
        }
        
        $str_data .= PadWithCharacter($qry_supplier->FieldByName('supplier_name'), ' ', 29)." ".
            PadWithCharacter($qry_supplier->FieldByName('purchase_order_ref'), ' ', 9)." ".
            PadWithCharacter(makeHumanTime($qry_supplier->FieldByName('date_received')), ' ', 9)." ".
            StuffWithCharacter(number_format($flt_total_amount,2,'.',','), ' ', 9)." ".
            StuffWithCharacter(number_format($flt_total_tax_amount,2,'.',','), ' ', 9)." ".
            StuffWithCharacter(number_format(($flt_total_amount+$flt_total_tax_amount),2,'.',','), ' ' ,9)."\n";
        
        $flt_total += $flt_total_amount;
        $flt_tax_total += $flt_total_tax_amount;
        $flt_grand_total += ($flt_total_amount+$flt_total_tax_amount);
        
        $qry_supplier->Next();
    }

$str_totals = StuffWithCharacter('Totals', ' ', 49)." ".
            StuffWithCharacter(number_format($flt_total,2,'.',','), ' ', 9)." ".
            StuffWithCharacter(number_format($flt_tax_total,2,'.',','), ' ', 9)." ".
            StuffWithCharacter(number_format($flt_grand_total,2,'.',','), ' ', 9);



$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "%c
".$str_application_title."
".$str_application_title2."
".$str_print_address."
".$str_print_phone."

".$str_double."
".$str_header."
".$str_single."
".$str_data."
".$str_single."
".$str_totals."
".$str_double.$str_eject_lines."%n";

$str_statement = replaceSpecialCharacters($str_statement);

?>


<PRE>
<?
 echo $str_statement;
?>
</PRE>


<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/pourtous/print.php">
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
