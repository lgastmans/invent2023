<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	//====================
	// process GET parameters
	//====================
	$int_order_id = 0;
	if (IsSet($_GET['order_id']))
		$int_order_id = $_GET['order_id'];

	$qry_order = new Query("
		SELECT *
		FROM ".Monthalize('orders')."
		WHERE order_id = ".$int_order_id."
	");

	$is_cancelled = 'N';
	if ($qry_order->FieldByName('order_status') == ORDER_STATUS_CANCELLED) {
		$is_cancelled = 'Y';
	}

	$qry_account = new Query("
		SELECT *
		FROM account_cc
		WHERE cc_id = ".$qry_order->FieldByName('CC_id')."
	");

	function getMySQLDate($aDate) {
		$arr_date = explode('-', $aDate);
		return $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
	}

    function validate_date($str_date) {
        $bool_retval = false;
        if (!empty($str_date)) {
            $arr_date = explode('-', $str_date);
	    
	    if (count($arr_date) == 3) {
		if (strlen($arr_date[2] < 4))
		    return $bool_retval;
		
		$bool_retval = checkdate($arr_date[1], $arr_date[0], $arr_date[2]);
	    }
        }
	
        return $bool_retval;
    }
    
	$cancel_from = date('d-m-Y', time());
	if (IsSet($_GET['cancel_from']))
	    $cancel_from = $_GET['cancel_from'];
	    
	$cancel_to = date('d-m-Y', time());
	if (IsSet($_GET['cancel_to']))
	    $cancel_to = $_GET['cancel_to'];

	$str_message = '';
	
	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'cancel') {
			$bool_success = true;
			
			if (!validate_date($cancel_from)) {
			    $bool_success = false;
			    $str_message = 'Invalid From date';
			}
			else if (!validate_date($cancel_to)) {
			    $bool_success = false;
			    $str_message = 'Invalid To date';
			}
			
			if (getMySQLDate($cancel_from) > getMySQLDate($cancel_to)) {
				$bool_success = false;
				$str_message = "From date cannot be greater than To date";
			}
			
			if ($bool_success) {
			    $str_cancel = "
				    UPDATE ".Monthalize('orders')."
				    SET order_status = ".ORDER_STATUS_CANCELLED.",
					    date_cancel_from = '".getMySQLDate($cancel_from)."',
					    date_cancel_till = '".getMySQLDate($cancel_to)."'
				    WHERE order_id = ".$int_order_id;
			    $qry_cancel = new Query($str_cancel);
    
			    if ($qry_cancel->b_error == false) {
				    echo "<script language='javascript'>";
				    echo "if (top.window.opener) \n";
				    echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
				    echo "window.close();";
				    echo "</script>";
			    }
			    else
				    $str_message = $qry_cancel.mysql_error();
			}
		}
		else if ($_GET['action'] == 'uncancel') {
			$str_cancel = "
				UPDATE ".Monthalize('orders')."
				SET order_status = ".ORDER_STATUS_ACTIVE.",
					date_cancel_from = '0000-00-00',
					date_cancel_till = '0000-00-00'
				WHERE order_id = ".$int_order_id;
			$qry_cancel = new Query($str_cancel);

			if ($qry_cancel->b_error == false) {
				echo "<script language='javascript'>";
				echo "if (top.window.opener) \n";
				echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
				echo "window.close();";
				echo "</script>";
			}
			else
				echo $str_cancel;
		}
	}
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
	<script language="JavaScript" src="../include/calendar1.js"></script>
	<script language='javascript'>

		function setCancelled(anOrderId) {
			var oTextFrom = document.order_cancel.cancel_from;
			var oTextTo = document.order_cancel.cancel_to;
			
			if ((oTextFrom.value.length == 0) || (oTextFrom.value == "") || (oTextTo.value.length == 0) || (oTextTo.value == "")) {
			    alert('The dates cannot be blank');
			}
			else {
			    document.location = 'order_cancel.php?action=cancel'+
				    '&order_id='+anOrderId+
				    '&cancel_from='+oTextFrom.value+
				    '&cancel_to='+oTextTo.value;
			}
		}

		function setNotCancelled(anOrderId) {
			document.location = 'order_cancel.php?action=uncancel'+
				'&order_id='+anOrderId;
		}

	</script>
</head>

<body marginheight="10px" marginwidth="10px">

<form name='order_cancel'>

<? if ($is_cancelled == 'N') { ?>

	<table width='100%' height='100%' border='0' cellpadding='5' cellspacing='0'>
		<tr>
			<td width='15px'></td>
			<td class='<?echo $str_class_header?>'>
				<? echo $qry_account->FieldByName('account_number')." - ".$qry_account->FieldByName('account_name'); ?>
			</td>
		</tr>
		<tr>
			<td width='15px'></td>
			<td class='<?echo $str_class_header?>'>
				Cancel this order
			</td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header?>'>From:</td>
			<td>
				<input type="text" name="cancel_from" value="<?echo $cancel_from?>">
				<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the date"></a>
			</td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header?>'>To:</td>
			<td>
				<input type="text" name="cancel_to" value="<?echo $cancel_to?>">
				<a href="javascript:cal2.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the date"></a>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type='button' name='action' value='Cancel' onclick='setCancelled(<?echo $int_order_id?>)'>
				<input type='button' name='action' value='Close' onclick='window.close()'>
			</td>
		</tr>
	</table>

<? } else { ?>

	<table width='100%' height='100%' border='0' cellpadding='5' cellspacing='0'>
		<tr>
			<td width='15px'></td>
			<td class='<?echo $str_class_header?>'>
				<? echo $qry_account->FieldByName('account_number')." - ".$qry_account->FieldByName('account_name'); ?>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class='<?echo $str_class_header?>'>This order is cancelled<br>Do you want to set it active again ? </td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type='button' name='action' value='Activate' onclick='setNotCancelled(<?echo $int_order_id?>)'>
				<input type='button' name='action' value='Close' onclick='window.close()'>
			</td>
		</tr>
	</table>

<? } ?>

</form>

<script language="JavaScript">
<?
    if ($str_message <> "") {
	echo "alert('".$str_message."');";
    }
?>
	var oTextFrom = document.order_cancel.cancel_from;
	var oTextTo = document.order_cancel.cancel_to;

	if (oTextFrom) {
		var cal1 = new calendar1(oTextFrom);
		cal1.year_scroll = true;
		cal1.time_comp = false;

		var cal2 = new calendar1(oTextTo);
		cal2.year_scroll = false;
		cal2.time_comp = false;
	}
</script>

</body>
</html>