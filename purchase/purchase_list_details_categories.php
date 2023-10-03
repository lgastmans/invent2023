<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");

	$qry_category = new Query("
		SELECT category_id, category_description
		FROM stock_category
		ORDER BY category_description
	");
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
		<script language='javascript'>
			function toggle_select(bool_select) {
				var arrCategories = [];
				arrCategories = document.getElementsByName('check_category');
				
				for (i=0; i<arrCategories.length; i++) {
					if (bool_select == 'Y')
						arrCategories[i].checked = true;
					else
						arrCategories[i].checked = false;
				}
			}
			
		</script>
	</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth="5" marginheight="5">

<form name="purchase_list_details_categories" onsubmit="return false">

<table class="edit" border='0' cellpadding='2' cellspacing='0'>
	<tr>
		<td align="center">
		
			<table border='0' width='100%' cellpadding='2' cellspacing='0'>
				<tr>
					<td><a href="javascript:toggle_select('Y')"><img src='../images/tick_true.png' title='Select All' alt='Select All' border='0'></a>&nbsp;<a href="javascript:toggle_select('N')"><img src='../images/tick_false.png' title='Unselect All' alt='Unselect All' border='0'></a></td>
				</tr>
			</table>
			
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
			<?
				$int_rows = $qry_category->RowCount();
				for ($i=0;$i<$int_rows;$i++) {
					echo "<tr><td>";
					echo "<input type='checkbox' id='".$qry_category->FieldByName('category_id')."' name='check_category'><font class='normaltext'>".$qry_category->FieldByName('category_description')."</font>\n";
					echo "</td></tr>";
					
					$qry_category->Next();
				}
			?>
			</table>
		</td>
	</tr>
</table>

</form>
</body>
</html>