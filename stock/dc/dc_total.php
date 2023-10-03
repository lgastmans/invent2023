<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	$str_batches_enabled = 'N';
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
	);
	if ($sql_settings->RowCount() > 0) {
		$str_batches_enabled = $sql_settings->FieldByName('bill_enable_batches');
	}
	
	if ($_SESSION['str_user_font_size'] == 'small') {
	    $str_class_total = "bill_total_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
	    $str_class_total = "bill_total";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
	    $str_class_total = "bill_total_large";
	}
	else {
	    $str_class_total = "bill_total";
	}

	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else if ($_SESSION['str_user_color_scheme'] == 'purple')
		$str_css_filename = 'bill_styles_purple.css';
	else if ($_SESSION['str_user_color_scheme'] == 'green')
		$str_css_filename = 'bill_styles_green.css';
	else
		$str_css_filename = 'bill_styles.css';

	$int_products_billed = 0;
	if (IsSet($_GET['products_billed']))
		$int_products_billed = $_GET['products_billed'];
		
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
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

	<form name='billing_total' method='GET'>
	
	<table width='70%' height='100%' border='0' cellpadding=0 cellspacing=0>
		<tr>
			<td width='45%' class="<? echo $str_class_total?>" align='right'>Total :&nbsp;</td>
			<td width='20%' align='right'>
				<span id="bill_total" class="<? echo $str_class_total?>">
					<?
						echo number_format($flt_total,2);
					?>
				</span>
			</td>
			<td width='40%' class="<? echo $str_class_total?>" align='right'>
				<?
					if ($str_batches_enabled == 'N')
						echo "<b>".$int_products_billed."</b> product(s), ".$int_items_billed." item(s) billed";
				?>
			</td>
		</tr>
		<tr>
			<td width='45%' class="<? echo $str_class_total?>" align='right'>Rounded Total :&nbsp;</td>
			<td width='20%' align='right'>
				<span id="bill_total" class="<? echo $str_class_total?>">
					<?
						echo number_format(RoundUp($flt_total),2);
					?>
				</span>
			</td>
		</tr>
		<tr>
			<td width='10%' class="<? echo $str_class_total?>" align='right' id='bill_promotion_label'>
			    <?if ($flt_promotion > 0) echo "Sales Promotion :&nbsp;"; ?>
			</td>
			<td align='right' >
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
			<td align='right' >
				<span id="bill_grand_total" class="<? echo $str_class_total?>">
				<?
				    if ($flt_promotion > 0) {
						$tmp_grand_total = number_format(RoundUp($flt_total),2) - $_GET['promotion'];
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