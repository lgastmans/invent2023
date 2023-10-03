<?
    $flt_total = 0;
    if (IsSet($_GET['total']))
        $flt_total = $_GET['total'];

    $flt_percent = 0;
    if (IsSet($_GET['percent']))
        $flt_percent = $_GET['percent'];
    
    $flt_commission = 0;
    if (IsSet($_GET['commission']))
        $flt_commission = $_GET['commission'];

    $flt_percent_2 = 0;
    if (IsSet($_GET['percent_2']))
        $flt_percent_2 = $_GET['percent_2'];
    
    $flt_commission_2 = 0;
    if (IsSet($_GET['commission_2']))
        $flt_commission_2 = $_GET['commission_2'];

    $flt_percent_3 = 0;
    if (IsSet($_GET['percent_3']))
        $flt_percent_3 = $_GET['percent_3'];
    
    $flt_commission_3 = 0;
    if (IsSet($_GET['commission_3']))
        $flt_commission_3 = $_GET['commission_3'];
    
    $given = $flt_total - $flt_commission - $flt_commission_2 - $flt_commission_3;

    $total_qty = 0;
    if (IsSet($_GET['total_qty']))
    	$total_qty = $_GET['total_qty'];

    $total_taxable_value = 0;
    if (IsSet($_GET['total_taxable_value']))
    	$total_taxable_value = $_GET['total_taxable_value'];

	$total_tax_amount = 0;
	if (IsSet($_GET['total_tax_amount']))
		$total_tax_amount = $_GET['total_tax_amount'];

	$total_amount = 0 ;
	if (IsSet($_GET['total_amount']))
		$total_amount = $_GET['total_amount'];

	$calc_price = "BP";
	if (IsSet($_GET['calc_price']))
		$calc_price = $_GET['calc_price'];

	$taxes = array();
	if (isset($_GET['taxes']))
		$taxes = json_decode(urldecode($_GET['taxes']));

?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>

<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>

	<table border=1 cellpadding=7 cellspacing=0>
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<!--
			<td width='50px'></td>
			<td width='60px'></td>
			<td width='120px'></td>
			<td width='250px'></td>
			-->
			<td width="480px">

				<?php if ($calc_price == 'SP') { ?>

				<table width='100%' border='0' >
					<tr class='normaltext_bold'>
						<td width='100%' align='right'>Total :&nbsp;</td>
						<td align='right'><? echo number_format($flt_total, 2, '.', ','); ?></td>
					</tr>
					<tr class='normaltext_bold'>
						<td align='right'><? echo "Commission ".$flt_percent."% :";?>&nbsp;</td>
						<td align='right'><? echo number_format($flt_commission, 2, '.', ','); ?></td>
					</tr>
					<? if ($flt_percent_2 > 0) { ?>
					<tr class='normaltext_bold'>
						<td align='right'><? echo "Commission ".$flt_percent_2."% :";?>&nbsp;</td>
						<td align='right'><? echo number_format($flt_commission_2, 2, '.', ','); ?></td>
					</tr>
					<? } ?>
					<? if ($flt_percent_3 > 0) { ?>
					<tr class='normaltext_bold'>
						<td align='right'><? echo "Commission ".$flt_percent_3."% :";?>&nbsp;</td>
						<td align='right'><? echo number_format($flt_commission_3, 2, '.', ','); ?></td>
					</tr>
					<? } ?>
					<tr class='normaltext_bold'>
						<td align='right'>Given :&nbsp;</td>
						<td align='right'><? echo number_format($given, 2, '.', ','); ?></td>
					</tr>
				</table>

				<?php } ?>

			</td>

			<td width='80px' align="right"><?php echo "Total Qty<br><br>".$total_qty; ?></td>
			<td width='80px'></td>
			<td width='80px'></td>
			<td width='80px' align="right"><?php echo "Total Taxable Value <br><br>".number_format($total_taxable_value,2,'.',','); ?></td>


			<?php if ($calc_price=='BP') { ?>
				<td width='60px' align="right"></td>
				<td width='60px' align="right">
					<?php echo "Total Tax Amount<br><br>".number_format($total_tax_amount,2,'.',','); ?>
				</td>
			<?php } else { ?>
				<td width='140px' colspan="2">
				<?php
					foreach ($taxes as $row) {
						echo $row->description." = ".number_format($row->amount,2,'.',',')."<br>";
					}
				?>
				</td>
			<?php } ?>


			<td width='120px' align="right"><?php echo "Total Amount<br><br>".number_format(($flt_total+$total_tax_amount),2,'.',','); ?></td>
		</tr>
	</table>

        
</body>
</html>