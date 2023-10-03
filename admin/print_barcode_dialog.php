<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

    $int_id = 0;
    if (IsSet($_GET['id']))
	$int_id = $_GET['id'];
    
    if (IsSet($_POST['action'])) {
	if ($_POST['action'] == 'Print') {
	    $int_id = $_POST['id'];
	    $str_printer = $_POST['select_printer'];
	    $int_num_rows = $_POST['num_rows'];
	    
	    header("location:print_barcodes.php?id=".$int_id."&printer=".$str_printer."&num_rows=".$int_num_rows);
	}
    }    
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>
<body>
<form name='print_barcode_dialog' method='POST'>
    <input type='hidden' name='id' value='<?echo $int_id;?>'>
    <table>
	<tr>
	    <td class='normaltext'>Printer:</td>
	    <td>
		<select name='select_printer'>
		<?
		    
		    foreach (printer_list(PRINTER_ENUM_LOCAL) as $printer) {
			$curprin = strtoupper($printer["NAME"]);
			echo "<option value='".addslashes($curprin)."'>".$curprin."\n";
		    }
		?>
		</select>
	    </td>
	</tr>
	<tr>
	    <td class='normaltext'>No. of rows:</td>
	    <td>
		<input class='<?echo $str_class_input?>' type='text' name='num_rows' value='1'>
	    </td>
	</tr>
	<tr>
	    <td>&nbsp;</td>
	</tr>
	<tr>
	    <td>
		<input type='submit' name='action' value='Print'>
	    </td>
	</tr>
    </table>
</form>
</body>
</html>

