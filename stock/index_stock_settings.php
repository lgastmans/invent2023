<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    $_SESSION["int_stock_selected"] = 12;
    
    if (IsSet($_POST["action"])) {
        if ($_POST["action"] == "save") {
            if (IsSet($_POST["is_equal_prices"]))
                $str_is_equal_prices = 'Y';
            else
                $str_is_equal_prices = 'N';
	    
	    if (IsSet($_POST['cb_show_returned']))
		$str_show_returned = 'Y';
	    else
		$str_show_returned = 'N';
            
	    if (IsSet($_POST['cb_show_available']))
		$str_show_available = 'Y';
	    else
		$str_show_available = 'N';
	    
            $sql = new Query("
                UPDATE user_settings
                SET stock_is_equal_prices = '".$str_is_equal_prices."',
		    stock_bulk_unit = ".$_POST['select_bulk'].",
		    stock_packaged_unit = ".$_POST['select_packaged'].",
		    stock_show_returned = '".$str_show_returned."',
		    stock_show_available = '".$str_show_available."'
            ");
        }
    }
    
    $qry_units = new Query("
	SELECT *
	FROM stock_measurement_unit
	ORDER BY measurement_unit
    ");
    
    $sql = new Query("
        SELECT *
        FROM user_settings
	WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
    ");

?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/bill_styles.css" />
</head>

<script language="javascript">
    function saveSettings() {
        stock_settings.submit();
    }
</script>

<body>

<form name="stock_settings" method="POST">
<br>
<br>

<table border="0" cellpadding="5" cellspacing="0" width="80%">
    <tr>
        <td class="headertext_r">Bulk product measurement unit:</td>
        <td>
            <select name='select_bulk'>
	    <?
		for ($i=0;$i<$qry_units->RowCount();$i++) {
		    if ($sql->FieldByName('stock_bulk_unit') == $qry_units->FieldByName('measurement_unit_id'))
			echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."' selected>".$qry_units->FieldByName('measurement_unit');
		    else
			echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."'>".$qry_units->FieldByName('measurement_unit');
		    $qry_units->Next();
		}
	    ?>
	    </select>
        </td>
    </tr>
    <tr>
        <td class="headertext_r">Packaged product measurement unit:</td>
        <td>
            <select name='select_packaged'>
	    <?
		$qry_units->First();
		for ($i=0;$i<$qry_units->RowCount();$i++) {
		    if ($sql->FieldByName('stock_packaged_unit') == $qry_units->FieldByName('measurement_unit_id'))
			echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."' selected>".$qry_units->FieldByName('measurement_unit');
		    else
			echo "<option value='".$qry_units->FieldByName('measurement_unit_id')."'>".$qry_units->FieldByName('measurement_unit');
		    $qry_units->Next();
		}
	    ?>
	    </select>
        </td>
    </tr>
    <tr>
        <td class="headertext_r">&nbsp;</td>
        <td>
            <input type="checkbox" name="is_equal_prices" <? if ($sql->FieldByName('stock_is_equal_prices') == 'Y') echo "checked";?>><font class="headertext"> Buying price and selling price are equal</font>
        </td>
    </tr>
    <tr>
        <td class="headertext_r">&nbsp;</td>
        <td>
            <input type="checkbox" name="cb_show_returned" <? if ($sql->FieldByName('stock_show_returned') == 'Y') echo "checked";?>><font class="headertext"> Display returned stock in Supplier Received statement</font>
        </td>
    </tr>
    <tr>
        <td class="headertext_r">&nbsp;</td>
        <td>
            <input type="checkbox" name="cb_show_available" <? if ($sql->FieldByName('stock_show_available') == 'Y') echo "checked";?>><font class="headertext"> Display stock marked as 'available' in the Stock|Products grid</font>
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
          <input type="button" class="v3button" name="Save" value="Save" onclick="saveSettings()">
          <input type="hidden" name="action" value="save">
        </td>
    </tr>
</table>

</form>  

</body>
</html>
