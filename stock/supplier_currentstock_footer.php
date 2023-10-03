<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    
    $flt_total_stock = 0;
    if (IsSet($_GET['total_stock']))
        $flt_total_stock = $_GET['total_stock'];

    $flt_total_adjusted = 0;
    if (IsSet($_GET['total_adjusted']))
        $flt_total_adjusted = $_GET['total_adjusted'];
    
    $flt_buying_value = 0;
    if (IsSet($_GET['buying_value']))
        $flt_buying_value = $_GET['buying_value'];

    $flt_selling_value = 0;
    if (IsSet($_GET['selling_value']))
        $flt_selling_value = $_GET['selling_value'];

?>

<html>
    <head>
	<link rel="stylesheet" type="text/css" href="../include/sweetTitles.css" />
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script type="text/javascript" src="../include/addEvent.js"></script>
	<script type="text/javascript" src="../include/sweetTitles.js"></script>
    </head>
<body id='body_bgcolor' leftmargin='200px'>
	<font class='normaltext'>
	
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr><td align='left'>
            
            <table border='0' cellpadding='2' cellspacing='0'>
		<tr>
		    <td class='normaltext_bold' align='right'>Total Stock:</td>
		    <td class='normaltext'><?echo number_format($flt_total_stock,2,'.',',')?></td>
		</tr>
		<tr>
		    <td class='normaltext_bold' align='right'>Total Adjusted:</td>
		    <td class='normaltext'><?echo $flt_total_adjusted?></td>
		</tr>
		<tr>
		    <td class='normaltext_bold' align='right'>Buying Value:</td>
		    <td class='normaltext'><?echo number_format($flt_buying_value,2,'.',',')?></td>
		</tr>
		<tr>
		    <td class='normaltext_bold' align='right'>Selling Value:</td>
		    <td class='normaltext'><?echo number_format($flt_selling_value,2,'.',',')?></td>
		</tr>
            </table>
                
	</td></tr>
	</table>
	</font>
</body>
</html>