<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	
	/*
		check permissions
	*/
	$bool_can_modify_record = false;
	if ($_SESSION["int_user_type"]>1) {
		$bool_can_modify_record = true;
	}
	
	$str_message = '';
	
	$int_user_id = 0;
	if (IsSet($_GET["id"]))
		$int_user_id = $_GET['id'];

	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			/*
				verify current password
			*/
			$str_current_password = base64_encode($_POST["password_current"]);
			
			$qry = new Query("
				SELECT *
				FROM user
				WHERE password='".$str_current_password."'
					AND user_id = ".$int_user_id."
				");
			
			if ($qry->RowCount() > 0) {
				/*
					confirm new password
				*/
				$str_new_password = $_POST['password_new'];
				$str_confirm_password = $_POST['password_confirm'];
				
				if ($str_new_password === $str_confirm_password) {
					$str_new_password = base64_encode($str_new_password);
					
					$str_query = "
						UPDATE user
						SET password = '$str_new_password'
						WHERE user_id = ".$int_user_id;
					
					$qry->Query($str_query);
					if ($qry->b_error == false) {
						echo "<script language='javascript'>";
						echo "top.window.close();";
						echo "</script>";
					}
					else
						$str_message = "An error occurred changing password\\nPassword not changed";
				}
				else
					$str_message = "New password did not pass confirmation";
			}
			else {
				$str_message = "Current password does not match";
			}
		}
	}
	
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		
		function saveData() {
			var can_save = true;
			
			if (can_save)
				document.user_password.submit();
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body topmargin="5" leftmargin="5">
<form name='user_password' method='POST'>
	<?
		if ($int_user_id > 0)
			echo "<input type='hidden' name='id' value='".$int_user_id."'>";
			
		if ($str_message != '')  { ?>
			<script language='javascript'>
				alert('<?echo $str_message?>');
			</script>
	<?	} ?>

	<table class="edit">
		<tr>
			<td align="center" valign="middle">
				<table width='100%' cellpadding="5" cellspacing="0">
					<tr>
						<td width='160px' align="right" class='normaltext_bold'>Current password:</td>
						<td>
							<input type='password' name='password_current' value='' class='input_200'>
						</td>
					</tr>
					<tr>
						<td width='160px' align="right" class='normaltext_bold'>New password:</td>
						<td>
							<input type='password' name='password_new' value='' class='input_200'>
						</td>
					</tr>
					<tr>
						<td width='160px' align="right" class='normaltext_bold'>Confirm password:</td>
						<td>
							<input type='password' name='password_confirm' value='' class='input_200'>
						</td>
					</tr>
				</table>

				<table cellpadding="3" cellspacing="0" border='0'>
					<tr>
						<td>
							<input type='hidden' name='action' value='save'>
							<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
						</td>
						<td>
							<input type="button" name="button_close" value="Close" class="settings_button" onclick="closeWindow()">
						</td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	
	<script language="javascript">
		var oTextCurrent = document.user_password.password_current;
		oTextCurrent.focus();
	</script>
</form>
</body>
</html>