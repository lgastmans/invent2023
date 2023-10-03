<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>

<body leftmargin=5 topmargin=5 marginwidth=0 marginheight=0 bgcolor="#DADADA">

<form name="order_list" method="GET">
    <font class="<?echo $str_class_list_header?>">
    <?
//print_r($_SESSION['order_arr_items']);
	echo "&nbsp;";
	echo StuffWithBlank('Code', 6)." ";
	echo PadWithBlank('Description', 29)." ";
	echo StuffWithBlank('Ordered', 10)." ";
	echo StuffWithBlank('Delivered', 10)." ";
	echo StuffWithBlank('Price', 10)." ";
	echo StuffWithBlank('Total', 10);
    ?>
    </font><br>

    <select name="item_list" size="18" class="<?echo $str_class_list_box?>">
        <?
		$flt_total_amount = 0;
		
		for ($i=0;$i<count($_SESSION['order_arr_items']);$i++) {
			
			$flt_total = number_format($_SESSION['order_arr_items'][$i][1] * $_SESSION['order_arr_items'][$i][4],3,'.','');
			$flt_total_amount += $flt_total;
			
			$strList = StuffWithBlank($_SESSION["order_arr_items"][$i][0], 6)." ". // Code
				PadWithBlank(trim($_SESSION["order_arr_items"][$i][2]), 30)." ". // Description
				StuffWithBlank($_SESSION["order_arr_items"][$i][5], 10)." ". // Qty Ordered
				StuffWithBlank($_SESSION["order_arr_items"][$i][1], 10)." ". // Qty
				StuffWithBlank($_SESSION["order_arr_items"][$i][4], 10)." ". // Price
				StuffWithBlank($flt_total, 10);
			echo "<option value=\"".$i."\">".$strList."\n";
		}
		
		$_SESSION['order_total_amount'] = $flt_total_amount;
        ?>
    </select>
</form>

</body>
</html>