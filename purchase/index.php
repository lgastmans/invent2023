<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='purchase' rows='30,*,150' border=0 scrolling=no>
	<frame name='purchase_menu' src="../blank.htm" scrolling=no>
	<frame name='purchase_content' <? echo "src=\"purchase.php?cur_selected=".$_GET["cur_selected"]."&status=".$_GET["status"]."\""; ?> scrolling=auto>
	<frame name='purchase_content_detail' src="purchase_items_frameset.php" scrolling=auto>
</frameset>

</html>