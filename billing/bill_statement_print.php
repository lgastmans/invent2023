<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	
	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	$int_eject_lines = 12;
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	$billed = 'N';
	if (IsSet($_GET['billed']))
		$billed = $_GET['billed'];

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
		
	$int_cur_day = date('j');
  
    
	// check whether PT Accounts module is available
	$qry = new Query("
		SELECT *
		FROM module
		WHERE (module_id = 6)
			AND (active='Y')
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

    $qry_bills = new Query($str_bills);
    

//================================== PRINTER OUTPUT

	define('STATEMENT_WIDTH', 85);
	$cur_year = $_SESSION["int_year_loaded"];
	$cur_month = getMonthName($_SESSION["int_month_loaded"]);
	if ($billed == 'ALL')
		if ($str_filter_day == 'Y')
			$str_title = "Bill Statement from $str_day_from $cur_month $cur_year to $str_day_to $cur_month $cur_year";
		else
			$str_title = "Bill Statement";
	else
		if ($str_filter_day == 'Y')
			$str_title = "$billed Statement from $str_day_from $cur_month $cur_year to $str_day_to $cur_month $cur_year";
		else
			$str_title = "$billed Statement";
	
	$str_top = "";
	$str_top = PadWithCharacter($str_top, '=', STATEMENT_WIDTH);
	
	$str_bottom = "";
	$str_bottom = PadWithCharacter($str_bottom, '-', STATEMENT_WIDTH);
	
	$str_bills = '';
	
    if ($qry_bills->RowCount() > 0) {
        

	
        for ($i=0;$i<$qry_bills->RowCount();$i++) {
                
            if ($qry_bills->FieldByName('payment_type') == BILL_CASH)
				$str_bills .= "CASH\n";
            else if ($qry_bills->FieldByName('payment_type') == BILL_ACCOUNT)
				$str_bills .= "FS Account: ".$qry_bills->FieldByName('account_number')." - ".$qry_bills->FieldByName('account_name')."\n";
            else if ($qry_bills->FieldByName('payment_type') == BILL_PT_ACCOUNT)
                $str_bills .= "PT Account: ".$qry_bills->FieldByName('account_number')." - ".$qry_bills->FieldByName('account_name')."\n";
            
            if ($qry_bills->FieldByName('bill_status') == BILL_STATUS_UNRESOLVED)
				$str_bills .=
				StuffWithCharacter($qry_bills->FieldByName('bill_number'), ' ', 5)." ".
				PadWithCharacter(makeHumanTime($qry_bills->FieldByName('date_created')), ' ', 15)." ".
				StuffWithCharacter(number_format($qry_bills->FieldByName('total_amount'),2,'.',','), ' ', 10)." ".
				PadWithCharacter('UNRESOLVED', ' ', 15)." ".
				PadWithCharacter($qry_bills->FieldByName('username'), ' ', 30)."\n";
			else
				$str_bills .=
				StuffWithCharacter($qry_bills->FieldByName('bill_number'), ' ', 5)." ".
				PadWithCharacter(makeHumanTime($qry_bills->FieldByName('date_created')), ' ', 15)." ".
				StuffWithCharacter(number_format($qry_bills->FieldByName('total_amount'),2,'.',','), ' ', 10)." ".
				PadWithCharacter(makeHumanTime($qry_bills->FieldByName('resolved_on')), ' ', 15)." ".
				PadWithCharacter($qry_bills->FieldByName('username'), ' ', 30)."\n";

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
						bi.price,
						mu.measurement_unit,
						st.tax_description
                    FROM
						".Monthalize('bill_items')." bi
					INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
				    LEFT JOIN stock_measurement_unit mu ON sp.measurement_unit_id = mu.measurement_unit_id
					INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
					INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = bi.tax_id)
                    WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').") ".$str_where."
                    ORDER BY product_code";

                $qry_items->Query($str_items);
                
                if ($qry_items->RowCount() > 0 ) {
                    $str_item_data = '';
                    for ($j=0;$j<$qry_items->RowCount();$j++) {
						$str_item_data .=
						StuffWithCharacter($qry_items->FieldByName('product_code'), ' ', 10)." ".
						PadWithCharacter($qry_items->FieldByName('product_description'), ' ', 30)." ".
						StuffWithCharacter((number_format($qry_items->FieldByName('quantity'),3,'.',',')." ".$qry_items->FieldByName('measurement_unit')), ' ', 15)." ".
						StuffWithCharacter("Rs.".number_format($qry_items->FieldByName('price'),2,'.',','), ' ', 10)." ".
						StuffWithCharacter($qry_items->FieldByName('tax_description'),' ',4)." ".
						StuffWithCharacter("Rs.".number_format($qry_items->FieldByName('amount'),2,'.',','), ' ', 10)."\n";
                        
                        $qry_items->Next();
                    }
                }
				$str_bills .= $str_bottom."\n".$str_item_data."\n".$str_top."\n";
            }
            
            $qry_bills->Next();
        }
    }

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_header =
	StuffWithCharacter('Bill', ' ', 5)." ".
	PadWithCharacter('Date', ' ', 15)." ".
	StuffWithCharacter('Amount', ' ', 10)." ".
	PadWithCharacter('Transferred', ' ', 15)." ".
	PadWithCharacter('User', ' ', 30);

$str_statement = "%c
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_title."
".$str_top."
".$str_header."
".$str_bottom."
".$str_bills."
".$str_top.$str_eject_lines."%n";

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
echo $str_statement;
?>
</PRE>


<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
	  <input type="hidden" name="os" value="<? echo $os;?>"><br>
	  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
	  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>

    </td>
  </tr>
  <tr>
    <td class='normaltext'>
      <textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea>
    </td>
  </tr>
  <tr>
    <td align='center'>
      <br><input type='submit' name='doaction' value="Print">
      <input type='button' onclick="window.close();" name='doaction' value="Close">
    </td>
  </tr>
</table>

</form>

<script language="JavaScript">
	printerForm.submit();
</script>

</body>
</html>
