<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='orders' rows='30,*,150' border=2 scrolling=no>
	<frame name='order_bills_menu' src="../blank.htm" scrolling=no noresize>
	<frame name='order_bills_content' src="order_bills.php" scrolling=auto>
	<frame name='order_bills_content_detail' src="order_bill_items_frameset.php" scrolling=auto>
</frameset>

</html>