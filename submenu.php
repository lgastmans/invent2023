<?
	require_once  "include/config.inc.php";
	require_once  "include/const.inc.php";
	require_once "include/session.inc.php";
	require_once  "include/db.inc.php";
	require_once  "include/module.inc.php";

	$int_module_selected = 1;
	if (IsSet($_GET["selected"]))
		$int_module_selected = $_GET["selected"];
	
?>
<html>
<head>
	<link href="include/styles.css" rel="stylesheet" type="text/css">
</head>
<body style="background-color:transparent;display:inline;" topmargin="0" leftmargin="0" marginheight="0" marginwidth="0">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<TR>
		<TD valign=bottom>
			<?
				getModuleByID($int_module_selected)->buildSubMenu();
			?>
		</TD>
	</TR>
</table>

</body>
</html>