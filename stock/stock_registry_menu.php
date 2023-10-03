<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	
	$int_cur_day = date('j');
	
	$_SESSION["int_stock_selected"] = 9;
	
	$qry_types = new Query("
		SELECT *
		FROM stock_transfer_type
		ORDER BY transfer_type_description
	");
	
	$str_code = "";
	if (IsSet($_SESSION['current_filter_value']))
		$str_code = $_SESSION['current_filter_value'];
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

    function setText(evt, aField) {
        evt = (evt) ? evt : event;
        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

        if (charCode == 13 || charCode == 3 || charCode == 9) {
            aField.select();
            apply_settings('screen');
        }
        return true;
    }
    
    function apply_settings(aDestination) {
        var oTextCode = document.bill_statement_menu.product_code;
        var oCheckboxDay = document.bill_statement_menu.checkbox_day;
        var oSelectFrom = document.bill_statement_menu.select_days_from;
        var oSelectTo = document.bill_statement_menu.select_days_to;
        var oCheckboxType = document.bill_statement_menu.checkbox_type;
        var oSelectType = document.bill_statement_menu.select_type;

        str_day = 'N';
        if (oCheckboxDay.checked)
            str_day = 'Y';
        	
        str_type = 'N';
        if (oCheckboxType.checked)
            str_type = 'Y';

        if (aDestination == 'screen') {
            str_url = "stock_registry_frameset.php?"+
                "product_code="+oTextCode.value+
                "&filter_day="+str_day+
                "&filter_day_from="+oSelectFrom.value+
                "&filter_day_to="+oSelectTo.value+
                "&filter_type="+str_type+
                "&filter_type_value="+oSelectType.value;
	
	    
	    //alert(str_url);
	    
	    parent.frames["stock_registry_frameset"].document.location = str_url;
	}
	else {
            str_url = "stock_registry_print.php?"+
                "product_code="+oTextCode.value+
                "&filter_day="+str_day+
                "&filter_day_from="+oSelectFrom.value+
                "&filter_day_to="+oSelectTo.value+
                "&filter_type="+str_type+
                "&filter_type_value="+oSelectType.value;
	
	    var myWin = window.open(str_url, "print_window");
	    myWin.focus();
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

        Code:
        <input type="text" name="product_code" value="<?php echo $str_code;?>" onkeypress="return setText(event, this)"  class='input_100'>
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
        
        <input type='checkbox' name='checkbox_type' onchange="javascript:apply_settings('screen')">
        Type:
        <select name="select_type" onchange="javascript:apply_settings('screen')" class='select_200'>
        <?
            for ($i=0;$i<$qry_types->RowCount();$i++) {
                echo "<option value='".$qry_types->FieldByName('transfer_type')."'>".$qry_types->FieldByName('transfer_type_description');
                $qry_types->Next();
            }
        ?>
        </select>
        
    </font>
    &nbsp;
    
    <a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
</form>

<script language="javascript">
        var oTextCode = document.bill_statement_menu.product_code;
        var oCheckboxDay = document.bill_statement_menu.checkbox_day;
        var oSelectFrom = document.bill_statement_menu.select_days_from;
        var oSelectTo = document.bill_statement_menu.select_days_to;
        var oCheckboxType = document.bill_statement_menu.checkbox_type;
        var oSelectType = document.bill_statement_menu.select_type;

        str_day = 'N';
        if (oCheckboxDay.checked)
            str_day = 'Y';
        	
        str_type = 'N';
        if (oCheckboxType.checked)
            str_type = 'Y';
            
	str_url = "stock_registry_frameset.php?"+
	    "product_code="+oTextCode.value+
	    "&filter_day="+str_day+
	    "&filter_day_from="+oSelectFrom.value+
	    "&filter_day_to="+oSelectTo.value+
            "&filter_type="+str_type+
            "&filter_type_value="+oSelectType.value;
	
//	alert(str_url);
	
	parent.frames["stock_registry_frameset"].document.location = str_url;
</script>

</body>
</html>