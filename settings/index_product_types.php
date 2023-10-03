<html>
	<head>
		<TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?></TITLE>
	</head>
	<frameset id='product_types' rows='30,*,250' border=0 scrolling=no>
		<frame name='product_types_menu' src="../blank.htm" scrolling=no>
		<frame name='product_types_content' src="product_types.php" scrolling=auto>
		<frame name='product_types_detail' src="index_product_types_details.php" scrolling=auto>
	</frameset>
</html>