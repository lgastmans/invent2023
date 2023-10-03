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

        $int_day = date('d', time());
        if (IsSet($_GET['day']))
            $int_day = $_GET['day'];
            
        $str_type = 'ALL';
        if (IsSet($_GET['type']))
            $str_type = $_GET['type'];
	    
	$str_status = 'ALL';
	if (IsSet($_GET['status']))
	    $str_status = $_GET['status'];
	    
	if ($str_status == 'ALL')
	    $str_filter_on_date = 'date_created';
	else
	    $str_filter_on_date = 'date_completed';
            
        if ($str_type == 'Bill')
            $int_type = 2;
        else if ($str_type == 'Order')
            $int_type = 7;

        if ($str_type == 'ALL')
            $str_query = "
                SELECT *
                FROM ".Monthalize('account_transfers')."
                WHERE DAY(".$str_filter_on_date.") = ".$int_day;
        else
            $str_query = "
                SELECT *
                FROM ".Monthalize('account_transfers')."
                WHERE module_id = ".$int_type."
                    AND DAY(".$str_filter_on_date.") = ".$int_day;
        
        $qry = new Query($str_query);
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
        <tr>
            <td class='<?echo $str_class_header?>'>From Account</td>
            <td class='<?echo $str_class_header?>' align='right'>Amount</td>
            <td class='<?echo $str_class_header?>'>Description</td>
            <td class='<?echo $str_class_header?>'>Status</td>
        </tr>
        <?
            $flt_pending = 0;
            $flt_complete = 0;
            $flt_nofunds = 0;
            $flt_cancelled = 0;
            $flt_other = 0;
            $flt_total = 0;
            
            for ($i=0; $i<$qry->RowCount(); $i++) {
                if ($i % 2 == 0)
                    $str_color="#eff7ff";
                else
                    $str_color="#deecfb";

                echo "<tr bgcolor='$str_color'>";
                echo "<td align='right' class='normaltext'>".$qry->FieldByName('account_from')."</td>";
                echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('amount'), 2, '.', '')."</td>";
                echo "<td class='normaltext'>".$qry->FieldByName('description')."</td>";
                if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_PENDING) {
                    echo "<td class='normaltext'>Pending</td>";
                    $flt_pending += $qry->FieldByName('amount');
                }
                else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS) {
                    echo "<td class='normaltext'>Insufficient Funds</td>";
                    $flt_nofunds += $qry->FieldByName('amount');
                }
                else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_ERROR) {
                    echo "<td class='normaltext'>Error</td>";
                    $flt_other += $qry->FieldByName('amount');
                }
                else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_CANCELLED) {
                    echo "<td class='normaltext'><font color='red'>Cancelled</font></td>";
                    $flt_cancelled += $qry->FieldByName('amount');
                }
                else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_HOLD) {
                    echo "<td class='normaltext'>Hold</td>";
                    $flt_other += $qry->FieldByName('amount');
                }
                else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_COMPLETE) {
                    echo "<td class='normaltext'>Complete</td>";
                    $flt_complete += $qry->FieldByName('amount');
                }
                else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_REVIEW) {
                    echo "<td class='normaltext'>Review</td>";
                    $flt_other += $qry->FieldByName('amount');
                }
                echo "</tr>\n";
                
                $qry->Next();
            }
            
            $flt_total = $flt_complete - $flt_cancelled;
            $flt_total = number_format($flt_total, 2, '.', ',');

            $flt_pending = number_format($flt_pending, 2, '.', ',');
            $flt_complete = number_format($flt_complete, 2, '.', ',');
            $flt_nofunds = number_format($flt_nofunds, 2, '.', ',');
            $flt_cancelled = number_format($flt_cancelled, 2, '.', ',');
            $flt_other = number_format($flt_other, 2, '.', ',');

        ?>
    </table>

    </td></tr>
    </table>
    
<script language='javascript'>
    parent.frames['totals_footer'].document.location = 'totals_footer.php?'+
	'status=<?echo $str_status?>'+
        '&pending=<?echo $flt_pending?>'+
        '&complete=<?echo $flt_complete?>'+
        '&nofunds=<?echo $flt_nofunds?>'+
        '&cancelled=<?echo $flt_cancelled?>'+
        '&other=<?echo $flt_other?>'+
        '&total=<?echo $flt_total?>';
</script>

</body>
</html>