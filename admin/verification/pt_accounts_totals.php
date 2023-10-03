<?
    include("../../include/const.inc.php");
    include("../../include/session.inc.php");
    include("../../include/db.inc.php");

    $qry_accounts = new Query("
        SELECT ap.*, ab.opening_balance, ab.closing_balance
        FROM account_pt ap
        INNER JOIN ".Monthalize('account_pt_balances')." ab ON (ab.account_id = ap.account_id)
        ORDER BY account_number
    ");
    
    $qry_verify = new Query("SELECT * FROM account_pt LIMIT 1");
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
    $int_discrepancies = 0;
    $arr_found = array();
    
    for ($i=0;$i<$qry_accounts->RowCount();$i++) {
        $qry_verify->Query("
            SELECT SUM(total_amount) AS total
            FROM ".Monthalize('bill')."
            WHERE CC_id = ".$qry_accounts->FieldByName('account_id')."
                AND (bill_status < 3)
            GROUP BY CC_id
        ");
        
        $flt_balance = number_format($qry_accounts->FieldByName('opening_balance'), 2, ',', '') - number_format($qry_verify->FieldByName('total'), 2, '.', '');
        
        if (abs($qry_accounts->FieldByName('closing_balance') - $flt_balance) > 1) {
            $arr_found[$int_discrepancies][] = $qry_accounts->FieldByName('account_number');
            $arr_found[$int_discrepancies][] = $qry_accounts->FieldByName('account_name');
            $arr_found[$int_discrepancies][] = $qry_accounts->FieldByName('closing_balance');
            $arr_found[$int_discrepancies][] = $flt_balance;
            $int_discrepancies++;
        }
            
        $qry_accounts->Next();
    }
    
    boundingBoxStart("800", "../../images/blank.gif");
?>    
    <br>
    <table border='0' cellpadding='0' cellspacing='5'>
        <tr>
            <td colspan='4' class='normaltext'>
                Checking <? echo $qry_accounts->RowCount()?> accounts... <? echo $int_discrepancies ?> discrepancies found.<br><br>
            </td>
        </tr>
        <tr>
            <td class='normaltext' width='120px' align='right'><b>Number</b></td>
            <td class='normaltext' width='250px'><b>Name</b></td>
            <td class='normaltext' width='120px' align='right'><b>Closing<br>Balance</b></td>
            <td class='normaltext' width='120px' align='right'><b>Calculated<br>Balance</b></td>
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
                echo "<td class='normaltext' align='right'>".number_format($arr_found[$i][2],2,'.',',')."</td>";
                echo "<td class='normaltext' align='right'>".number_format($arr_found[$i][3],2,'.',',')."</td>";
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