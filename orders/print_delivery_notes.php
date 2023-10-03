<?
	require_once("order_bill_deliver.php");

	$str_id_list = '';
	if (IsSet($_GET['id_list']))
		$str_id_list = $_GET['id_list'];

	$arr_id_list = explode('|', $str_id_list);

	for ($i=0; $i<count($arr_id_list); $i++) {
//		header("print_delivery_note.php?id=".$arr_id_list[$i]);

		echo "<script language='javascript'>\n";
		echo "window.open('print_delivery_note.php?id=".$arr_id_list[$i]."');\n";
		echo "</script>";

	}
?>