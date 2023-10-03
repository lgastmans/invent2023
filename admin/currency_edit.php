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
				// check for duplicate currency name
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_currency
					WHERE (currency_name = '".addslashes($_POST['currency'])."')
						AND (currency_id <> $int_id)
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate currency name found';
				}
				else {
					$str_query = "
						UPDATE stock_currency
						SET
							currency_name = '".addslashes($_POST['currency'])."',
							currency_rate = ".$_POST['rate']."
						WHERE (currency_id = $int_id)
					";
					$qry = new Query($str_query);
					
					if ($qry->b_error == true)
						$str_message = 'Error updating currency information';
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
				// check for duplicate currency
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_currency
					WHERE (currency_name = '".addslashes($_POST['currency'])."')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate currency found';
				}
				else {
					$str_query = "
						INSERT INTO stock_currency
						(
							currency_name,
							currency_rate
						)
						VALUES (
							'".addslashes($_POST['currency'])."',
							".$_POST['rate']."
						)
					";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = 'Error inserting new currency';
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

	$str_currency = '';
	$flt_rate = 0.0;

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_currency
			WHERE currency_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_currency = $qry->FieldByName('currency_name');
			$flt_rate = number_format($qry->FieldByName('currency_rate'),2,'.',',');
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
			var oTextCurrency = document.currency_edit.currency;
			var oTextRate = document.currency_edit.rate;
			var can_save = true;
			
			if (isEmpty(oTextCurrency.value)) {
				can_save = false;
				alert('Currency cannot be blank');
				oTextCurrency.focus();
				return false;
			}
			if ((!isFloat(oTextRate.value)) || (isEmpty(oTextRate.value))) {
				can_save = false;
				alert('Invalid rate value');
				oTextRate.focus();
				return false;
			}
			
			if (can_save)
				document.currency_edit.submit();
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin=7 topmargin=7 marginwidth=15 marginheight=15>
<form name='currency_edit' method='POST'>
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
		<td width='160px' align="right" class='normaltext_bold'>Currency:</td>
		<td>
			<input type='text' name='currency' value='<?echo $str_currency?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Rate:</td>
		<td>
			<input type='text' name='rate' value='<?echo $flt_rate?>' class='input_100'>
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