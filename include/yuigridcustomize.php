<? 
/*
	the parent page is assumed to have const.inc.php and session.inc.php
*/
require_once('../include/const.inc.php');
require_once('db_params.php');

error_reporting(E_ERROR);

if (empty($_REQUEST['viewname'])) {
	$_REQUEST['viewname']='default';
}

if ($_REQUEST['action'] == 'Save') {
	foreach ($_POST as $key=>$value) {
		$arr_values = explode("|", $key);
		
		if ($key == 'field_sort') {
			$str_update = "UPDATE yui_grid
				SET defaultsort = 'N'
				WHERE view_name='".$_REQUEST['viewname']."'
					AND gridname='".$_REQUEST['gridname']."'
					AND user_id=".$_SESSION['int_user_id'];
			$qry_update =& $conn->Query($str_update);
			
//			print_r($_POST['field_dir']);
			
			$str_update = "
				UPDATE yui_grid
				SET defaultsort = 'Y',
					defaultdir = '".$_POST['field_dir']."'
				WHERE id = ".$value;
			
		}
		else {
			if ($arr_values[0] == 'field_caption')
				$str_update = "
					UPDATE yui_grid
					SET columnname = '$value'
					WHERE id = ".$arr_values[1];
			
			else if ($arr_values[0] == 'field_order')
				$str_update = "
					UPDATE yui_grid
					SET position = $value
					WHERE id = ".$arr_values[1];
			
			else if ($arr_values[0] == 'field_width')
				$str_update = "
					UPDATE yui_grid
					SET columnwidth = $value
					WHERE id = ".$arr_values[1];
		}
		
		$qry_update =& $conn->Query($str_update);
	}
	echo "<script language='javascript'>";
	echo "if (top.window.opener)\n";
	echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
	echo "top.window.close();\n";
	echo "</script>";
}
else if ($_REQUEST['action'] == 'Reset') {
	$str_query = "
		DELETE FROM yui_grid
		WHERE view_name='".$_REQUEST['viewname']."'
			AND gridname='". $_REQUEST['gridname']."'
			AND user_id=".$_SESSION['int_user_id']."
	";
	$qry =& $conn->query($str_query);
	
	
	echo "<script language='javascript'>";
	echo "if (top.window.opener)\n";
	echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
	echo "top.window.close();\n";
	echo "</script>";
	
}

?>
<html>
<head>
	<!-- Dependency -->
	<script src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	
	<!-- Used for Custom Events and event listener bindings -->
	<script src="../yui2.7.0/build/event/event-min.js"></script>
	
	<!-- Source file -->
	<script src="../yui2.7.0/build/connection/connection-min.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	
	<script language="javascript">
		var handleSuccess = function(o){
			//alert(o.responseText);
		}
		
		var handleFailure = function(o){
			//alert(o.responseText);
		}
		
		var callback = {
			success:handleSuccess,
			failure:handleFailure
		};
		
		function toggleVisibility(oID, oState) {
			strVisible = 'N';
			if (oState.checked==true)
				strVisible = 'Y';
			var sUrl = "yuigrid_toggle_visible.php?id="+oID+"&visible="+strVisible;
			//alert(sUrl);
			var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
		}
		
		function resetGrid() {
			var oHidden = document.getElementById('action');
			
			if (confirm("Are you sure?")) {
				oHidden.value = 'Reset';
				document.forms[0].submit();
			}
		}
	</script>
</head>

<body id='body_bgcolor' leftmargin=20 topmargin=5>
	<form name='viewform' method='post' action='yuigridcustomize.php'>
	
	<input type='hidden' name='gridname' value='<? echo $_REQUEST["gridname"]; ?>'>
	<input type='hidden' name='action' id='action' value='Save'>
	<font class='normaltext_bold'>Customize Grid </font><br><br>
	<table border=1 cellpadding=5 cellspacing=0>
		<?php
		/*
			get the sort direction of the default sort field
		*/
		$str_qry = "
			SELECT *
			FROM yui_grid
			WHERE view_name='".$_REQUEST['viewname']."'
				AND gridname='". $_REQUEST['gridname']."'
				AND user_id=".$_SESSION['int_user_id']."
				AND defaultsort='Y'";
		$qry =& $conn->query($str_qry);
		$dir = "A";
		if (mysqli_num_rows($qry) > 0) {
			$obj = mysqli_fetch_object($qry);
			$dir = $obj->defaultdir;
		}
		
		?>
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td>Field</td>
			<td>Caption</td>
			<td>Order</td>
			<td>Width</td>
			<td align='center'>Default<br>Sort<br>
				<select name='field_dir'>
					<option value='A' <?php if ($dir=='A') echo "selected";?>>ASC</option>
					<option value='D' <?php if ($dir=='D') echo "selected";?>>DESC</option>
				</select>
			</td>
			<td>Visible</td>
		</tr>
		<?
		$str_qry = "
			SELECT *
			FROM yui_grid
			WHERE view_name='".$_REQUEST['viewname']."'
				AND gridname='". $_REQUEST['gridname']."'
				AND user_id=".$_SESSION['int_user_id']."
				AND is_primary_key='N'
			ORDER BY position";
		$qry_fields =& $conn->Query($str_qry);
		
		while ($obj = mysqli_fetch_object($qry_fields)) {
			echo "<tr>";
			echo "<td class='normaltext'>".$obj->fieldname."</td>";
			echo "<td><input class='input_200' type='text' value='".$obj->columnname."' name='field_caption|".$obj->id."'></td>";
			echo "<td><input class='input_50' type='text' value='".$obj->position."' name='field_order|".$obj->id."' size='7'></td>";
			echo "<td><input class='input_50' type='text' value='".$obj->columnwidth."' name='field_width|".$obj->id."' size='7'></td>";
			if ($obj->defaultsort == 'Y')
				echo "<td align='center'><input type='radio' value='".$obj->id."' name='field_sort' checked>";
			else
				echo "<td align='center'><input type='radio' value='".$obj->id."' name='field_sort'>";
			echo "</td>";
			if ($obj->visible == 'Y')
				echo "<td align='center'><input type='checkbox' name='field_visible|".$obj->id."' checked onclick='toggleVisibility(".$obj->id.",this)'></td>";
			else
				echo "<td align='center'><input type='checkbox' name='field_visible|".$obj->id."' onclick='toggleVisibility(".$obj->id.",this)'></td>";
			echo "</tr>\n";
		}
		?>
		<tr>
			<td colspan='6'>
				<input type='submit' value='Save' name='action' class='settings_button'>
				<input type='button' value='Reset' name='action' class='settings_button' onclick="javascript:resetGrid()">
			</td>
		</tr>
	</table>
</form>
</body>
</html>