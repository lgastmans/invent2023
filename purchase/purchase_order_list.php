<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once('../common/tax.php');

	$int_len = 0;
	if (isset($_SESSION['purchase_order_arr_items']))
		$int_len = count($_SESSION['purchase_order_arr_items']);
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<STYLE TYPE="text/css"> body{background-color:transparent}</STYLE>
</head>

<body topmargin="0" rightmargin="0" bottommargin="0" leftmargin="0">
<form name="purchase_order_list" method="GET">


<table id='tbl_products' class='products_list' width='100%' border='0' cellpadding='0' cellspacing='0'>

	<tr bgcolor="lightgrey" height='12px'>
		<td width='70px' class="normaltext_bold" >Code</td>
		<td width='250px' class="normaltext_bold">Description</td>
		<td width='80px' class="normaltext_bold" align="right">B.Price</td>
		<td width='80px' class="normaltext_bold" align="right">S.Price</td>
		<td width='70px' class="normaltext_bold" align="right">Qty</td>
		<td width='60px' class="normaltext_bold" align="right">Tax</td>
		<td width='60px' class="normaltext_bold" align="right">Taxable<br>Value</td>
		<td width='80px' class="normaltext_bold" align="right">Amount</td>
		<td width='10px'>&nbsp;</td>
	</tr>

	<?
		if ($int_len > 0) {
			for ($i=0;$i<$int_len;$i++) {
				if ($i % 2 == 0)
					$strClass = 'odd';
				else
					$strClass = 'even';
				

				$arr = getTaxDetails($_SESSION['purchase_order_arr_items'][$i]['buying_price'], $_SESSION['purchase_order_arr_items'][$i]['tax_id']);
				$taxable = $_SESSION['purchase_order_arr_items'][$i]['buying_price'] *  $_SESSION['purchase_order_arr_items'][$i][1];


				// product_id
				echo "<tr class='$strClass' id='".$_SESSION['purchase_order_arr_items'][$i][3]."'>";

				//product_code
				echo "<td width='70px'>".$_SESSION['purchase_order_arr_items'][$i][0]."</td>";

				//description
				echo "<td width='250px'>".$_SESSION['purchase_order_arr_items'][$i][2]."</td>";

				// b price
				echo "<td width='80px' align='right'>".$_SESSION['purchase_order_arr_items'][$i]['buying_price']."</td>";

				// price
				echo "<td width='80px' align='right'>".$_SESSION['purchase_order_arr_items'][$i][4]."</td>";

				// qty
				if ($_SESSION['purchase_order_arr_items'][$i]['is_decimal'] == 'Y')
					echo "<td width='70px' align='right'>".number_format($_SESSION['purchase_order_arr_items'][$i][1],2,'.')."</td>";
				else
					echo "<td width='70px' align='right'>".number_format($_SESSION['purchase_order_arr_items'][$i][1],0)."</td>";

				// tax
				echo "<td width='80px' align='right'>".$arr[0]['definition_percent']."%</td>";

				// taxable value
				echo "<td width='80px' align='right'>".number_format($taxable,2,'.','')."</td>";

				//amount
				$flt_amount = $_SESSION['purchase_order_arr_items'][$i]['buying_price'] * $_SESSION['purchase_order_arr_items'][$i][1] * (1+($arr[0]['definition_percent']/100));
				echo "<td width='70px' align='right'>".number_format($flt_amount,2,'.','')."</td>";

				echo "<td width='10px'></td>";

				echo "</tr>";
			}
		}
	?>
</table>

</form>
</body>
</html>