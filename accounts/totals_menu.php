<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

 	$_SESSION['int_accounts_selected'] = 3;

	if ($_SESSION['str_user_font_size'] == 'small') {
		$str_class_header = "headertext_small";
		$str_class_input = "inputbox60_small";
		$str_class_input100 = "inputbox100_small";
		$str_class_input300 = "inputbox300_small";
		$str_class_select = "select_small";
		$str_class_select100 = "select100_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
		$str_class_header = "headertext";
		$str_class_input = "inputbox60";
		$str_class_input100 = "inputbox100";
		$str_class_input300 = "inputbox300";
		$str_class_select = "select";
		$str_class_select100 = "select100";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
		$str_class_header = "headertext_large";
		$str_class_input = "inputbox60_large";
		$str_class_input100 = "inputbox100_large";
		$str_class_input300 = "inputbox300_large";
		$str_class_select = "select_large";
		$str_class_select100 = "select100_large";
	}
	else {
		$str_class_header = "headertext";
		$str_class_input = "inputbox60";
		$str_class_input100 = "inputbox100";
		$str_class_input300 = "inputbox300";
		$str_class_select = "select";
		$str_class_select100 = "select100";
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


?>

<script language='javascript'>
    function update_data() {
        var oSelectDay = document.totals_menu.select_day;
        var oSelectType = document.totals_menu.select_type;
	var oSelectStatus = document.totals_menu.select_status;
        
        parent.frames['totals_data'].document.location = 'totals_data.php?'+
            'day='+oSelectDay.value+
            '&type='+oSelectType.value+
	    '&status='+oSelectStatus.value;
    }
    
    function update_text() {
	var oSpanTransferDate = document.getElementById('transfer_date');
	var oSelectStatus = document.totals_menu.select_status;
	
	if (oSelectStatus.value == 'ALL')
	    oSpanTransferDate.innerHTML = 'created';
	else
	    oSpanTransferDate.innerHTML = 'completed';
    }
</script>


<html>
<head><TITLE></TITLE>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>

<body leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>
<form name='totals_menu' method='GET'>

    <table border='0' cellpadding='5' cellspacing='0'>
        <tr>
	    <td align='right' width='50' class='<?echo $str_class_header?>'>List</td>
	    <td>
		<select name='select_status' onchange='update_text()' class='<?echo $str_class_select?>'>
		    <option value='ALL'>All
		    <option value='completed'>Completed
		</select>
	    </td>
	    <td class='<?echo $str_class_header?>'>transfers&nbsp;<span id='transfer_date'>created</span>&nbsp;on</td>
            <td>
                <select name='select_day' class='<?echo $str_class_select?>'>
                    <?
                        $int_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
                        $int_cur_day = date('d', time());
                        for ($i=1; $i<=$int_days; $i++) {
                            if ($i == $int_cur_day)
                                echo "<option value=".$i." selected=\"selected\">".$i;
                            else
                                echo "<option value=".$i.">".$i;
                        }
                    ?>
                </select>
            </td>
            <td align='right' width='50' class='<?echo $str_class_header?>'>Type</td>
            <td>
                <select name='select_type' class='<?echo $str_class_select?>'>
                    <option value='ALL'>All
                    <option value='Bill'>Bill
                    <option value='Order'>Order
                </select>
            </td>
            <td>
                <input type='button' name='action' value='load' onclick='update_data()'>
            </td>
        </tr>
    </table>
</form>
</body>
</html>