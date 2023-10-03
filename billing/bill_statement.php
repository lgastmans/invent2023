<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	$_SESSION["int_bills_menu_selected"] = 3;
	
	$str_account = 'N';
	if (IsSet($_GET["account"])) {
		$str_account = $_GET["account"];
		$str_account_number = $_GET["account_number"];
	}
	
	$str_filter_day = 'N';
	if (IsSet($_GET["filter_day"])) {
		$str_filter_day = $_GET["filter_day"];
		$str_day_from = $_GET["filter_day_from"];
		$str_day_to = $_GET["filter_day_to"];
	}
	
	$str_filter_extra = 'N';
	if (IsSet($_GET["filter_extra"])) {
		$str_filter_extra = $_GET["filter_extra"];
		$str_filter_field = $_GET["filter_field"];
		$str_filter_value = $_GET["filter_value"];
	}
	
	$str_details = 'N';
	if (IsSet($_GET["show_details"]))
		$str_details = $_GET["show_details"];
	$qry_items = new Query("SELECT * FROM module"); // dummy initialization
	
	$billed = 'N';
	if (IsSet($_GET['billed']))
		$billed = $_GET['billed'];
	
	$int_cur_day = date('j');
  
    
	// check whether PT Accounts module is available
	$qry = new Query("
		SELECT *
		FROM module
		WHERE (module_id = 6)
			AND (active = 'Y')
	");
	$bool_pt = false;
	if ($qry->RowCount() > 0)
		$bool_pt = true;
  
    
	if ($bool_pt == true) {
		$str_select = ", ";
		$str_from = "";
		$str_where = "";
		
		if ($str_account == 'Y') {
		$str_select .="
			pt.account_number,
			pt.account_name";
	
		$str_from .= ", account_pt pt ";
		
		$str_where .= "
			AND (b.payment_type = 3)
			AND (pt.account_id = b.CC_id)
			AND (pt.account_number = '".$str_account_number."')";
		}
		
		if ($str_filter_day == 'Y') {
		$str_where .= "
			AND (DATE(b.date_created)
				BETWEEN '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_day_from)."'
				AND '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_day_to)."'
			)
		";
		}
		
		if ($str_filter_extra == 'Y') {
		if ($str_filter_field == 'product') {
			$str_from .= ", ".Monthalize('bill_items')." bi, stock_product sp";
			$str_where .= "
			AND (bi.bill_id = b.bill_id)
			AND (bi.product_id = sp.product_id)
			AND (sp.product_code = '".$str_filter_value."')";
		}
		else {
			$str_where .= "
			AND (b.total_amount >= ".intval($str_filter_value).")";
		}
		}
		
		if (strlen($str_select) < 3)
		$str_select = "";
		
		$str_bills = "
		SELECT DISTINCT
			b.bill_id,
			b.storeroom_id,
			b.bill_number,
			b.date_created,
			b.total_amount,
			b.payment_type,
			b.payment_type_number,
			b.bill_promotion,
			b.bill_status,
			b.is_pending,
			b.is_debit_bill,
			b.resolved_on,
			b.user_id,
			u.username".
			$str_select."
		FROM user u ".
			$str_from."
		INNER JOIN ".Monthalize('bill')." b ON (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		WHERE (u.user_id = b.user_id)".
			$str_where;
	}
	else {
		$str_select = ", ";
		$str_from = "";
		$str_left_join = "";
		$str_where = "";
		
		if ($str_account == 'Y') {
			$str_select .="
				ac.account_number,
				ac.account_name";
			
			$str_from .= ", account_cc ac ";
			
			$str_where .= "
				AND (b.payment_type = 2)
				AND (ac.cc_id = b.CC_id)
				AND (ac.account_number = '".$str_account_number."')";
		}
		else {
			$str_select .="
				ac.account_number,
				ac.account_name";
			
			$str_left_join .= "LEFT JOIN account_cc ac ON (ac.cc_id = b.CC_id)";
		}
		
		if ($billed != 'ALL') {
			$str_select .= ", o.is_billable";
			if ($billed == 'Billed')
				$str_left_join .= "
					INNER JOIN ".Monthalize('orders')." o ON ( o.order_id = b.module_record_id )
						AND (b.module_id = 7)
						AND (o.is_billable = 'Y')";
			else
				$str_left_join .= "
					INNER JOIN ".Monthalize('orders')." o ON ( o.order_id = b.module_record_id )
						AND (b.module_id = 7)
						AND (o.is_billable = 'N')";
		}
		
		if ($str_filter_day == 'Y') {
			$str_where .= "
				AND (DATE(b.date_created)
					BETWEEN '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_day_from)."'
					AND '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_day_to)."'
				)
			";
		}
		
		if ($str_filter_extra == 'Y') {
			if ($str_filter_field == 'product') {
				$str_from .= ", ".Monthalize('bill_items')." bi, stock_product sp";
				$str_where .= "
				AND (bi.bill_id = b.bill_id)
				AND (bi.product_id = sp.product_id)
				AND (sp.product_code = '".$str_filter_value."')";
			}
			else {
				$str_where .= "
				AND (b.total_amount >= ".intval($str_filter_value).")";
			}
		}
		
		if (strlen($str_select) < 3)
			$str_select = "";
		
		$str_bills = "
			SELECT DISTINCT
				b.bill_id,
				b.storeroom_id,
				b.bill_number,
				b.date_created,
				b.total_amount,
				b.payment_type,
				b.payment_type_number,
				b.bill_promotion,
				b.bill_status,
				b.is_pending,
				b.is_debit_bill,
				b.resolved_on,
				b.user_id,
				u.username,
				u2.username AS cancelled_username,
				b.cancelled_reason".
				$str_select."
			FROM user u".
				$str_from." 
			INNER JOIN ".Monthalize('bill')." b ON (storeroom_id = ".$_SESSION["int_current_storeroom"].")".
				$str_left_join."
			LEFT JOIN user u2 ON (u2.user_id = b.cancelled_user_id)
			WHERE (u.user_id = b.user_id)".
				$str_where;
	}
	//echo $str_bills;
	$qry_bills = new Query($str_bills);

?>

<html>
<head><TITLE></TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<?
//echo $str_bills."<br><br>";

    if ($qry_bills->RowCount() > 0) {
        
        echo "<table width='100%' border=1 cellpadding=7 cellspacing=0>";
        echo "<tr class='headertext' bgcolor='#808080'><td>Bill</td> <td>Date</td> <td>Amount</td> <td>Transferred</td> <td>User</td> </tr>";
        
        $flt_grand_total = 0;
        $flt_qty_total = 0;
        for ($i=0;$i<$qry_bills->RowCount();$i++) {
            if ($i % 2 == 0)
                $str_color="#eff7ff";
            else
                $str_color="#deecfb";
                
            $flt_grand_total += $qry_bills->FieldByName('total_amount');
                
		if ($qry_bills->FieldByName('is_debit_bill')=='Y')
			echo "<tr bgcolor='#FF7474'>";
		else
			echo "<tr bgcolor='$str_color'>";
            echo "<td class='normaltext'>".$qry_bills->FieldByName('bill_number')."</td>";
            echo "<td class='normaltext'>".makeHumanTime($qry_bills->FieldByName('date_created'))."</td>";
            echo "<td class='normaltext'><b>".number_format($qry_bills->FieldByName('total_amount'),2,'.',',')."</b></td>";
            if ($qry_bills->FieldByName('bill_status') == BILL_STATUS_RESOLVED)
                echo "<td class='normaltext'>".makeHumanTime($qry_bills->FieldByName('resolved_on'))."</td>";
            else if ($qry_bills->FieldByName('bill_status') == BILL_STATUS_UNRESOLVED)
                echo "<td class='normaltext'><b>UNRESOLVED</b></td>";
            echo "<td class='normaltext'>".$qry_bills->FieldByName('username')."</td>";
            echo "</tr>";
		if ($qry_bills->FieldByName('is_debit_bill')=='Y')
			echo "<tr bgcolor='#FF7474'><td class='normaltext' colspan='5'>";
		else
			echo "<tr bgcolor='$str_color'><td class='normaltext' colspan='5'>";
		if ($qry_bills->FieldByName('payment_type') == BILL_CASH)
			echo "CASH";
		else if ($qry_bills->FieldByName('payment_type') == BILL_ACCOUNT)
			echo "FS Account: ".$qry_bills->FieldByName('account_number')." - ".$qry_bills->FieldByName('account_name');
		else if ($qry_bills->FieldByName('payment_type') == BILL_PT_ACCOUNT)
			echo "PT Account: ".$qry_bills->FieldByName('account_number')." - ".$qry_bills->FieldByName('account_name');
		if ($qry_bills->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
			$str_username = $qry_bills->FieldByName('cancelled_username');
			$str_reason = '';
			if ($str_username <> "")
				$str_reason .= $str_username;
			else
				$str_reason .= "&lt;unknown&gt;";

			if ($qry_bills->FieldByName('cancelled_reason') <> "")
				$str_reason .= " for ".$qry_bills->FieldByName('cancelled_reason');
			else
				$str_reason .= " reason not specified";

			echo "<font color='red'>  Cancelled by ".$str_reason."</font>";
		}
		echo "</td></tr>";
            
            if ($str_details == 'Y') {
                $str_where="";
                if ($str_filter_extra == 'Y') {
                    if ($str_filter_field == 'product') {
                        $str_where = " AND (sp.product_code = ".$str_filter_value.") ";
                    }
                }
		
			$str_items = "
				SELECT
					bi.bill_item_id,
					(bi.quantity + bi.adjusted_quantity) AS quantity,
					bi.discount,
					((bi.quantity + bi.adjusted_quantity) * bi.price) AS amount,
					sp.product_code,
					sp.product_description,
					sb.batch_code,
					bi.price
				FROM
					".Monthalize('bill_items')." bi
					INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
					LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
				WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').") ".$str_where."
				ORDER BY product_code";
//echo $str_items;
                $qry_items->Query($str_items);
                
                if ($qry_items->RowCount() > 0 ) {
                    echo "<tr><td colspan='5'>";
                    echo "<table width='80%' border=1 cellpadding=2 cellspacing=0>";
                    
                    for ($j=0;$j<$qry_items->RowCount();$j++) {
                        echo "<tr>";
                        echo "<td class='normaltext' align='right'>".$qry_items->FieldByName('product_code')."</td>";
                        echo "<td class='normaltext'>".$qry_items->FieldByName('product_description')."</td>";
                        echo "<td class='normaltext' align='right'>".number_format($qry_items->FieldByName('quantity'),3,'.',',')."</td>";
                        echo "<td class='normaltext' align='right'> Rs.".number_format($qry_items->FieldByName('price'),2,'.',',')."</td>";
                        echo "<td class='normaltext' align='right'> Rs.".number_format($qry_items->FieldByName('amount'),2,'.',',')."</td>";
                        echo "</tr>";
                        
                        $flt_qty_total += $qry_items->FieldByName('quantity');

                        $qry_items->Next();
                    }
                    
                    echo "</table>";
                    echo "</td></tr>";
                }
            }
            
            $qry_bills->Next();
        }
        echo "</table>";
    }
    else {
        echo "No bills found for the given criteria";
    }
?>
<script language='javascript'>
    parent.frames['footer'].document.location = "bill_statement_footer.php?total=<?echo number_format($flt_grand_total,2,'.',',');?>&qty=<?echo number_format($flt_qty_total,2,'.',',');?>";
</script>

</html>