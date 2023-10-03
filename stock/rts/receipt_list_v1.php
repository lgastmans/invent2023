<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");


	if (IsSet($_SESSION['current_discount']))
		$int_discount = $_SESSION['current_discount'];
	else
		$int_discount = 0;

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == "set_type") {
			$_SESSION['current_bill_day'] = $_GET['bill_day'];
		}
		if ($_GET['action'] == "set_discount") {
			$_SESSION['current_discount'] = $_GET["discount"];
			$int_discount = $_GET["discount"];
		}
	}

?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=7 marginheight=7> 

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr><td align='center'>
<?
	boundingBoxStart("800", "../../images/blank.gif");
?>

<form name="receipt_list" method="GET" onsubmit="return false">
	<font class="list_text_bold">
	<?
		echo "&nbsp;";
		echo StuffWithBlank('Code', 6)." ";
		echo StuffWithBlank('Batch', 10)." ";
//		echo StuffWithBlank('Inv No', 10)." ";
//`		echo StuffWithBlank('Inv Dt', 10)." ";
		echo PadWithBlank('Description', 30)." ";
		echo StuffWithBlank('Qty', 5)." ";
		echo StuffWithBlank('B Price', 9)." ";
		echo StuffWithBlank('S Price', 9)." ";
		echo StuffWithBlank('Tax', 7)." ";
		echo StuffWithBlank('Total', 10);
	?>
	</font>
	<select name="item_list" size="16" class='select_list'>
	<?
		if (IsSet($_GET["code"])) {
			// set the discount, if one was entered
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				if (($_SESSION["arr_total_qty"][$i][0] == $_GET["code"]) && ($_SESSION["arr_total_qty"][$i][1] == $_GET["batch_code"]))
					$_SESSION["arr_total_qty"][$i][4] = $_GET["discount"];
			}
		}
		else
		if (IsSet($_GET["del"])) {
			// get the number of entries found in the session array arr_total_qty for the given product code
			$int_TotalRows = 0;
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				if ($_SESSION["arr_total_qty"][$i][0] == $_SESSION['current_code'])
					$int_TotalRows = $int_TotalRows + 1;
			}

			// remove the row from session array arr_total_qty
			$_SESSION["arr_total_qty"] = array_delete($_SESSION["arr_total_qty"], $_GET["atIndex"]);
		}

		// iterate through the session array arr_total_qty
		$flt_total = 0;
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {

			$tmp_qty = number_format($_SESSION["arr_total_qty"][$i][2] + $_SESSION["arr_total_qty"][$i][5], 3,'.','');
			$tmp_price = $_SESSION["arr_total_qty"][$i][6];
	
			$flt_price_total = round(($tmp_qty * $tmp_price), 2);

			// save the total per item 
			$_SESSION["arr_total_qty"][$i][10] = round($flt_price_total,2);

			$strList = StuffWithBlank($_SESSION["arr_total_qty"][$i][0], 6)." ".		// product code
				StuffWithBlank($_SESSION["arr_total_qty"][$i][1], 10)." ".				// batch code
				PadWithBlank($_SESSION["arr_total_qty"][$i][12], 30)." ".				// product description
				StuffWithBlank($_SESSION["arr_total_qty"][$i][2], 5)." ".				// quantity billed
				StuffWithBlank(sprintf("%01.2f", $_SESSION["arr_total_qty"][$i]['buying_price']), 10)." ".	// buying price
				StuffWithBlank(sprintf("%01.2f", $_SESSION["arr_total_qty"][$i][6]), 10)." ".	// selling price
				StuffWithBlank($_SESSION["arr_total_qty"][$i][8], 7)." ".	// tax
				StuffWithBlank(sprintf("%01.2f", $_SESSION["arr_total_qty"][$i][10]), 10);		// total
			echo "<option value=\"".$i."\">".$strList;

			// in the case of a manual transfer of extra stock, show this in the bill
			// as another line. The price, however, is included in the line above
			if ($_SESSION["arr_total_qty"][$i][5] > 0) {
				$strList = StuffWithBlank("", 6)." ".
				StuffWithBlank("", 10)." ".
				PadWithBlank($_SESSION["arr_total_qty"][$i][12], 30)." ".
				StuffWithBlank($_SESSION["arr_total_qty"][$i][5], 5);

				echo "<option value=\"".$i."\">".$strList;
			}

			// calculate the bill total
			$flt_total += $_SESSION["arr_total_qty"][$i][10];
		}
	?>
	</select>
<?
	$flt_total = RoundUp($flt_total);
	$_SESSION['bill_total'] = number_format($flt_total,3,'.','');
?>
	<script language='javascript'>
		parent.frames["frame_total"].document.location="receipt_total.php?total=<?echo number_format($flt_total,2,'.','');?>&discount=<?echo $int_discount?>";
	</script>
</form>

<?
    boundingBoxEnd("750", "../../images/blank.gif");
?>
</td></tr>
</table>

</body>
</html>