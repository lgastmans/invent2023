<?
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	
	$_SESSION["int_bills_menu_selected"] = 10;
?>
<html>
	<head>
		<TITLE><? echo $str_application_title; ?></TITLE>
	</head>
	
	<frameset id='Statistics' rows='80,40,*' border=2 scrolling=no>
		<frame name='menu' src="statistics_menu.php" scrolling=no noresize>
		<frame name='details' src="statistics_details.php" scrolling=no noresize>
		<frame name='content' src="statistics_content.php" scrolling=auto>
	</frameset>
</html>