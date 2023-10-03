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
				// check for duplicate language
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_language
					WHERE (language = '".addslashes($_POST['language'])."')
						AND (language_id <> $int_id)
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate language found';
				}
				else {
					$str_query = "
						UPDATE stock_language
						SET
							language = '".addslashes($_POST['language'])."',
							language_group = '".addslashes($_POST['language_group'])."'
						WHERE (language_id = $int_id)
					";
					$qry = new Query($str_query);
					
					if ($qry->b_error == true)
						$str_message = 'Error updating language information';
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
				// check for duplicate language
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_language
					WHERE (language = '".addslashes($_POST['language'])."')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate language found';
				}
				else {
					$str_query = "
						INSERT INTO stock_language
						(
							language,
							language_group
						)
						VALUES (
							'".addslashes($_POST['language'])."',
							'".addslashes($_POST['language_group'])."'
						)
					";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = 'Error inserting new language';
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

	$str_language = '';
	$str_language_group = '';

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_language
			WHERE language_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_language = $qry->FieldByName('language');
			$str_language_group = $qry->FieldByName('language_group');
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
			var oTextLanguage = document.language_edit.language;
			var can_save = true;
			
			if (isEmpty(oTextLanguage.value)) {
				can_save = false;
				alert('Language cannot be blank');
				oTextLanguage.focus();
				return false;
			}
			
			if (can_save)
				document.language_edit.submit();
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin=7 topmargin=7 marginwidth=15 marginheight=15>
<form name='language_edit' method='POST'>
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
		<td width='160px' align="right" class='normaltext_bold'>language:</td>
		<td>
			<input type='text' name='language' value='<?echo $str_language?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td width='160px' align="right" class='normaltext_bold'>group:</td>
		<td>
			<input type='text' name='language_group' value='<?echo $str_language_group?>' class='input_100'>
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