<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("../common/tax.php");

	$flt_buying_total = 0;
	$flt_buying_tax = 0;

	$flt_selling_total = 0;
	$flt_selling_tax = 0;

	$int_len = count($_SESSION['purchase_order_arr_items']);

	for ($i=0;$i<$int_len;$i++) {

		$arr = getTaxDetails($_SESSION['purchase_order_arr_items'][$i]['buying_price'], $_SESSION['purchase_order_arr_items'][$i]['tax_id']);

		$flt_buying_total += $_SESSION['purchase_order_arr_items'][$i]['buying_price'] * $_SESSION['purchase_order_arr_items'][$i][1];
		$flt_buying_tax += ($_SESSION['purchase_order_arr_items'][$i]['buying_price'] * $_SESSION['purchase_order_arr_items'][$i][1]) * (1+($arr[0]['definition_percent']/100));

		$flt_selling_total += $_SESSION['purchase_order_arr_items'][$i][4] * $_SESSION['purchase_order_arr_items'][$i][1];
		$flt_selling_tax += $_SESSION['purchase_order_arr_items'][$i][4] * $_SESSION['purchase_order_arr_items'][$i][1] * (1+($arr[0]['definition_percent']/100));
	}

	$flt_buying_total = number_format($flt_buying_total, 2, '.','');
	$flt_buying_tax = number_format($flt_buying_tax, 2, '.','');

	$flt_selling_total = number_format($flt_selling_total, 2, '.','');
	$flt_selling_tax = number_format($flt_selling_tax, 2, '.','');

?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>

<body id='body_bgcolor' marginwidth="10" marginheight="10">

<table border='0' cellpadding='0' cellspacing='0' class="edit">
	<tr>
		<td align='center' valign="top">
			<table width="600" height="30" border="0" cellpadding="0" cellspacing="0">
				<tr class="normaltext_bold">
					<td width='70px'></td>
					<td width='250px'>Total</td>
					<td width='80px' align="right"><?echo $flt_buying_total; ?></td>
					<td width='80px' align="right"><?echo $flt_selling_total; ?></td>
					<td width='70px'></td>
					<td width='80px'></td>
					<td width='10px'>&nbsp;</td>
				</tr>
				<tr class="normaltext_bold">
					<td width='70px'></td>
					<td width='250px'>Total with tax</td>
					<td width='80px' align="right"><?echo $flt_buying_tax; ?></td>
					<td width='80px' align="right"><?echo $flt_selling_tax; ?></td>
					<td width='70px'></td>
					<td width='80px'></td>
					<td width='10px'>&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>