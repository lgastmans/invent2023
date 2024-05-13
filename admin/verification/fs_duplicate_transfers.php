<?
    include("../../include/const.inc.php");
    include("../../include/session.inc.php");
    require_once('db_params.php');

    $str_query = "
        SELECT * 
        FROM ".Yearalize('transfers_log_2024')."
        WHERE id NOT IN ( 
            SELECT id
            FROM ".Yearalize('transfers_log')."
            GROUP BY account_to, description
            HAVING ( COUNT(*) = 1)
        )
        ORDER BY called_on, account_from;
    ";
    $qry = $conn->Query($str_query);
    
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
    $int_discrepancies = $qry->num_rows;
    $arr_found = array();
    
    $i=0;
    while ($obj = $qry->fetch_object()) {

        $arr_found[$i][] = $obj->account_from;
        $arr_found[$i][] = $obj->amount;
        $arr_found[$i][] = $obj->description;
        $arr_found[$i][] = makeHumanTime($obj->called_on);
        $arr_found[$i][] = $obj->result;
        $arr_found[$i][] = $obj->result_string;

        $i++;
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
            <td class='normaltext' width='250px'><b>Amount</b></td>
            <td class='normaltext' width='80px'><b>Description</b></td>
            <td class='normaltext' width='140px'><b>Date</b></td>
            <td class='normaltext' width='120px' align='right'><b>Result</b></td>
            <td class='normaltext' width='120px'><b>Result data</b></td>
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