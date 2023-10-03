<?
        require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

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

	$str_status = 'ALL';
	if (IsSet($_GET['status']))
		$str_status = $_GET['status'];
	
	$flt_pending = 0;
	if (IsSet($_GET['pending']))
		$flt_pending = $_GET['pending'];
		
	$flt_complete = 0;
	if (IsSet($_GET['complete']))
		$flt_complete = $_GET['complete'];

	$flt_nofunds = 0;
	if (IsSet($_GET['nofunds']))
		$flt_nofunds = $_GET['nofunds'];

	$flt_cancelled = 0;
	if (IsSet($_GET['cancelled']))
		$flt_cancelled = $_GET['cancelled'];

	$flt_other = 0;
	if (IsSet($_GET['other']))
		$flt_other = $_GET['other'];

        $flt_total = 0;
        if (IsSet($_GET['total']))
            $flt_total = $_GET['total'];
	    
?>

<html>
<head><TITLE></TITLE>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>

<body leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

    <table width='100%' border='0'>
    <tr><td align='center'>
    
    <table border='1' cellpadding='7' cellspacing='0'>
        <? if ($str_status == 'ALL') { ?>
	<tr>
            <td align='right' class='<?echo $str_class_header?>'><b>Pending</b></td>
	    <td align='right' class='<?echo $str_class_header?>'><b><?echo $flt_pending?></b></td>
        </tr>
        <? } ?>
        <tr>
            <td align='right' class='<?echo $str_class_header?>'><b>Complete</b></td>
	    <td align='right' class='<?echo $str_class_header?>'><b><?echo $flt_complete?></b></td>
        </tr>
        <? if ($str_status == 'ALL') { ?>
        <tr>
            <td align='right' class='<?echo $str_class_header?>'><b>No Funds</b></td>
	    <td align='right' class='<?echo $str_class_header?>'><b><?echo $flt_nofunds?></b></td>
        </tr>
        <tr>
            <td align='right' class='<?echo $str_class_header?>'><b>Cancelled</b></td>
	    <td align='right' class='<?echo $str_class_header?>'><b><?echo $flt_cancelled?></b></td>
        </tr>
        <tr>
            <td align='right' class='<?echo $str_class_header?>'><b>Other</b></td>
	    <td align='right' class='<?echo $str_class_header?>'><b><?echo $flt_other?></b></td>
        </tr>
        <? } ?>
        <tr>
            <td align='right' class='<?echo $str_class_header?>'><b>Total</b></td>
	    <td align='right' class='<?echo $str_class_header?>'><b><?echo $flt_total?></b></td>
        </tr>
    </table>

    </td></tr>
    </table>
    
</body>
</html>