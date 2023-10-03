<?
    if (IsSet($_GET['id'])) {
	$int_id = $_GET['id'];
	$str_printer = $_GET['printer'];
	$int_num_rows = $_GET['num_rows'];
	
	$str_url = "location:print_barcode.php?id=".$int_id."&selected_printer=".$str_printer;
	header($str_url);
    }

?>