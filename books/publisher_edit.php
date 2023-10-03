<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/functions.inc.php");
	
	$str_message = '';

	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			if (IsSet($_POST['id'])) {
				$int_id = $_POST['id'];
				
				//==================================
				// check for duplicate publisher
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_publisher
					WHERE (publisher = '".addslashes($_POST['publisher'])."')
						AND (publisher_id <> $int_id)
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate publisher name found';
				}
				else {
					$str_query = "
						UPDATE stock_publisher
						SET
							publisher = '".addslashes($_POST['publisher'])."'
						WHERE (publisher_id = $int_id)
					";
					$qry = new Query($str_query);
					
					if ($qry->b_error == true)
						$str_message = 'Error updating publisher information';
					else {
						echo "<script language='javascript'>\n;";
						echo "if (top.window.opener)\n";
						echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
						echo "top.window.close();\n";
						echo "</script>";
					}
				}
			}
			else {
				//==================================
				// check for duplicate publisher
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_publisher
					WHERE (publisher = '".addslashes($_POST['publisher'])."')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate publisher found';
				}
				else {
					$str_query = "
						INSERT INTO stock_publisher
						(
							publisher
						)
						VALUES (
							'".addslashes($_POST['publisher'])."'
						)
					";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = 'Error inserting new publisher';
						echo $str_query;
					}
					else {
						echo "<script language='javascript'>\n;";
						echo "if (top.window.opener)\n";
						echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
						echo "top.window.close();\n";
						echo "</script>";
					}
				}
			}
		}
	}

	$str_publisher = '';

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_publisher
			WHERE publisher_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_publisher = $qry->FieldByName('publisher');
		}
	}
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function trim(s) {
			return s.replace(/^\s+|\s+$/g, "");
		}
		
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function isFloat(s) {
			var n = trim(s);
			return n.length>0 && !(/[^0-9.]/).test(n) && (/\.\d/).test(n);
		}
		
		function saveData() {
			var oTextPublisher = document.publisher_edit.publisher;
			var can_save = true;
			
			if (isEmpty(oTextPublisher.value)) {
				can_save = false;
				alert('Publisher cannot be blank');
				oTextPublisher.focus();
				return false;
			}
			
			if (can_save)
				document.publisher_edit.submit();
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin=7 topmargin=7 marginwidth=15 marginheight=15>
<form name='publisher_edit' method='POST'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../images/blank.gif");

	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?
	}
//===================
?>

<table width='100%' cellpadding="5" cellspacing="0">
	<tr>
		<td width='160px' align="right" class='normaltext_bold'>publisher:</td>
		<td>
			<input type='text' name='publisher' value='<?echo $str_publisher?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align='right'>
			<input type='hidden' name='action' value='save'>
			<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
		</td>
		<td>
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="CloseWindow()">
		</td>
	</tr>
</table>


<?
//=================
// bounding box end
//-----------------
    boundingBoxEnd("400", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//===================
?>

</form>
</body>
</html>