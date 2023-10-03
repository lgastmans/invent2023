<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

    $flt_total_buying = 0;
    if (IsSet($_GET['total_buying']))
        $flt_total_buying = $_GET['total_buying'];

    $flt_total_value = 0;
    if (IsSet($_GET['total_value']))
        $flt_total_value = $_GET['total_value'];
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=15 topmargin=0>

<table width='100%' border='0'>
<tr><td align='left'>

<table border='1' cellpadding='7' cellspacing='0'>
    <tr bgcolor='lightgrey'>
        <td width='841px' align='right' class='normaltext_bold' colspan='7'>Total</td>
        <td width='100px' align='right' class='normaltext_bold'><?echo number_format($flt_total_buying,2,'.',',')?></td>
        <td width='100px' align='right' class='normaltext_bold'><?echo number_format($flt_total_value,2,'.',',')?></td>
    </tr>
</table>

</td></tr>
</table>

</body>
</html>