<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
        function get_selected_count() {
	    $int_count = 0;
	    for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
		if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y')
		    $int_count++;
	    }
	    return $int_count;
	}
	
	$int_selected = get_selected_count();
?>

<script language='javascript'>
    function toggle_select(bool_select) {
	var int_selected = document.getElementById('num_selected');

	document.location = 'order_sheet_update_array.php?toggle_select='+bool_select;
	parent.parent.frames['order_sheet_content'].document.location = 'order_sheet_update_array.php?' +
	    'toggle_select=' + bool_select;
    }
    
    function update_sheet() {
	var oArrSelected = parent.frames['order_sheet_products'].document.getElementsByName('select_print');
	var int_selected = document.getElementById('num_selected');
	var str_selected = '&selected_products=';
	
	int_count = 0;
	for (i=0; i<oArrSelected.length; i++) {
		if (oArrSelected[i].checked) {
			str_checked = 'Y';
			int_count++;
		}
		else
			str_checked = 'N';
		str_selected += oArrSelected[i].getAttribute('id') +'|' + str_checked +',';
	}
	str_selected = str_selected.substring(0, str_selected.length - 1);
	
	parent.parent.frames['order_sheet_content'].document.location = 'order_sheet_update_array.php?' +
	    'action=update'+
	    '&selected_products='+str_selected;

	int_selected.innerHTML = int_count;
    }
</script>

<html>
<body>

    
    <table border='1' width='100%' cellpadding='2' cellspacing='0'>
        <tr>
            <td align='center'><font style="font-family:Verdana,sans-serif;font-size:12px;font-weight:bold">selected: <span id='num_selected'><?echo $int_selected;?></span></font></td>
        </tr>
        <tr>
            <td align='center'>
                <input type='button' name='action' value='all' onclick="toggle_select('Y')">
                <input type='button' name='action' value='none' onclick="toggle_select('N')">
            </td>
        </tr>
        <tr>
            <td align='center'>
                <input type='button' name='action' value='update' onclick="update_sheet()">
            </td>
        </tr>
    </table>
    
    <br>
    <table border='0' width='100%' cellpadding='0' cellspacing='0'>
    <?
		for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
			echo "<tr>";
			if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y')
				echo "<td align='right' width='40px'><input type='checkbox' id='".$_SESSION['arr_order_sheet_products'][$i][0]."' name='select_print' checked></td>";
			else
				echo "<td align='right' width='40px'><input type='checkbox' id='".$_SESSION['arr_order_sheet_products'][$i][0]."' name='select_print'></td>";
			echo "<td>".$_SESSION['arr_order_sheet_products'][$i][1]."</td>";
			echo "</tr>";
		}
    ?>
    </table>

</body>
</html>