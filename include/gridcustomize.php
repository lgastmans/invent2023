<? 
  require_once("const.inc.php");
  require_once("session.inc.php");
  require_once("db.inc.php");


error_reporting(E_ERROR);

/*
	if we need to create a new view
*/
if (!empty($_REQUEST['newviewname'])) {
	$qry = new Query("
		SELECT * 
		FROM grid 
		WHERE view_name='all' 
			AND grid_name='". $_REQUEST['gridname']."' 
			AND user_id=".$_SESSION['int_user_id']." 
		ORDER BY column_order LIMIT 0,1
	");
	
	if ($qry->RowCount()>0) {
			$str_insert = "INSERT INTO grid (user_id, 
						grid_name, 
						view_name, 
						column_name,
						field_name,
						field_type,
						width,
						can_filter,
						callback,
						visible,
						column_order)
				VALUES (
					\"".$qry->FieldByName('user_id')."\",
					\"".$qry->FieldByName('grid_name')."\",
					\"".$_REQUEST['newviewname']."\",
					\"".$qry->FieldByName('column_name')."\",
					\"".$qry->FieldByName('field_name')."\",
					\"".$qry->FieldByName('field_type')."\",
					\"".$qry->FieldByName('width')."\",
					\"".$qry->FieldByName('can_filter')."\",
					\"".$qry->FieldByName('callback')."\",
					\"".$qry->FieldByName('visible')."\",
					\"".$qry->FieldByName('column_order')."\"
				)";
//			echo $str_insert;
			$qry->Query($str_insert);
			$_REQUEST['viewname']=$_REQUEST['newviewname'];
		
	}
}

if ($_REQUEST['action']=='Save') {
	
	$qry_update = new Query("SELECT * FROM grid LIMIT 1");
	
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
				
		$qry_update->Query($str_update);
	}
}

if ($_REQUEST['action']=='delete') {
	$qry= new Query("delete from grid where view_name='".$_REQUEST['viewname']."' and grid_name='". $_REQUEST['gridname']."' and user_id=".$_SESSION['int_user_id']." and field_name='".$_REQUEST['fieldname']."'");
}

if ($_REQUEST['action']=='Add') {
	$qry = new Query("select * from grid where view_name='all' and grid_name='". $_REQUEST['gridname']."' and user_id=".$_SESSION['int_user_id']." and field_name='".$_REQUEST['fieldname']."'");
	if ($qry->RowCount()>0) {
		$qry2 = new Query("select * from grid where view_name='".$_REQUEST['viewname']."' and grid_name='". $_REQUEST['gridname']."' and user_id=".$_SESSION['int_user_id']." and field_name='".$_REQUEST['fieldname']."'");
		
		if ($qry2->RowCount()==0) {

			$str_insert = "INSERT INTO grid (user_id, 
						grid_name, 
						view_name, 
						column_name,
						field_name,
						field_type,
						width,
						can_filter,
						callback,
						visible,
						column_order)
				VALUES (
					\"".$qry->FieldByName('user_id')."\",
					\"".$qry->FieldByName('grid_name')."\",
					\"".$_REQUEST['viewname']."\",
					\"".$qry->FieldByName('column_name')."\",
					\"".$qry->FieldByName('field_name')."\",
					\"".$qry->FieldByName('field_type')."\",
					\"".$qry->FieldByName('width')."\",
					\"".$qry->FieldByName('can_filter')."\",
					\"".$qry->FieldByName('callback')."\",
					\"".$qry->FieldByName('visible')."\",
					\"".$qry->FieldByName('column_order')."\"
				)";
//			echo $str_insert;
			$qry->Query($str_insert);
		}
	}
}

?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=20 topmargin=5>
<form name='viewform' method='post' action='gridcustomize.php'>
<input type='hidden' name='gridname' value='<? echo $_REQUEST["gridname"]; ?>'>
<font class='normaltext_bold'>Customize Grid </font><br><br>

<font class='normaltext'>View:</font><select name='viewname' class='select_100' size=1>
<? 
if (empty($_REQUEST['viewname'])) {
	$_REQUEST['viewname']='default';
}
$str_qry = "select distinct view_name from grid where grid_name='". $_REQUEST['gridname']."' and user_id=".$_SESSION['int_user_id']." and view_name<>'all'";
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
<input type='submit' name='action' value='Load' class='settings_button'><script language="JavaScript">
function getName() {
   var newname= prompt('Please enter a name for the view:', ' ');
   if (newname!="") {
	viewform.newviewname.value=newname;
	viewform.submit();
   }
}
</script>
<input type='hidden' name='newviewname' value=''>
<input type='button' name='mybutton' onclick='getName();' value='New' class='settings_button'>
<hr>
<br>
<font class='normaltext_bold'>Current Fields Visible:</font><br>
<table border=1 cellpadding=2 cellspacing=0>
	<tr class='normaltext_bold' bgcolor='lightgrey'>
		<td>Field</td>
		<td>Caption</td>
		<td>Order</td>
		<td>Width</td>
		<td>&nbsp;</td>
	</tr>
	<?
	$str_qry="
		SELECT *
		FROM grid
		WHERE view_name='".$_REQUEST['viewname']."'
			AND grid_name='". $_REQUEST['gridname']."'
			AND user_id=".$_SESSION['int_user_id']."
		ORDER BY column_order";
	$qry_fields = new Query($str_qry);
	
	for ($i=0;$i<$qry_fields->RowCount();$i++) {
		echo "<tr>";
		echo "<td class='normaltext'>".$qry_fields->FieldByName('field_name')."</td>";
		echo "<td><input class='input_200' type='text' value='".$qry_fields->FieldByName('column_name')."' name='field_caption|".$qry_fields->FieldByName('grid_id')."'></td>";
		echo "<td><input class='input_50' type='text' value='".$qry_fields->FieldByName('column_order')."' name='field_order|".$qry_fields->FieldByName('grid_id')."' size='7'></td>";
		echo "<td><input class='input_50' type='text' value='".$qry_fields->FieldByName('width')."' name='field_width|".$qry_fields->FieldByName('grid_id')."' size='7'></td>";
		echo "<td><a href='gridcustomize.php?action=delete&gridname=".$_REQUEST['gridname']."&viewname=".$_REQUEST['viewname']."&fieldname=".$qry_fields->FieldByName('field_name')."'><img src='../images/delete.png' border='0'></a></td>";
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
<font class='normaltext_bold'>Add Field: </font><br>

<select name='fieldname' class='select_200' size=1>
<? 
$str_qry = "select column_name,field_name from grid where view_name='all' and grid_name='". $_REQUEST['gridname']."' and user_id=".$_SESSION['int_user_id'];
$qry_fields = new Query($str_qry);
for ($i=0;$i < $qry_fields->RowCount();$i++) {
	echo "<option value='".$qry_fields->FieldByName('field_name')."' ";
	if ($qry_fields->FieldByName('column_name')==$_REQUEST['fieldname']) echo "selected";
	echo ">".$qry_fields->FieldByName('column_name')."</option>\n";
	$qry_fields->Next();
}
?>
</select>
&nbsp;<input type='submit' name='action' value='Add' class='settings_button'>
</form>
</body>
</html>