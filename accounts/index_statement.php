<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");

		
?>
<html>
	<head>
		<TITLE><? echo $str_application_title; ?></TITLE>
	</head>
	
	<frameset id='Statement' rows='80,*' border=2 scrolling=no>
		<frame name='menu' src="statement_menu.php" scrolling=no noresize>
		<frame name='content' src="../blank.htm" scrolling=auto>
	</frameset>
</html>