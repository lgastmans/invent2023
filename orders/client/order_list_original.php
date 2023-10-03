<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	
	/*
		get the client price_increase field
	*/
	$str_query = "
		SELECT price_increase
		FROM customer
		WHERE id = ".$_SESSION['order_client_id'];
	$qry = new Query($str_query);
	
	$flt_price_increase = 0;
	if ($qry->RowCount() > 0)
		$flt_price_increase = 1 + ($qry->FieldByName('price_increase') / 100);
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>

<body leftmargin="1" topmargin="1" rightmargin="1" bottommargin="1" bgcolor="lightgrey">

<form name="order_list" method="GET">

<table class="edit" with="100%" height="100%">
	<tr><TD>
	
		<font class="listheader">
		<?
	//print_r($_SESSION['order_arr_items']);
		echo "&nbsp;";
		echo StuffWithBlank('Code', 6)." ";
		echo PadWithBlank('Description', 29)." ";
		echo StuffWithBlank('Qty', 10)." ";
		echo StuffWithBlank('Price', 10)." ";
		echo StuffWithBlank('Total', 10);
		?>
		</font><br>

		<select name="item_list" size="18" class="listbox">
			<?
			$flt_total_amount = 0;
			
			for ($i=0;$i<count($_SESSION['order_arr_items']);$i++) {
				
				$flt_price = number_format($_SESSION['order_arr_items'][$i][4] * $flt_price_increase,2,'.','');
				
				$flt_total = number_format($_SESSION['order_arr_items'][$i][1] * $flt_price,2,'.','');
				$flt_total_amount += $flt_total;
				
				$strList = StuffWithBlank($_SESSION["order_arr_items"][$i][0], 6)." ". // Code
					PadWithBlank(trim($_SESSION["order_arr_items"][$i][2]), 30)." ". // Description
					StuffWithBlank($_SESSION["order_arr_items"][$i][1], 10)." ". // Qty
					StuffWithBlank($flt_price, 10)." ". // Price
					StuffWithBlank($flt_total, 10);
				echo "<option value=\"".$i."\">".$strList."\n";
			}
			
			$_SESSION['order_total_amount'] = $flt_total_amount;
			?>
		</select>
	</TD></tr>
</table>

</form>
</body>
</html>