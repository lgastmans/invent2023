<?
    $str_product_code = "";
    if (IsSet($_GET["product_code"]))
        $str_product_code = $_GET["product_code"];

    $str_filter_day = "N";
    if (IsSet($_GET["filter_day"])) {
        $str_filter_day = $_GET["filter_day"];
        $str_filter_from = $_GET["filter_day_from"];
        $str_filter_to = $_GET["filter_day_to"];
    }

    $str_filter_type = "N";
    if (IsSet($_GET["filter_type"])) {
        $str_filter_type = $_GET["filter_type"];
        $str_filter_type_value = $_GET["filter_type_value"];
    }
?>

<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='stock_registry' rows='185,*' border=1 scrolling=no>
	<frame name='stock_registry_summary' src="stock_registry_summary.php?<?echo "code=".$str_product_code;?>" scrolling=no noresize>
	<frame name='stock_registry_details' src="stock_registry_details.php?<?echo "code=".$str_product_code."&filter=".$str_filter_day."&from=".$str_filter_from."&to=".$str_filter_to."&filter_type=".$str_filter_type."&filter_type_value=".$str_filter_type_value;?>" scrolling=yes noresize>
</frameset>

</html>