<html>
	<head>
		<TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?></TITLE>
	</head>

	<frameset id='Statements' rows='90,*' border=2 scrolling=no>
		<frame name='menu' src="statement_totals_menu.php" scrolling=no noresize>
		<frame name='content' src="statement_totals.php" scrolling=auto>
	</frameset>
</html>