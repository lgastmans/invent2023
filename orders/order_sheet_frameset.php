<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='index_order_sheet' cols='*,175' border=2 scrolling=no>
	<frame name='order_sheet_data' src="order_sheet_content.php" scrolling=auto>
        <frame name='order_sheet_products' src='order_sheet_products.php' scrolling=auto>
</frameset>

</html>