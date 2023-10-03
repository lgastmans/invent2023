<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='orders' rows='30,*,150' border=2 scrolling=no>
	<frame name='orders_menu' src="../blank.htm" scrolling=no noresize>
	<frame name='orders_content' src="orders.php" scrolling=auto>
	<frame name='orders_content_detail' src="order_items_frameset.php" scrolling=auto>
</frameset>

</html>