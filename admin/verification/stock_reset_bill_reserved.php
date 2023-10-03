<?
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	include("../../include/db.inc.php");
	
	//======================================
	// reset the ordered and reserved fields
	//--------------------------------------
	$str_reset = "
		UPDATE ".Monthalize('stock_storeroom_batch')." ssb
		SET bill_reserved = 0
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
	
	$qry = new Query($str_reset);
?>
<html>
<head>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
    <script language='javascript'>
        function goBack() {
            document.location = '../index_verification_tools.php';
        }
    </script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
    boundingBoxStart("800", "../../images/blank.gif");
?>
    <br>
    <div class='normaltext'>Successfully reset the 'Bill Reserved' quantity.</div>
    <br>
    <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
    <br><br>
<?
    boundingBoxEnd("800", "../../images/blank.gif");
?>

</body>
</html>