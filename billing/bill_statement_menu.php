<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
	
    $int_cur_day = date('j');
  
    // check whether PT Accounts module is available
    $qry = new Query("
        SELECT *
        FROM module
        WHERE (module_id = 6)
		AND active='Y'
    ");
    $bool_pt = false;
    if ($qry->RowCount() > 0)
        $bool_pt = true;
?>

<script language="javascript">

    function mouseGoesOver(element, aSource)
    {
        element.src = aSource;
    }

    function mouseGoesOut(element, aSource)
    {
        element.src = aSource;
    }
    function setComparison(aValue) {
        var oFilterComparison = document.getElementById('filter_comparison');
        if (aValue == 'product')
            oFilterComparison.innerHTML = '=';
        else
            oFilterComparison.innerHTML = '>=';
    }

    function setText(evt) {
        evt = (evt) ? evt : event;
        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

        if (charCode == 13 || charCode == 3 || charCode == 9) {
            apply_settings('screen');
        }
        return true;
    }
    
    function apply_settings(aDestination) {

        var oCheckboxAccount = document.bill_statement_menu.checkbox_account;
        var oTextAccountNumber = document.bill_statement_menu.account_number;
        var oCheckboxDay = document.bill_statement_menu.checkbox_day;
        var oSelectFrom = document.bill_statement_menu.select_days_from;
        var oSelectTo = document.bill_statement_menu.select_days_to;
        var oCheckboxFilter = document.bill_statement_menu.checkbox_filter;
        var oSelectFilter = document.bill_statement_menu.select_filter;
        var oTextFilter = document.bill_statement_menu.filter_value;
        var oCheckboxDetails = document.bill_statement_menu.checkbox_details;
	var oBilled = document.getElementById('billed');
        
        str_account = 'N';
        if (oCheckboxAccount.checked)
            str_account='Y';

        str_day = 'N';
        if (oCheckboxDay.checked)
            str_day = 'Y';
        
        str_filter = 'N';
        if (oCheckboxFilter.checked)
            str_filter = 'Y';
        
        str_details = 'N';
        if (oCheckboxDetails.checked)
            str_details = 'Y';
            
        if (aDestination == 'screen') {
	    str_url = "bill_statement.php?"+
		"account="+str_account+
		"&account_number="+oTextAccountNumber.value+
		"&filter_day="+str_day+
		"&filter_day_from="+oSelectFrom.value+
		"&filter_day_to="+oSelectTo.value+
		"&filter_extra="+str_filter+
		"&filter_field="+oSelectFilter.value+
		"&filter_value="+oTextFilter.value+
		"&show_details="+str_details+
		"&billed="+oBilled.value;
	    
	   // alert(str_url);
	    
	    parent.frames["content"].document.location = str_url;
	}
	else {
	    str_url = "bill_statement_print.php?"+
		"account="+str_account+
		"&account_number="+oTextAccountNumber.value+
		"&filter_day="+str_day+
		"&filter_day_from="+oSelectFrom.value+
		"&filter_day_to="+oSelectTo.value+
		"&filter_extra="+str_filter+
		"&filter_field="+oSelectFilter.value+
		"&filter_value="+oTextFilter.value+
		"&show_details="+str_details+
		"&billed="+oBilled.value;
	    window.open(str_url, "print_window");
	}
    }
    
    function printStatement() {
        apply_settings('printer');
    }
    
</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="bill_statement_menu" onsubmit="return false">

    <font class='normaltext'>

        <input type="checkbox" name="checkbox_account" onchange="javascript:apply_settings('screen')" checked>
        <?
            if ($bool_pt == true) { 
                echo "PT Account :";
            } else { 
                echo "FS Account :";
            }
        ?>
        <input type="text" name="account_number" value="" class='input_100' onkeypress="return setText(event)">
        &nbsp;
        
        <input type="checkbox" name="checkbox_day" onchange="javascript:apply_settings('screen')">
        From : 
        <select name="select_days_from" onchange="javascript:apply_settings('screen')" class='select_100'>
            <?
                $int_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
                for ($i=1; $i<=$int_days; $i++) {
                    if ($i == $int_cur_day)
                        echo "<option value=".$i." selected=\"selected\">".$i;
                    else
                        echo "<option value=".$i.">".$i;
                }
            ?>
        </select>
        to
        <select name="select_days_to" onchange="javascript:apply_settings('screen')" class='select_100'>
            <?
                $int_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
                for ($i=1; $i<=$int_days; $i++) {
                    if ($i == $int_cur_day)
                        echo "<option value=".$i." selected=\"selected\">".$i;
                    else
                        echo "<option value=".$i.">".$i;
                }
            ?>
        </select>
        &nbsp;
        
        <input type="checkbox" name="checkbox_filter" onchange="javascript:apply_settings('screen')">
        Filter on
        <select name="select_filter" class='select_200' onchange="javascript:setComparison(this.value)">
            <option value="product">product code
            <option value="amount">bill total
        </select>
        <span id="filter_comparison">=</span>
        <input type="text" name="filter_value" value="" class='input_100' onkeypress="return setText(event)">
        <br>
	<label>
        	<input type="checkbox" name="checkbox_details" onchange="javascript:apply_settings('screen')">Show bill details
	</label>
	&nbsp;&nbsp;
	Show
        <select name="billed" id="billed" onchange="javascript:apply_settings('screen')">
		<option value="ALL">All Transfers</option>
		<option value="Billed">Billed Transfers</option>
		<option value="Donation">Donations</option>
	</select>
    </font>
    &nbsp;
    
    <a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
</form>

<script language="javascript">
        var oCheckboxAccount = document.bill_statement_menu.checkbox_account;
        var oTextAccountNumber = document.bill_statement_menu.account_number;
        var oCheckboxDay = document.bill_statement_menu.checkbox_day;
        var oSelectFrom = document.bill_statement_menu.select_days_from;
        var oSelectTo = document.bill_statement_menu.select_days_to;
        var oCheckboxFilter = document.bill_statement_menu.checkbox_filter;
        var oSelectFilter = document.bill_statement_menu.select_filter;
        var oTextFilter = document.bill_statement_menu.filter_value;
        var oCheckboxDetails = document.bill_statement_menu.checkbox_details;

        str_account = 'N';
        if (oCheckboxAccount.checked)
            str_account='Y';

        str_day = 'N';
        if (oCheckboxDay.checked)
            str_day = 'Y';
        
        str_filter = 'N';
        if (oCheckboxFilter.checked)
            str_filter = 'Y';
        
        str_details = 'N';
        if (oCheckboxDetails.checked)
            str_details = 'Y';
	
	str_url = "bill_statement.php?"+
	    "account="+str_account+
	    "&account_number="+oTextAccountNumber.value+
	    "&filter_day="+str_day+
	    "&filter_day_from="+oSelectFrom.value+
	    "&filter_day_to="+oSelectTo.value+
	    "&filter_extra="+str_filter+
	    "&filter_field="+oSelectFilter.value+
	    "&filter_value="+oTextFilter.value+
	    "&show_details="+str_details;
	
	//alert(str_url);
	
	parent.frames["content"].document.location = str_url;
</script>

</body>
</html>