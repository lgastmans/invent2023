<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("order_bill_deliver.php");

	$qry_settings = new Query("
		SELECT * 
		FROM user_settings 
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
	);
	$str_print_bill = $qry_settings->FieldByName('order_print_bill');
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
	
	<script language='javascript'>
		function print_bill(a_bill_id) {
			myWin = window.open("print_order_bill.php?id="+a_bill_id, 'printwin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=450,height=250');
			myWin.focus();
		}
		
		function closeWindow() {
			if (window.opener)
				window.opener.document.location=window.opener.document.location.href;
			window.close();
		}
	</script>
</head>
<body>

	<table width='100%' border='0'>
		<tr>
			<td align='center' valign='center' class='<?echo $str_class_header;?>'>Delivering selected order bills...</td>
		</tr>

<?
	$str_id_list = '';
	if (IsSet($_GET['id_list']))
		$str_id_list = $_GET['id_list'];
	
	$str_delivery_date = date('d-m-Y', time());
	if (IsSet($_GET['delivery_date']))
		$str_delivery_date = $_GET['delivery_date'];

	$arr_id_list = explode('|', $str_id_list);

	$str_message = '';
	if (!validate_date($str_delivery_date)) {
	    $str_message = 'Invalid delivery date';
	}
	else {	
		$bool_success = true;
		for ($i=0; $i<count($arr_id_list); $i++) {
			
			$str_retval = deliver_order_bill($arr_id_list[$i], $str_delivery_date);
			$arr_retval = explode('|', $str_retval);
			
			if ($arr_retval[0] != 'OK') {
				$bool_success = false;
				echo "<tr height='20px'><td class='".$str_class_header."'>".$arr_retval[1]."</td></tr>";
			}
			else {
				if ($str_print_bill == 'Y') {
					echo "<script language=\"javascript\">\n";
					echo "	print_bill(".$arr_id_list[$i].");\n";
					echo "</script>\n";
				}
			}
		}
	}
?>
	<tr>
		<td align='center'>
		<?
			if ($str_message <> '')
				echo $str_message;
			else
				echo "Done";
		?>
		</td>
	</tr>
	<tr>
		<td align='center'><input type='button' name='action' value='Close' onclick='closeWindow()'></td>
	</tr>
	</table>


</body>
</html>