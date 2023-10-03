<?
	$flt_ob_total = 0;
	if (IsSet($_GET['ob_total'])) {
		$flt_ob_total = $_GET['ob_total'];
	}

	$flt_cb_total = 0;
	if (IsSet($_GET['cb_total']))
		$flt_cb_total = $_GET['cb_total'];
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>

    <table border=1 cellpadding=7 cellspacing=0>
        <tr class='normaltext_bold' bgcolor='lightgrey'>
            <td width='160px' colspan='2'>Totals</td>
            <td align='right' width='250px'><?echo number_format($flt_ob_total,2,'.',',')?></td>
            <td align='right' width='250px'><?echo number_format($flt_cb_total,2,'.',',')?></td>
        </tr>
    </table>
    
</body>
</html>