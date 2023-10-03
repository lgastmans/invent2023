<?
    $str_cur_module='Storerooms';
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$_SESSION['int_storerooms_menu_selected']=1;

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
				var oSelectCategory = document.current_stock_menu.select_category;
				
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
		var oSelectType = document.current_stock_menu.select_type;

		requester.onreadystatechange = statehandler;
		requester.open("GET", "../common/get_categories.php?live=1&type="+oSelectType.value);
		requester.send(null);
	}
	
	function loadStatement() {
		var oSelectType = document.current_stock_menu.select_type;
		var oSelectCategory = document.current_stock_menu.select_category;
		var oSelectOrder = document.current_stock_menu.select_order;
		var oSelectQuantity = document.current_stock_menu.select_quantity;
		var oSelectShow = document.current_stock_menu.select_show;
	    
		if (oSelectQuantity.checked)
			str_select_quantity = 'Y';
		else
			str_select_quantity = 'N';
		
		str_url = 'current_stock_data_frameset.php?'+
		    'category_type='+oSelectType.value+
		    '&category_id='+oSelectCategory.value+
		    '&order='+oSelectOrder.value+
		    '&global_stock='+str_select_quantity+
		    '&show='+oSelectShow.value;
		parent.frames['content'].document.location = str_url;
	}
	
	function printStatement() {
		var oSelectType = document.current_stock_menu.select_type;
		var oSelectCategory = document.current_stock_menu.select_category;
		var oSelectOrder = document.current_stock_menu.select_order;
		var oSelectQuantity = document.current_stock_menu.select_quantity;
		var oSelectShow = document.current_stock_menu.select_show;
		var oCheckedCategory = document.getElementById('print_category');
		
		if (oSelectQuantity.checked)
			str_select_quantity = 'Y';
		else
			str_select_quantity = 'N';
		
		if (oCheckedCategory.checked)
			printCategory = 'Y';
		else
			printCategory = 'N';
		
		str_variables = 'category_type='+oSelectType.value+
		    '|category_id='+oSelectCategory.value+
		    '|order='+oSelectOrder.value+
		    '|global_stock='+str_select_quantity+
		    '|show='+oSelectShow.value+
		    '|print_category='+printCategory;
		    
		str_url = '../common/print_dialog.php?print_page=../storerooms/current_stock_print.php&variables='+str_variables;
		
		myWin = window.open(str_url,'print_dialog','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=300,height=150,top=0,left=0');
		myWin.moveTo((screen.availWidth/2 - 300/2), (screen.availHeight/2 - 150/2));
		myWin.focus();
	}
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=20 topmargin=5>
<form name="current_stock_menu" method="GET">

    <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
	    <td width='50px' align='right' class='normaltext'>Type</td>
	    <td>
		<select name='select_type' class='select_200' onchange='javascript:setCategories()'>
		    <option value='ALL'>All
		    <option value='1'>Perishable
		    <option value='2'>Non-Perishable
		</select>
	    </td>
	    <td width='95px' align='right' class='normaltext'>Category</td>
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
	    <td width='50px' align='right' class='normaltext'>Show</td>
	    <td>
		<select name='select_show' class='select_100'>
		    <option value='ALL'>All
		    <option value='ZERO'>Zero
		    <option value='NONZERO'>Non-Zero
		</select>
	    </td>
	    <td width='30px' align='center'>
		<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	    </td>
<!--
	    <td width='30px' align='center'>
		<a href="javascript:export()"><img src="../images/table_go.png" border="0" title='Export to CSV file'></a>
	    </td>
-->
	    <td>
		<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadStatement()'>
	    </td>
	</tr>
	<tr>
		<td align='right' class='normaltext'>Order</td>
		<td>
			<select name='select_order' class='select_100'>
				<option value='product_code'>Code</option>
				<option value='product_description'>Description</option>
				<option value='category_description'>Category</option>
			</select>
		</td>
		<td class='normaltext' colspan='2' align='center'>
			<label>
				<input type='checkbox' name='select_quantity' checked>Stock across storerooms
			</label>
			<label>
				<input type="checkbox" id="print_category" name="print_category">Print the category column
			</label>
		</td>
	</tr>
    </table>
    
</form>
</body>
</html>
