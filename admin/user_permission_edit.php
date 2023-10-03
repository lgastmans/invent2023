<?
require_once("../include/const.inc.php");
require_once("session.inc.php");
require_once("db.inc.php");

//***
//  check permissions
//***
$bool_can_modify_record = false;
if ($_SESSION["int_user_type"] > 1) {
	$bool_can_modify_record = true;
}

$str_message = '';

//***
// user_id set		=> new
// permission_id set	=> edit
//***
$int_user_id = 0;
if (IsSet($_GET["user_id"]))
	$int_user_id = $_GET['user_id'];
	
$int_permission_id = 0;
if (IsSet($_GET['permission_id']))
	$int_permission_id = $_GET['permission_id'];

if (IsSet($_POST['action'])) {
	if ($_POST['action'] == 'save') {
		//***
		// Edit
		//***
		if (IsSet($_POST['permission_id'])) {
			$str_query = "
				UPDATE user_permissions
				SET storeroom_id = ".$_POST['storeroom_id'].",
					access_level = ".$_POST['access_level']."
				WHERE permission_id = ".$_POST['permission_id']."
			";
			$qry = new Query($str_query);
			
			if ($qry->b_error == true) {
				$str_message = 'Error updating permissions';
				echo $str_query;
				die();
			}
			else {
				echo "<script language='javascript'>\n;";
				echo "if (top.window.opener)\n";
				echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
				echo "top.window.close();\n";
				echo "</script>";
			}
		}
		
		//***
		// New
		//***
		if (IsSet($_POST['user_id'])) {
			$str_query = "
				INSERT INTO user_permissions
				(
					user_id,
					module_id,
					storeroom_id,
					access_level
				)
				VALUES (
					".$_POST['user_id'].",
					".$_POST['module_id'].",
					".$_POST['storeroom_id'].",
					".$_POST['access_level']."
				)
			";
			$qry = new Query($str_query);
			
			if ($qry->b_error == true) {
				$str_message = 'Error updating permissions';
				echo $str_query;
				die();
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


$int_module_id = 0;
$int_storeroom_id = 0;
$int_access_level = 0;

if ($int_permission_id > 0) {
	$str_query = "
		SELECT *
		FROM user_permissions
		WHERE permission_id = $int_permission_id
	";
	$qry = new Query($str_query);
	
	$int_module_id = $qry->FieldByName('module_id');
	$int_storeroom_id = $qry->FieldByName('storeroom_id');
	$int_access_level = $qry->FieldByName('access_level');
}


//***
// list of modules
//***
if ($int_user_id > 0) {
	/*
		new
	*/
	$qry_modules = new Query("
		SELECT *
		FROM module
		WHERE module_id NOT IN (
			SELECT module_id
			FROM user_permissions
			WHERE (user_id = $int_user_id)
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		)
		AND (active = 'Y')
		ORDER BY module_id
	");
}
else {
	/*
		edit
	*/
	$qry_modules = new Query("
		SELECT *
		FROM module
		WHERE module_id IN (
			SELECT module_id
			FROM user_permissions
			WHERE permission_id = $int_permission_id
		)
		AND (active = 'Y')
		ORDER BY module_id
	");
}

//***
// list of storerooms
//***
$qry_storerooms = new Query("
	SELECT *
	FROM stock_storeroom
	ORDER BY description
");
?>
<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		
		function createRequest() {
			try {
				var requester = new XMLHttpRequest();
			}
			catch (error) {
				try {
					requester = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch (error) {
					return false;
				}
			}
			return requester;
		}
		
		var requester = createRequest();
		var intUserID = <?echo $int_user_id;?>;
		var intPermissionID = <?echo $int_permission_id;?>;
		
		function stateHandler() {
			if (requester.readyState == 4) {
				if (requester.status == 200)  {
					str_retval = requester.responseText;
					
					arr_retval = str_retval.split('|');
					
					var oSelectModules = document.user_permission_edit.module_id;
					oSelectModules.length = 0;
					
					if (str_retval != '') {
						for (i=0; i<arr_retval.length; i++) {
							arr_temp = arr_retval[i].split('_');
							oSelectModules.options[i] = new Option(arr_temp[1], arr_temp[0]);
						}
					}
				}
				else {
					alert("Failed to load submenu.");
				}
				requester = null;
				requester = createRequest();
			}
		}
		
		function setModules() {
			var oSelectStoreroom = document.user_permission_edit.storeroom_id;
			var strURL = "get_modules.php?live=1"+
				"&user_id="+intUserID+
				"&permission_id="+intPermissionID+
				"&storeroom_id="+oSelectStoreroom.value;
			
			requester.onreadystatechange = stateHandler;
			requester.open("GET", strURL);
			requester.send(null);
		}
		
		function saveData(intModules) {
			var can_save = true;
			var oSelectModules = document.user_permission_edit.module_id;
			
			if (oSelectModules)
				if (oSelectModules.length == 0) {
					can_save = false;
					alert('no modules available');
				}
			
			if (can_save)
				document.user_permission_edit.submit();
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>

<body id='body_bgcolor' marginwidth=5 marginheight=5>

<form name='user_permission_edit' method='POST'>
<?
	if ($int_user_id > 0)
		echo "<input type='hidden' name='user_id' value='".$int_user_id."'>";
	if ($int_permission_id > 0)
		echo "<input type='hidden' name='permission_id' value='".$int_permission_id."'>";
		
	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?	} ?>

<table class="edit" width='100%' height='90%' border='0' >
	<tr>
		<td align='center' valign='center'>
		
			<table width='100%' cellpadding="5" cellspacing="0">
				<tr>
					<td align="right" class='normaltext_bold'>Module:</td>
					<td>
						<?
							if ($int_permission_id == 0) { ?>
								<select name='module_id' class='select_200'>
								<?
								if ($qry_modules->RowCount() > 0) {
									for ($i=0; $i<$qry_modules->RowCount(); $i++) {
										echo "<option value=".$qry_modules->FieldByName('module_id').">".$qry_modules->FieldByName('module_name');
										$qry_modules->Next();
									}
								}
								?>
								</select>
						<?	}
							else {
								echo $qry_modules->FieldByName('module_name');
								echo "<input type='hidden' name='module_id' value='".$qry_modules->FieldByName('module_id')."'>";
							}
						?>
					</td>
				</tr>
				<tr>
					<td align="right" class='normaltext_bold'>Storeroom:</td>
					<td>
						<?
							if ($int_permission_id == 0) { ?>
								<select name='storeroom_id' class='select_200' onchange="javascript:setModules()">
								<?
									for ($i=0; $i<$qry_storerooms->RowCount(); $i++) {
										if ($qry_storerooms->FieldByName('storeroom_id') == $int_storeroom_id)
											echo "<option value=".$qry_storerooms->FieldByName('storeroom_id')." 'selected'>".$qry_storerooms->FieldByName('description');
										else
											echo "<option value=".$qry_storerooms->FieldByName('storeroom_id').">".$qry_storerooms->FieldByName('description');
										$qry_storerooms->Next();
									}
								?>
								</select>
						<?	}
							else {
								echo $qry_storerooms->FieldByName('description');
								echo "<input type='hidden' name='storeroom_id' value='".$qry_storerooms->FieldByName('storeroom_id')."'>";
							}
						?>
					</td>
				</tr>
				<tr>
					<td align="right" class='normaltext_bold'>Access level:</td>
					<td>
						<select name='access_level' class='select_200'>
							<option value='<? echo ACCESS_NONE ?>' <?if ($int_access_level == ACCESS_NONE) echo 'selected';?> >None</option>
							<option value='<? echo ACCESS_READ ?>' <?if ($int_access_level == ACCESS_READ) echo 'selected';?> >Read</option>
							<option value='<? echo ACCESS_WRITE ?>' <?if ($int_access_level == ACCESS_WRITE) echo 'selected';?> >Read/Write</option>
							<option value='<? echo ACCESS_ADMIN ?>' <?if ($int_access_level == ACCESS_ADMIN) echo 'selected';?> >Admin</option>
						</select>
					</td>
				</tr>
			</table>

			<table cellpadding="3" cellspacing="0" border='0'>
				<tr>
					<td>
						<input type='hidden' name='action' value='save'>
						<input type="button" class="settings_button" name="button_save" value="Save" <? if (!$bool_can_modify_record) echo 'disabled';?> onclick="javascript:saveData(<? echo $qry_modules->RowCount();?>)">
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

</form>
</body>
</html>
