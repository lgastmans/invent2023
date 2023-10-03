<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

    $flt_total = 0;
    if (IsSet($_GET['total']))
        $flt_total = $_GET['total'];

    $flt_qty = 0;
    if (isset($_GET['qty']))
        $flt_qty = $_GET['qty'];
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor'>
    <table width='50%' border='0'>
        <tr>
            <td align='right' class='normaltext_bold'><b>Grand Total:</b></td>
            <td align='right' class='normaltext_bold'><b><? echo "Rs. ".$flt_total; ?></b></td>
        </tr>
        <tr>
            <td align='right' class='normaltext_bold'><b>Qty Total:</b></td>
            <td align='right' class='normaltext_bold'><b><? echo $flt_qty; ?></b></td>
        </tr>
    </table>
</body>
</html>