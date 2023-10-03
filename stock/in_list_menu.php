<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$qry_categories = new Query("
	    SELECT *
	    FROM stock_category
	    ORDER BY category_description
	");
?>

<script language='javascript'>
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

	function mouseGoesOver(element, aSource) {
		element.src = aSource;
	}
	
	function mouseGoesOut(element, aSource)	{
		element.src = aSource;
	}
	
	function statehandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200) {
				var oSelectCategory = document.stock_in_list.select_category;
				
				oSelectCategory.length = 0;
				
				str_retval = requester.responseText;
				
				arr_categories = str_retval.split('|');
				
				oSelectCategory.options[0] = new Option('All', 'ALL');
				for (i=0; i<arr_categories.length; i++) {
					arr_temp = arr_categories[i].split('^');
					oSelectCategory.options[i+1] = new Option(arr_temp[1], arr_temp[0]);
				}
				
				
			}
			requester = null;
			requester = createRequest();
		}
	}

	function setCategories() {
		var oSelectType = document.stock_in_list.select_type;

		requester.onreadystatechange = statehandler;
		requester.open("GET", "../common/get_categories.php?live=1&type="+oSelectType.value);
		requester.send(null);
	}
	
	function loadStatement() {
		var oSelectDay = document.stock_in_list.select_day;
		var oSelectInType = document.stock_in_list.select_in_type;
		var oSelectType = document.stock_in_list.select_type;
		var oSelectCategory = document.stock_in_list.select_category;
		var oSelectOrder = document.stock_in_list.select_order;
		
		str_url = 'in_list_data.php?'+
		    'category_type='+oSelectType.value+
		    '&category_id='+oSelectCategory.value+
		    '&selected_day='+oSelectDay.value+
		    '&in_type='+oSelectInType.value+
		    '&order='+oSelectOrder.value;
		parent.frames['content'].frames['content'].document.location = str_url;
	}
	
	function printStatement() {
		var oSelectDay = document.stock_in_list.select_day;
		var oSelectInType = document.stock_in_list.select_in_type;
		var oSelectType = document.stock_in_list.select_type;
		var oSelectCategory = document.stock_in_list.select_category;
		var oSelectOrder = document.stock_in_list.select_order;

		str_url = 'in_list_print.php?'+
		    'category_type='+oSelectType.value+
		    '&category_id='+oSelectCategory.value+
		    '&selected_day='+oSelectDay.value+
		    '&in_type='+oSelectInType.value+
		    '&order='+oSelectOrder.value;

		window.open(str_url,'in_list_print','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=100,height=100,top=0,left=0');
	}
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>
<form name="stock_in_list" method="GET">

    <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
	    <td align='right' class='normaltext'>Day</td>
	    <td>
		<select name="select_day" class='select_50'>
			<?
				$int_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
				$int_cur_day = date('d', time());
				for ($i=1; $i<=$int_days; $i++) {
				 if ($i == $int_cur_day)
					echo "<option value=".$i." selected=\"selected\">".$i;
				 else
					echo "<option value=".$i.">".$i;
				}
			?>
		</select>
	    </td>

	    <td  width='35px' align='right' class='normaltext'>In</td>
	    <td>
		<select name='select_in_type' class='select_100'>
			<option value='ALL'>All
			<option value='<? echo TYPE_INTERNAL?>'>Internal</option>
			<option value='<? echo TYPE_RECEIVED?>'>Received</option>
			<option value='<? echo TYPE_CORRECTED?>'>Corrected</option>
			<option value='<? echo TYPE_CANCELLED?>'>Cancelled</option>
			<option value='<? echo TYPE_RETURNED?>'>Returned</option>
			<option value='<? echo TYPE_ADJUSTMENT?>'>Adjusted</option>
		</select>
	    </td>

	    <td width='55px' align='right' class='normaltext'>Type</td>
	    <td>
		<select name='select_type' class='select_200' onchange='javascript:setCategories()'>
		    <option value='ALL'>All
		    <option value='1'>Perishable
		    <option value='2'>Non-Perishable
		</select>
	    </td>

	    <td width='90px' align='right' class='normaltext'>Category</td>
	    <td>
		<select name='select_category' class='select_200'>
		    <option value='ALL'>All
		<?
		    for ($i=0; $i<$qry_categories->RowCount(); $i++) {
			echo "<option value='".$qry_categories->FieldByName('category_id')."'>".$qry_categories->FieldByName('category_description')."\n";
			$qry_categories->Next();
		    }
		?>
		</select>
	    </td>

	    <td width='30px' align='center'>
		<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	    </td>
	    <td>
		<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadStatement()'>
	    </td>

	</tr>
	<tr>
		<td align='right' class='normaltext'>Order</td>
		<td>
			<select name='select_order' class='select_100'>
				<option value='product_code'>Code
				<option value='product_description'>Description
			</select>
		</td>
	</tr>
    </table>
    
</form>
</body>
</html>
