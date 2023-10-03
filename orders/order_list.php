<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>

<body id='body_bgcolor' leftmargin=0 topmargin=0>

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr><td align='center'>
<?
	boundingBoxStart("750", "../images/blank.gif");
?>

<form name="order_list" method="GET">
    <font class="list_text_bold">
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

    <select name="item_list" size="20" class="select_list">
        <?
		$flt_total_amount = 0;
		
		for ($i=0;$i<count($_SESSION['order_arr_items']);$i++) {
			
			$flt_total = number_format($_SESSION['order_arr_items'][$i][1] * $_SESSION['order_arr_items'][$i][4],3,'.','');
			$flt_total_amount += $flt_total;
			
			$strList = StuffWithBlank($_SESSION["order_arr_items"][$i][0], 6)." ". // Code
				PadWithBlank(trim($_SESSION["order_arr_items"][$i][2]), 30)." ". // Description
				StuffWithBlank($_SESSION["order_arr_items"][$i][1], 10)." ". // Qty
				StuffWithBlank($_SESSION["order_arr_items"][$i][4], 10)." ". // Price
				StuffWithBlank($flt_total, 10);
			echo "<option value=\"".$i."\">".$strList."\n";
		}
		
		$_SESSION['order_total_amount'] = $flt_total_amount;
        ?>
    </select>
</form>

<?
    boundingBoxEnd("750", "../images/blank.gif");
?>
</td></tr>
</table>

</body>
</html>