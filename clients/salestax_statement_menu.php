<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    $_SESSION['int_clients_menu_selected'] = 2;

?>
<html>
    <head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language='javascript'>
	    function print_statement() {
		myWin = window.open("print_salestax_statement.php", 'printwin', 'width=800,height=500,resizable=yes,menubar=yes');
		myWin.focus();
	    }
	</script>
    </head>

<body id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>
<form name="current_stock_menu" method="GET">

    <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
            <td><font style='font-family:Verdana;font-size:14px;font-weight:bold;'>Sales tax statement for <?echo getMonthName($_SESSION['int_month_loaded'])." - ".$_SESSION['int_year_loaded'];?></font></td>
        </tr>
	<tr>
	    <td><a href='javascript:print_statement()'><img border='0' src='../images/printer.png'></a></td>
	</tr>
    </table>
    
</form>

<script language='javascript'>
    parent.frames['content'].document.location = 'salestax_statement_content.php';
</script>

</body>
</html>
