<?
    include("../../include/const.inc.php");
    include("../../include/session.inc.php");
    include("../../include/db.inc.php");

    $str_query = "
        SELECT at.transfer_id, at.cc_id_from AS current_id, at.amount, at.date_created, DAY(at.date_created) AS current_day,
            ac.account_number, ac.account_name,
            m.description,
            b.bill_number
        FROM ".Monthalize('account_transfers')." at
        LEFT JOIN account_cc ac ON (ac.cc_id = at.cc_id_from)
        LEFT JOIN module m ON (m.module_id = at.module_id)
        LEFT JOIN ".Monthalize('bill')." b ON (b.bill_id = at.module_record_id)
        WHERE amount
            IN (
                SELECT amount
                FROM ".Monthalize('account_transfers')."
                WHERE (cc_id_from = current_id) AND (DAY(date_created) = current_day)
                GROUP BY amount
                HAVING COUNT(*) > 1
            )
        ORDER BY cc_id_from
    ";
    
    $qry = new Query($str_query);
    
?>
<html>
<head>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
    <script language='javascript'>
        function goBack() {
            document.location = '../index_verification_tools.php';
        }
    </script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
    $int_discrepancies = $qry->RowCount();
    $arr_found = array();
    
    for ($i=0;$i<$qry->RowCount();$i++) {
        
        $arr_found[$i][] = $qry->FieldByName('account_number');
        $arr_found[$i][] = $qry->FieldByName('account_name');
        $arr_found[$i][] = $qry->FieldByName('bill_number');
        $arr_found[$i][] = makeHumanTime($qry->FieldByName('date_created'));
        $arr_found[$i][] = $qry->FieldByName('amount');
        $arr_found[$i][] = $qry->FieldByName('description');
            
        $qry->Next();
    }
    
    boundingBoxStart("800", "../../images/blank.gif");
?>
    <br>
    <table border='0' cellpadding='0' cellspacing='5'>
        <tr>
            <td colspan='4' class='normaltext'>
                <? echo $int_discrepancies ?> possible discrepancies found<br><br>
            </td>
        </tr>
        <tr>
            <td class='normaltext' width='120px' align='right'><b>Number</b></td>
            <td class='normaltext' width='250px'><b>Name</b></td>
            <td class='normaltext' width='80px'><b>Bill</b></td>
            <td class='normaltext' width='140px'><b>Date</b></td>
            <td class='normaltext' width='120px' align='right'><b>Amount</b></td>
            <td class='normaltext' width='120px'><b>Type</b></td>
        </tr>
        <?
            for ($i=0;$i<count($arr_found);$i++) {
                if ($i % 2 == 0)
                    $str_color="#eff7ff";
                else
                    $str_color="#deecfb";

                echo "<tr bgcolor='$str_color'>";
                echo "<td class='normaltext' align='right'>".$arr_found[$i][0]."</td>";
                echo "<td class='normaltext'>".$arr_found[$i][1]."</td>";
                echo "<td class='normaltext'>".$arr_found[$i][2]."</td>";
                echo "<td class='normaltext'>".$arr_found[$i][3]."</td>";
                echo "<td class='normaltext' align='right'>".number_format($arr_found[$i][4],2,'.',',')."</td>";
                echo "<td class='normaltext'>".$arr_found[$i][5]."</td>";
                echo "</tr>";
            }
        ?>
        <tr>
            <td colspan='4'>
                <br>
                <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
                <br><br>
            </td>
        </tr>
    </table>
<?  
    boundingBoxEnd("800", "../../images/blank.gif");
?>

</body>
</html>