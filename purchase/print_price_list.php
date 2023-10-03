<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	include("../common/product_funcs.inc.php");

	if (IsSet($_GET['type']))
		$int_type = $_GET['type'];

	if ($int_type == 1)
		$str_type = 'Perishable';
	elseif ($int_type == 2)
		$str_type = 'Non-perishable';
	else
		$str_type ='All';
	
	if (IsSet($_GET['category']))
		$int_category = $_GET['category'];

	$qry = new Query("SELECT category_description FROM stock_category WHERE category_id = $int_category");
	$str_category = '';
	if ($qry->RowCount() > 0)
		$str_category = $qry->FieldByName('category_description');
	
	if (IsSet($_GET['id_list'])) {
		$arr_temp = explode('^', $_GET['id_list']);
		$arr_product_ids = array();
		for ($i=0;$i<count($arr_temp)-1;$i++){
			$arr_product_ids[] = substr($arr_temp[$i], 3, strlen($arr_temp[$i]));
		}
	}
	$str_in_clause = '';
	for ($i=0;$i<count($arr_product_ids);$i++) {
		$str_in_clause .= $arr_product_ids[$i].",";
	}
	$str_in_clause = substr($str_in_clause, 0, strlen($str_in_clause)-1);

	$str_query = "
		SELECT product_id, product_code, product_description
		FROM stock_product
		WHERE (product_id IN ($str_in_clause))
			AND (list_in_price_list = 'Y')
		ORDER BY product_description";
	$qry->Query($str_query);
	
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

	<table width='100%' border='1' cellpadding='7' cellspacing='0'>
		<tr>
			<td colspan='3' class='normaltext_bold_20'><?echo $str_category?> price list for <? echo date('j M y', time()); ?></td>
		</tr>
		<tr>
			<td class='normaltext_bold_20'>Code</td>
			<td class='normaltext_bold_20'>Description</td>
			<td class='normaltext_bold_20' align='right'>Price</td>
		</tr>
		<?
			for ($i=0;$i<$qry->RowCount();$i++) {
				echo "<tr>";
				echo "<td class='normaltext_20'>".$qry->FieldByName('product_code')."</td>";
				echo "<td class='normaltext_20'>".$qry->FieldByName('product_description')."</td>";
				echo "<td class='normaltext_20' align='right'>".getSellingPrice($qry->FieldByName('product_id'))."</td>";
				echo "</tr>\n";
				
				$qry->Next();
			}
		?>
	</table>
</body>
</html>