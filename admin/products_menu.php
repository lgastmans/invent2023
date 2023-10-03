<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/yui_grid.inc.php");
	
	$str_grid_name = "admin_stock";
	
	insert_grid_fields('stock_product', $str_grid_name, $_SESSION['int_user_id']);
	
	$str_filter = "";
	
	$qry_columns = new Query("
		SELECT *
		FROM grid
		WHERE grid_name = '$str_grid_name'
		    AND visible = 'Y'
		    AND can_filter = 'Y'
		    AND view_name = 'default'
		    AND user_id = ".$_SESSION['int_user_id']."
		ORDER BY column_order ASC
	");
	
	$str_query = "
		SELECT DISTINCT view_name
		FROM grid
		WHERE grid_name = '$str_grid_name'
			AND user_id = ".$_SESSION['int_user_id'];
	$qry_view = new Query($str_query);
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	
	<script language="javascript">
		function mouseGoesOver(element, aSource) {
			element.src = aSource;
		}
		
		function mouseGoesOut(element, aSource) {
			element.src = aSource;
		}
		
		function filter() {
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('select_field');
			
			var strURL = "products_grid.php?"+
				"filter="+oTextFilter.value+
				"&field="+oSelectField.value;
			//alert(strURL);
			
			parent.frames['content'].document.location = strURL;
		}
		
		function editRecord() {
			var ID = parent.frames['content'].selectedID;
			if (ID > 0) {
				myWin = window.open("product_edit.php?id="+ID,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=1000,height=620,top=0');
				myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 620/2));
				myWin.focus();
			}
			else
				alert('select a news item to edit');
		}
		
		function addRecord() {
			myWin = window.open("product_edit.php", 'newsedit', 'width=600,height=650,resizable=yes');
			myWin.moveTo((screen.availWidth/2 - 600/2), (screen.availHeight/2 - 650/2));
			myWin.focus();
		}
		
		function delRecord() {
			var ID = parent.frames['content'].selectedID;
			if (ID > 0) {
				if (confirm('Are you sure?'))
					parent.frames['content'].document.location = 'news_grid.php?action=del&id='+ID;
			}
			else
				alert('select a news item to delete');
		}
		
		function customizeGrid(strGridName, strUserID) {
			var strURL = "../common/grid_customize.php?"+
				"grid_name="+strGridName+
				"&user_id="+strUserID;
			myWin = window.open(strURL,'grid','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=600,height=650,top=0');
			myWin.moveTo((screen.availWidth/2 - 600/2), (screen.availHeight/2 - 650/2));
			myWin.focus();
		}
		
		function printStatement(strGridName) {
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('select_field');
			var oSelectView = document.getElementById('select_view');
			
			var strURL = "products_grid_print.php?"+
				"grid_name="+strGridName+
				"&filter="+oTextFilter.value+
				"&field="+oSelectField.value+
				"&view="+oSelectView.value;
				
			var myWin = window.open(strURL, 'products_grid_print');
			myWin.focus();
		}
	</script>
</head>

<body id="body_bgcolor" marginheight="0" marginwidth="0" topmargin="0" leftmargin="0">
	<table width='100%' height='100%' border="0">
		<tr>
			<td>
				<a href="javascript:addRecord();"><img src="../images/page_add.png" border="0" title="add a product"></a>
				<a href="javascript:editRecord();"><img src="../images/page_edit.png" border="0" title="edit the selected product details"></a>
				<a href="javascript:delRecord();"><img src="../images/page_delete.png" border="0" title="remove the selected product"></a>
			</td>
			<td class="normaltext" align="center">
				Filter
				<select id="select_field" name="select_field" class="select_150">
				<?
					for ($i=0;$i<$qry_columns->RowCount();$i++) {
						echo "<option value=\"".$qry_columns->FieldByName('field_name')."\">".$qry_columns->FieldByName('column_name')."</option>\n";
						$qry_columns->Next();
					}
				?>
				</select>
				<input type='text' id='filter' name='filter' value="<?echo $str_filter;?>" class="input_200">
				<input type='button' name='action' value="filter" onclick="javascript:filter()">
				&nbsp;
				<a href="javascript:printStatement('<?echo $str_grid_name?>')"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
			</td>
			<td class="normaltext">
				View:&nbsp;
				<select id="select_view" name="select_view" onchange="javascript:filter()" class="select_100">
				<?
					for ($i=0;$i<$qry_view->RowCount();$i++) {
						echo "<option value='".$qry_view->FieldByName('view_name')."'>".$qry_view->FieldByName('view_name')."</option>\n";
						$qry_view->Next();
					}
				?>
				</select>
				&nbsp;
				<a href="#" onclick="javascript:customizeGrid('<?echo $str_grid_name?>',<?echo $_SESSION['int_user_id']?>)"><img src="../images/application_view_columns.png" border="0"></a>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</body>
</html>