<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    $_SESSION["int_bills_menu_selected"] = 6;
    
    $int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
    
    $qry = new Query("SELECT * FROM stock_product LIMIT 1");
    $arr_data = array();
    
    $flt_gtotal_buying_value = 0;
    $flt_gtotal_selling_value = 0;
    $flt_gtotal_discount_value = 0;
    $flt_gtotal_promotion = 0;

    for ($i=1; $i<=$int_num_days; $i++) {

        $str_query = "
                SELECT bi.discount, IF(b.is_debit_bill = 'Y',
                        (SUM(bi.quantity + bi.adjusted_quantity)* bi.price * -1),
                        (SUM(bi.quantity + bi.adjusted_quantity)* bi.price)
                       ) AS selling_value,
                    IF(b.is_debit_bill = 'Y',
                        (SUM(bi.quantity + bi.adjusted_quantity) * bi.bprice * -1),
                        (SUM(bi.quantity + bi.adjusted_quantity) * bi.bprice)
                    ) AS buying_value,
                    IF(bi.discount > 0,
                        ((SUM(bi.quantity + bi.adjusted_quantity) * bi.price) * (bi.discount/100)),
                        0
                    ) AS discount_value
                FROM ".Monthalize('bill')." b
                INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
                WHERE (DATE(b.date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $i)."')
                	AND (b.bill_status <> ".BILL_STATUS_CANCELLED.")
                GROUP BY bi.product_id, bi.discount
        ";
        $qry->Query($str_query);

        $flt_total_buying_value = 0;
        $flt_total_selling_value = 0;
        $flt_total_discount_value = 0;

        if ($qry->RowCount() > 0) {

            for ($j=0;$j<$qry->RowCount();$j++) {

                $flt_total_buying_value += $qry->FieldByName('buying_value');
                $flt_total_selling_value += $qry->FieldByName('selling_value');
                $flt_total_discount_value += $qry->FieldByName('discount_value');
                
                $qry->Next();
            }
        }
        
        $str_query = "
            SELECT SUM(bill_promotion) AS promotion
            FROM ".Monthalize('bill')." b
            WHERE (DATE(b.date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $i)."')
        ";
        $qry->Query($str_query);
        
        $flt_gtotal_buying_value += $flt_total_buying_value;
        $flt_gtotal_selling_value += $flt_total_selling_value;
        $flt_gtotal_discount_value += $flt_total_discount_value;
        $flt_gtotal_promotion += $qry->FieldByName('promotion');
        
        $arr_data[$i][0] = $i;
        $arr_data[$i][1] = number_format($flt_total_buying_value,2,'.',',');
        $arr_data[$i][2] = number_format($flt_total_selling_value,2,'.',',');
        $arr_data[$i][3] = number_format($flt_total_discount_value,2,'.',',');
        $arr_data[$i][4] = number_format($qry->FieldByName('promotion'),2,'.',',');
        $arr_data[$i][5] = number_format(($flt_total_selling_value - $flt_total_buying_value - $flt_total_discount_value - $qry->FieldByName('promotion')),2,'.',',');
    }
?>

<hmtl>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor'>
    <table border='1' cellpadding='7' cellspacing='0'>
        <tr>
            <td class='normaltext_bold' width='60px'>Day</td>
            <td class='normaltext_bold' width='120px' align='right'>Buying<br>Value</td>
            <td class='normaltext_bold' width='120px' align='right'>Selling<br>Value</td>
            <td class='normaltext_bold' width='120px' align='right'>Discount<br>Value</td>
            <td class='normaltext_bold' width='120px' align='right'>Promotions</td>
            <td class='normaltext_bold' width='120px' align='right'>Difference</td>
        </tr>
        <?
            for ($i=1;$i<=count($arr_data);$i++) {
                if ($i % 2 == 0)
                    $str_color="#eff7ff";
                else
                    $str_color="#deecfb";
                
                echo "<tr bgcolor='$str_color'>";
                echo "<td class='normaltext' align='right'>".$arr_data[$i][0]."</td>";
                echo "<td class='normaltext' align='right'>".$arr_data[$i][1]."</td>";
                echo "<td class='normaltext' align='right'>".$arr_data[$i][2]."</td>";
                echo "<td class='normaltext' align='right'>".$arr_data[$i][3]."</td>";
                echo "<td class='normaltext' align='right'>".$arr_data[$i][4]."</td>";
                echo "<td class='normaltext' align='right'>".$arr_data[$i][5]."</td>";
                echo "</tr>\n";
            }
        ?>
    </table>
</body>
</html>

<script language='javascript'>
parent.frames['footer'].document.location = 'profit_statement_footer.php?total_buying=<?echo number_format($flt_gtotal_buying_value,2,'.','')?>&total_selling=<?echo number_format($flt_gtotal_selling_value,2,'.','')?>&total_discount=<?echo number_format($flt_gtotal_discount_value,2,'.','')?>&total_promotion=<?echo number_format($flt_gtotal_promotion,2,'.','')?>';
</script>