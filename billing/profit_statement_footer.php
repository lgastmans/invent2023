<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    
    $flt_total_buying_value = 0;
    if (IsSet($_GET['total_buying']))
        $flt_total_buying_value = $_GET['total_buying'];
        
    $flt_total_selling_value = 0;
    if (IsSet($_GET['total_selling']))
        $flt_total_selling_value = $_GET['total_selling'];
    
    $flt_total_discount_value = 0;
    if (IsSet($_GET['total_discount']))
        $flt_total_discount_value = $_GET['total_discount'];

    $flt_total_promotion = 0;
    if (IsSet($_GET['total_promotion']))
        $flt_total_promotion = $_GET['total_promotion'];

    $flt_total_difference = $flt_total_selling_value - $flt_total_buying_value - $flt_total_discount_value - $flt_total_promotion;
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor'>
    <table border='1' cellpadding='7' cellspacing='0'>
        <tr>
            <td class='normaltext_bold' width='60px'><b>Totals<b></td>
            <td align='right' class='normaltext_bold' width='120px'><b><?echo number_format($flt_total_buying_value,2,'.',',')?></b></td>
            <td align='right' class='normaltext_bold' width='120px'><b><?echo number_format($flt_total_selling_value,2,'.',',')?></b></td>
            <td align='right' class='normaltext_bold' width='120px'><b><?echo number_format($flt_total_discount_value,2,'.',',')?></b></td>
            <td align='right' class='normaltext_bold' width='120px'><b><?echo number_format($flt_total_promotion,2,'.',',')?></b></td>
            <td align='right' class='normaltext_bold' width='120px'><b><?echo number_format($flt_total_difference,2,'.',',')?></b></td>
        </tr>
    </table>
</body>
</html>