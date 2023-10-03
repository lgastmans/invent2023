<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	
	$int_items_billed = 0;
	if (IsSet($_GET['items_billed']))
		$int_items_billed = $_GET['items_billed'];

	$flt_total = 0;
	if (IsSet($_GET['total']))
		$flt_total = $_GET['total'];

	$flt_promotion = 0;
	if (IsSet($_GET['promotion']))
		$flt_promotion = $_GET['promotion'];
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
</head>
<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

	<form name='billing_total' method='GET'>
	
	<table width='70%' height='100%' border='0' cellpadding=0 cellspacing=0>
		<tr>
			<td width='45%' class="<? echo $str_class_total?>" align='right'>Total :&nbsp;</td>
			<td width='15%' align='right'>
				<span id="bill_total" class="<? echo $str_class_total?>">
					<?
						echo number_format($flt_total,2);
					?>
				</span>
			</td>
			<td width='40%' class="<? echo $str_class_total?>" align='right'>
				<? echo "<b>".$int_items_billed."</b> item(s) billed"?>
			</td>
		</tr>
		<tr>
			<td width='10%' class="<? echo $str_class_total?>" align='right' id='bill_promotion_label'>
			    <?if ($flt_promotion > 0) echo "Sales Promotion :&nbsp;"; ?>
			</td>
			<td align='right' colspan='2'>
				<span id="bill_promotion" class="<? echo $str_class_total?>">
				<?
				    if ($flt_promotion > 0)
					echo number_format($flt_promotion,2,'.',',');
				?>
				</span>
			</td>
		</tr>
		<tr>
			<td width='10%' class="<? echo $str_class_total?>" align='right' id='bill_grand_total_label'>
			    <? if ($flt_promotion > 0) echo "Grand Total :&nbsp;"; ?>
			</td>
			<td align='right' colspan='2'>
				<span id="bill_grand_total" class="<? echo $str_class_total?>">
				<?
				    if ($flt_promotion > 0) {
					$tmp_grand_total = $_GET['total'] - $_GET['promotion'];
					echo number_format($tmp_grand_total, 2, '.', ',');
				    }
				?>
				</span>
			</td>
		</tr>
	</table>
	
	</form>
</body>
</html>