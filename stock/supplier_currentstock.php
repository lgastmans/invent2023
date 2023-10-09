<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");


	$_SESSION["int_stock_selected"] = 7;

	//==================
	// get user settings
	//------------------
	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$qry_settings = new Query("
		SELECT stock_show_available, bill_decimal_places
		FROM user_settings
		WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$str_show_available = 'Y';
	$int_decimal_places = 0;
	if ($qry_settings->RowCount() > 0) {
		$str_show_available = $qry_settings->FieldByName('stock_show_available');
		$int_decimal_places = $qry_settings->FieldByName('bill_decimal_places');
	}

	if (IsSet($_GET["include_tax"]))
		$str_include_tax = $_GET["include_tax"];
	else
		$str_include_tax = 'Y';
	
	if (IsSet($_GET["include_value"]))
		$str_include_value = $_GET["include_value"];
	else
		$str_include_value = 'Y';
		
		
	if (IsSet($_GET['include_bprice']))
		$str_include_bprice = $_GET['include_bprice'];
	else
		$str_include_bprice = 'N';


	/*
		the following include file expects variable
		$int_decimal_places
	*/
	require_once("supplier_currentstock_data.php");

		

?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor'>
	<font class='normaltext'>
	
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr><td align='left'>
	
		<table border=1 cellpadding=7 cellspacing=0 class='normaltext'>
			<?

				foreach ($data['data'] as $row) {

					if ($int_counter % 2 == 0)
					    $str_color="#eff7ff";
					else
					    $str_color="#deecfb";
					
					if ($row['use_batch_price']=='Y')
						echo "<tr bgcolor='darkgrey'>";
					else
						echo "<tr bgcolor=".$str_color.">";
						
					echo "<td width='50px' align=right>".$row['code']."</td>";
					echo "<td width='250px'>".$row['description']."</td>";
					echo "<td width='100px'>".$row['category_description']."</td>";

					if ($str_include_bprice == 'Y')
						echo "<td width='100px' align=right>".sprintf("%01.2f",$row['buying_price'],3)."</td>";

					echo "<td width='100px' align=right>".sprintf("%01.2f",$row['selling_price'],3)."</td>";

					if ($str_include_tax == 'Y') {
						echo "<td width='100px' align=right>".sprintf("%01.2f",$row['price_tax'],3)."</td>";
						echo "<td width='100px' align='right'>".$row['mrp']."</td>";
						echo "<td width='50px' align=right>".$row['tax_description']."</td>";
					}
					else
						echo "<td width='100px' align='right'>".$row['mrp']."</td>";

					if ($row['is_decimal'] == 'Y') {

						if ($row['stock_adjusted'] > 0)
							echo "<td width='100px' align=right>".$row['stock_current']."(-".number_format($row['stock_adjusted'],$int_decimal_places,'.',',').")</td>";
						elseif ($str_display_stock == 'Below Minimum') 
							echo "<td width='100px' align=right>".$row['stock_current']." (<".$row['stock_minimum'].")</td>";
						else
							echo "<td width='100px' align=right>".$row['stock_current']."</td>";

					} else {

						if ($row['stock_adjusted'] > 0)
							echo "<td width='100px' align=right>".$row['stock_current']."(-".number_format($row['stock_adjusted'],0,'.',',').")</td>";
						elseif ($str_display_stock == 'Below Minimum') 
							echo "<td width='100px' align=right>".$row['stock_current']." (<".$row['stock_minimum'].")</td>";
						else
							echo "<td width='100px' align=right>".$row['stock_current']."</td>";
					}

					if ($str_include_value == 'Y') {

						if ($str_include_bprice == 'Y')

							echo "<td width='100px' align=right>".sprintf("%01.2f",$row['buying_value'],3)."</td>";

						echo "<td width='100px' align=right>".sprintf("%01.2f",$row['selling_value'],3)."</td>";
					}

					echo "<td width='100px' align=right>".number_format($row['stock_sold'],$int_decimal_places,'.',',')."</td>";
					echo "<td width='100px' align=right>".number_format($row['stock_received'],$int_decimal_places,'.',',')."</td>";

					echo "</tr>\n";

					$int_counter++;
				}
	
			?>
		</table>
		
	</td></tr>
	</table>
	</font>
	<script language='javascript'>
	
		parent.frames["footer"].document.location = 'supplier_currentstock_footer.php?'+
			'total_stock=<?echo number_format($data["total_stock"],2,'.','')?>'+
			'&total_adjusted=<?echo number_format($data["total_adjusted"],2,'.','')?>'+
			'&buying_value=<?echo number_format($data["total_b_value"],2,'.','')?>'+
			'&selling_value=<?echo number_format($data["total_s_value"],2,'.','')?>';
	</script>

</body>
</html>