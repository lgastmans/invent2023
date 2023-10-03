<?
	session_start();
?>

<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<frameset id='purchase' rows='50,50,*,70' border=0 scrolling=no>
	<frame name='frame_header' src="po_view_header.php?id=<?echo $_GET["id"]?>"></frame>
	<frame name='frame_entry' src="po_view_enter.php" scrolling=no>
	<frame name='frame_details' src="po_view_list.php" scrolling=auto>
	<frame name='frame_action' src="po_view_action.php" scrolling=auto>
</frameset>

</html>