<? 
	error_reporting(E_ERROR);
	
	require_once("../include/config.inc.php");
	require_once("../include/db.inc.php");
	
	$str_grid_name = "";
	if (IsSet($_REQUEST['grid_name']))
		$str_grid_name = $_REQUEST['grid_name'];
	
	$int_user_id = 0;
	if (IsSet($_REQUEST['user_id']))
		$int_user_id = $_REQUEST['user_id'];
	
	$str_view_name = "default";
	if (IsSet($_REQUEST['viewname']))
		$str_view_name = $_REQUEST['viewname'];

//	print_r($_REQUEST);
	
/*
	create a new view
*/
if (IsSet($_REQUEST['newviewname'])) {
	if (!empty($_REQUEST['newviewname'])) {
		/*
			get the list of fields from the "default" view
		*/
		$qry_default = new Query("
			SELECT *
			FROM grid
			WHERE view_name = 'default'
				AND grid_name = '$str_grid_name'
				AND user_id = $int_user_id
		");
		
		/*
			insert these fields in the new view
		*/
		$str_view_name = $_REQUEST['newviewname'];
		
		$qry_insert = new Query("SELECT * FROM grid LIMIT 1");
		for ($i=0;$i<$qry_default->RowCount();$i++) {
			$str_insert = "
				INSERT INTO grid
				(
					user_id,
					grid_name,
					column_name,
					field_name,
					field_type,
					width,
					can_filter,
					callback,
					visible,
					view_name,
					column_order
				)
				VALUES (
					$int_user_id,
					'$str_grid_name',
					'".$qry_default->FieldByName('column_name')."',
					'".$qry_default->FieldByName('field_name')."',
					'".$qry_default->FieldByName('field_type')."',
					".$qry_default->FieldByName('width').",
					'".$qry_default->FieldByName('can_filter')."',
					'".$qry_default->FieldByName('callback')."',
					'".$qry_default->FieldByName('visible')."',
					'$str_view_name',
					'".$qry_default->FieldByName('column_order')."'
				)
			";
			$qry_insert->Query($str_insert);
			
			$qry_default->Next();
		}
	}
}

/*
	save settings
*/
if ($_REQUEST['action']=='Save') {
	
	$qry_update = new Query("
		SELECT * 
		FROM grid 
		LIMIT 1
	");
	
	foreach ($_POST as $key=>$value) {
		$arr_values = explode("|", $key);
		
		if ($arr_values[0] == 'field_caption')
			$str_update = "
				UPDATE grid
				SET column_name = '$value'
				WHERE grid_id = ".$arr_values[1];
		else if ($arr_values[0] == 'field_order')
			$str_update = "
				UPDATE grid
				SET column_order = $value
				WHERE grid_id = ".$arr_values[1];
		else if ($arr_values[0] == 'field_width')
			$str_update = "
				UPDATE grid
				SET width = $value
				WHERE grid_id = ".$arr_values[1];
		else if ($arr_values[0] == 'field_visible') {
			$str_update = "
				UPDATE grid
				SET visible = $value
				WHERE grid_id = ".$arr_values[1];
		}
		
		$qry_update->Query($str_update);
	}
}

if (IsSet($_REQUEST['delview'])) {
	if ($_REQUEST['delview']=='yes') {
		if ($str_view_name != 'default') {
			$str_query = "
				DELETE FROM grid
				WHERE view_name = '$str_view_name'
					AND grid_name = '$str_grid_name'
					AND user_id = $int_user_id
			";
			$qry = new Query($str_query);
			
			$str_view_name = 'default';
		}
	}
}

?>

<html>
	<head>
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
		
			function setVisible(intGridID, strField) {
				strChecked = 'N';
				if (strField.checked)
					strChecked = 'Y';
				
				requester.onreadystatechange = stateHandler;
				requester.open("GET", "set_grid_visible.php?grid_id="+intGridID+"&visible="+strChecked);
				requester.send(null);
			}
			
			function stateHandler() {
				if (requester.readyState == 4) {
					if (requester.status == 200)  {
						str_retval = requester.responseText;
					}
					else {
						alert("Failed to change visibility.");
					}
					requester = null;
					requester = createRequest();
				}
			}
			
			function getName() {
				var newname= prompt('Please enter a name for the view:', ' ');
				if (newname!="") {
					viewform.newviewname.value=newname;
					viewform.submit();
				}
			}
			
			function delView() {
				var oView = document.viewform.viewname;
				if (oView.value != 'default') {
					if (confirm('Are you sure?')) {
						viewform.delview.value='yes';
						viewform.submit();
					}
				}
				else
					alert('Cannot delete the default view');
			}
		</script>
	</head>
<body id="body_bgcolor" leftmargin="20" topmargin="5">

<form name='viewform' method='post' action='grid_customize.php'>

	<input type='hidden' name='grid_name' value='<? echo $str_grid_name;?>'>
	<input type="hidden" name="user_id" value="<?echo $int_user_id;?>">
	<input type='hidden' name='newviewname' value=''>
	<input type="hidden" name="delview" value="no">
	
	<font class='normaltext_bold'>Customize Grid</font>
	<br>
	<hr>
	<font class='normaltext'>View:</font>
	<select name='viewname' class='select_100' onchange="javascript:viewform.submit()">
		<? 
			$str_qry = "
				SELECT DISTINCT view_name
				FROM grid
				WHERE grid_name = '$str_grid_name'
					AND user_id = $int_user_id
					AND view_name <> 'all'
			";
			$qry_view = new Query($str_qry);
			
			for ($i=0;$i < $qry_view->RowCount();$i++) {
				echo "<option value='".$qry_view->FieldByName('view_name')."' ";
				if ($qry_view->FieldByName('view_name')==$_REQUEST['viewname']) echo "selected";
					echo ">".$qry_view->FieldByName('view_name')."</option>\n";
				$qry_view->Next();
			}
		?>
	</select>
	&nbsp;
	<input type='button' name='mybutton' onclick='getName();' value='New' class='settings_button'>
	<input type="button" name="delbutton" value="Delete" onclick="javascript:delView()" class="settings_button">
	<hr>
	<br>
	
	<table border="1" cellpadding="2" cellspacing="0">
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td>Field</td>
			<td>Caption</td>
			<td>Order</td>
			<td>Width</td>
			<td>Visible</td>
		</tr>
		<?
		$str_qry="
			SELECT *
			FROM grid
			WHERE view_name = '$str_view_name'
				AND grid_name = '$str_grid_name'
				AND user_id = $int_user_id
			ORDER BY column_order
		";
		$qry_fields = new Query($str_qry);
		
		for ($i=0;$i<$qry_fields->RowCount();$i++) {
			$int_grid_id = $qry_fields->FieldByName('grid_id');
			
			echo "<tr>";
			echo "<td class='normaltext'>".$qry_fields->FieldByName('field_name')."</td>";
			echo "<td><input class='input_200' type='text' value='".$qry_fields->FieldByName('column_name')."' name='field_caption|".$qry_fields->FieldByName('grid_id')."'></td>";
			echo "<td><input class='input_50' type='text' value='".$qry_fields->FieldByName('column_order')."' name='field_order|".$qry_fields->FieldByName('grid_id')."' size='7'></td>";
			echo "<td><input class='input_50' type='text' value='".$qry_fields->FieldByName('width')."' name='field_width|".$qry_fields->FieldByName('grid_id')."' size='7'></td>";
			if ($qry_fields->FieldByName('visible') == 'Y')
				echo "<td><input type='checkbox' name='visible' checked onclick='javascript:setVisible($int_grid_id,this)'></td>";
			else
				echo "<td><input type='checkbox' name='visible' onclick='javascript:setVisible($int_grid_id,this)'></td>";
			echo "</tr>\n";
			$qry_fields->Next();
		}
		?>
		<tr>
			<td colspan='5'>
				<input type='submit' value='Save' name='action' class='settings_button'>
			</td>
		</tr>
	</table>
	<br>
</form>
</body>
</html>