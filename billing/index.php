<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='billing' rows='*' border=0 scrolling=no>
	<frame name='billing_content' src="bills_grid.php" scrolling=auto>
</frameset>

<!--
<frameset id='billing' rows='30,*,150' border=2 scrolling=no>
	<frame name='billing_menu' src="../blank.htm" scrolling=no noresize>
	<frame name='billing_content' src="bills.php" scrolling=auto>
	<frame name='billing_content_detail' src="bill_items_frameset.php" scrolling=auto>
</frameset>
-->
</html>