<?
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	$str_message = '';
	
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}

	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];
	
	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
			$int_id = $_POST['id'];
			$str_date = set_mysql_date($_POST['date_created'],'-');
			$str_reference = $_POST['transfer_reference'];
			
			$str_query = "
				UPDATE ".Monthalize('stock_transfer')."
				SET date_created = '$str_date',
					transfer_reference = '$str_reference'
				WHERE transfer_id = $int_id
			";
			$qry = new Query($str_query);

			if ($qry->b_error == false) {
				echo "<script language='javascript'>";
				echo "if (window.opener)\n";
				echo "window.opener.document.location=window.opener.document.location.href;\n";
				echo "window.close();\n";
				echo "</script>";
			}
			else
				$str_message = "Error saving";
		}
	}
	
	$str_date = '';
	$str_reference = '';
	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM ".Monthalize('stock_transfer')."
			WHERE transfer_id = $int_id
		";
		$qry = new Query($str_query);

		if ($qry->RowCount() > 0) {
			$str_date = set_formatted_date($qry->FieldByName('date_created'), '-');
			$str_reference = $qry->FieldByName('transfer_reference');
		}
	}
?>

<html>
<head>
	<link href="../../include/styles.css" rel="stylesheet" type="text/css">
	<script language="JavaScript" src="../../include/calendar1.js"></script>
	<script language='javascript'>

		var can_save = true;

		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function isDate(str){
			if (isEmpty(str)) return false;
			var re = /^(\d{1,2})[\s\.\/-](\d{1,2})[\s\.\/-](\d{4})$/
			if (!re.test(str)) return false;
			var result = str.match(re);
			var d = parseInt(result[1],10);
			var m = parseInt(result[2],10);
			var y = parseInt(result[3],10);
			if(m < 1 || m > 12 || y < 1900 || y > 2100) return false;
			if(m == 2){
				var days = ((y % 4) == 0) ? 29 : 28;
			}else if(m == 4 || m == 6 || m == 9 || m == 11){
				var days = 30;
			}else{
				var days = 31;
			}
			return (d >= 1 && d <= days);
		}
		
		function save() {
			var oTextDate = document.transfer_edit.date_created;
			
			if (!isDate(oTextDate.value)) {
				alert ('Invalid date.');
				can_save = false;
				oTextDate.focus();
				return false;
			}
			
			if (can_save == true) {
				document.transfer_edit.submit();
			}
			else
				alert('Could not save');
		}

		function closeWindow() {
			if (window.opener)
				window.opener.document.location=window.opener.document.location.href;
			window.close();
		}
	</script>
</head>

<body>
<form name="transfer_edit" method="POST">
	<input type="hidden" name="id" value="<?echo $int_id?>">
	
	<?
	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
	<? } ?>
	
	<table class='edit'>
		<tr>
			<TD>
				<table>
					<tr>
						<td align='right' class="normaltext_bold">Date:</td>
						<td>
							<input type="text" name="date_created" value="<?echo $str_date;?>" class="input_200">
<!-- 							<a href="javascript:cal1.popup();"><img src="../../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a> -->
						</td>
					</tr>
					<tr>
						<td align='right' class="normaltext_bold">Reference:</td>
						<td>
							<input type="text" name="transfer_reference" value="<?echo $str_reference?>" class="input_200">
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td align="right">
							<input type="hidden" name="action" value="save">
							<? if ($int_access_level > ACCESS_READ) { ?>
								<input type="button" class="mainmenu_button" name="Save" value="Save" onclick="save()">
							<? } else { ?>
								&nbsp;
							<? } ?>
						</td>
						<td>
							<input type="button" class="mainmenu_button" name="Close" value="Close" onclick="closeWindow()">
						</td>
					</tr>
					<tr>
						<TD colspan="2" class="normaltext">
						<br><br>
						<font color="red">Important:</font> Corresponding entries in other storerooms are NOT updated
						</TD>
					</tr>
				</table>
			</TD>
		</tr>
	</table>

</form>
	<script language="JavaScript">
		var oTextDate = document.transfer_edit.date_created;
		var cal1 = new calendar1(oTextDate);
		cal1.year_scroll = true;
		cal1.time_comp = false;
	</script>
</body>
</html>