<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");

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

//echo $str_query;

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
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

    <table width='100%' border='0'>
    <tr><td align='center'>
    
    <table border='1' cellpadding='7' cellspacing='0'>
        <tr bgcolor='lightgrey'>
            <td class='normaltext'><b>Supplier</td>
            <td align='center' class='normaltext'><b>P.O.<br>Reference</td>
            <td align='center' class='normaltext'><b>Date<br>Received</td>
            <td align='center' class='normaltext'><b>Purchase<br>Value</b></td>
            <td align='center' class='normaltext'><b>Purchase<br>Tax Value</b></td>
            <td align='center' class='normaltext'><b>Total</td>
        </tr>
        <?
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
                
                echo "<tr bgcolor='$str_color'>";
                echo "<td class='normaltext'>".$qry_supplier->FieldByName('supplier_name')."</td>";
                echo "<td class='normaltext'>".$qry_supplier->FieldByName('purchase_order_ref')."</td>";
                echo "<td class='normaltext'>".makeHumanTime($qry_supplier->FieldByName('date_received'))."</td>";
                echo "<td align='right' class='normaltext'>".number_format($flt_total_amount,2,'.',',')."</td>";
                echo "<td align='right' class='normaltext'>".number_format($flt_total_tax_amount,2,'.',',')."</td>";
                echo "<td align='right' class='normaltext'>".number_format(($flt_total_amount+$flt_total_tax_amount),2,'.',',')."</td>";
                echo "</tr>\n";
                
                $flt_total += $flt_total_amount;
                $flt_tax_total += $flt_total_tax_amount;
                $flt_grand_total += ($flt_total_amount+$flt_total_tax_amount);
                
                $qry_supplier->Next();
            }
        ?>
        <tr bgcolor='lightgrey'>
            <td align='right' colspan='3' class='normaltext'><b>Totals</b></td>
            <td align='right' class='normaltext'><b><?echo number_format($flt_total,2,'.',',');?></b></td>
            <td align='right' class='normaltext'><b><?echo number_format($flt_tax_total,2,'.',',');?></b></td>
            <td align='right' class='normaltext'><b><?echo number_format($flt_grand_total,2,'.',',');?></b></td>
        </tr>
    </table>

    </td></tr>
    </table>
</body>
</html>