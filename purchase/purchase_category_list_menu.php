<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$_SESSION["int_purchase_menu_selected"] = 2;
	
	$int_access_level = (getModuleAccessLevel('Purchase'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$qry_categories = new Query("
	    SELECT *
	    FROM stock_category
	    ORDER BY category_description
	");

	$int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);

	$arr_storeroom_list = getStoreroomList();
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
				var oSelectCategory = document.purchase_category_list_menu.select_category;
				
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
		var oSelectType = document.purchase_category_list_menu.select_type;

		requester.onreadystatechange = statehandler;
		requester.open("GET", "../common/get_categories.php?live=1&type="+oSelectType.value);
		requester.send(null);
	}
	
	function loadStatement() {
		var oSelectType = document.purchase_category_list_menu.select_type;
		var oSelectCategory = document.purchase_category_list_menu.select_category;
		var oSelectDay = document.purchase_category_list_menu.select_day;
		var oSelectMethod = document.purchase_category_list_menu.select_method;
		var oSelectOrder = document.purchase_category_list_menu.select_order;
		var oSelectStoreroom = document.purchase_category_list_menu.select_storeroom;
		
		str_url = 'purchase_category_list_data.php?'+
		    'category_type='+oSelectType.value+
		    '&category_id='+oSelectCategory.value+
		    '&days='+oSelectDay.value+
		    '&method='+oSelectMethod.value+
		    '&order='+oSelectOrder.value+
		    '&storeroom_id='+oSelectStoreroom.value;
		
		parent.frames['purchase_category_list_content'].document.location = str_url;
	}
	
	function printStatement() {
		var oSelectType = document.purchase_category_list_menu.select_type;
		var oSelectCategory = document.purchase_category_list_menu.select_category;
		var oSelectDay = document.purchase_category_list_menu.select_day;
		var oSelectMethod = document.purchase_category_list_menu.select_method;
		var oSelectOrder = document.purchase_category_list_menu.select_order;
		var oSelectStoreroom = document.purchase_category_list_menu.select_storeroom;
	    
		str_variables = 'category_type='+oSelectType.value+
		    '|category_id='+oSelectCategory.value+
		    '|days='+oSelectDay.value+
		    '|method='+oSelectMethod.value+
		    '|order='+oSelectOrder.value+
		    '|storeroom_id='+oSelectStoreroom.value;
		    
		str_url = '../common/print_dialog.php?print_page=../purchase/purchase_category_list_print.php&variables='+str_variables;
		
		myWin = window.open(str_url,'print_dialog','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=300,height=150,top=0,left=0');
		myWin.moveTo((screen.availWidth/2 - 300/2), (screen.availHeight/2 - 150/2));
		myWin.focus();
//alert(str_url);
	}
	
	function printPriceList() {
		var oSelectType = document.purchase_category_list_menu.select_type;
		var oSelectCategory = document.purchase_category_list_menu.select_category;
		
		str_id_list = '';
		for (i=0;i<parent.frames['purchase_category_list_content'].document.purchase_category_list_data.length;i++) {
			if (parent.frames['purchase_category_list_content'].document.purchase_category_list_data.elements[i].name.indexOf('cb_')>=0) {
				if (parent.frames['purchase_category_list_content'].document.purchase_category_list_data.elements[i].checked == true)
					str_id_list += parent.frames['purchase_category_list_content'].document.purchase_category_list_data.elements[i].getAttribute('name') + '^';
			}
		}
		
		str_url = 'print_price_list.php?type='+oSelectType.value+'&category='+oSelectCategory.value+'&id_list='+str_id_list;
		
		myWin = window.open(str_url,'print_dialog','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=yes,width=800,height=600,top=0,left=0');
		myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
		myWin.focus();
	}
	
	function generateOrder() {
		alert('This operation is not yet available');
	}

</script>


<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>
<form name="purchase_category_list_menu" method='GET' onsubmit="return false">

    <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
	    <td width='50px' align='right' class='normaltext'>Type</td>
	    <td>
		<select name='select_type' class='select_100' onchange='javascript:setCategories()'>
		    <option value='ALL'>All
		    <option value='1'>Perishable
		    <option value='2'>Non-Perishable
		</select>
	    </td>
	    <td width='95px' align='right' class='normaltext'>Category</td>
	    <td>
		<select name='select_category' class='select_400'>
		    <option value='ALL'>All
		<?
		    for ($i=0; $i<$qry_categories->RowCount(); $i++) {
			echo "<option value='".$qry_categories->FieldByName('category_id')."'>".$qry_categories->FieldByName('category_description')."\n";
			$qry_categories->Next();
		    }
		?>
		</select>
	    </td>
		<td align='right' class='normaltext'>Order</td>
		<td>
			<select name='select_order' class='select_100'>
				<option value='product_code'>Code
				<option value='product_description'>Description
			</select>
		</td>
	</tr>
	<tr>
	    <td width='150px' align='right' class='normaltext'>Forecast for next </td>
	    <td class='normaltext'>
		<select name='select_day' class='select_100'>
			<?
				for ($i=1;$i<=$int_num_days;$i++) {
					echo "<option value=".$i.">".$i;
				}
			?>
		</select>
		day(s)
	    </td>
	    <td width='150px' align='right' class='normaltext'>Forecast method </td>
	    <td class='<?echo $str_class_header?>'>
		<select name='select_method' class='select_400'>
			<option value='<?echo PO_PREDICT_PREVIOUS?>' <?if ($_SESSION["int_user_prediction_method"] == PO_PREDICT_PREVIOUS) echo "selected"?>>Previous month
			<option value='<?echo PO_PREDICT_PREVIOUS_CURRENT?>' <?if ($_SESSION["int_user_prediction_method"] == PO_PREDICT_PREVIOUS_CURRENT) echo "selected"?>>Previous & Current month
			<option value='<?echo PO_PREDICT_CURRENT?>' <?if ($_SESSION["int_user_prediction_method"] == PO_PREDICT_CURRENT) echo "selected"?>>Current month
		</select>
	    </td>
	    <td align='right' class='normaltext'>Sales Storeroom</td>
	    <td>
		<select name='select_storeroom' onkeypress="focusNext(this, 'button_save', event)" class='select_100'>
		<?
		    foreach ($arr_storeroom_list as $key=>$value) {
			    if ($key == $_SESSION['int_current_storeroom'])
				echo "<option value='$key' selected>$value</option>\n";
			    else
				echo "<option value='$key'>$value</option>\n";
		    }
		?>
		</select>
	    </td>
	</tr>
	<tr>
		<td width='60px' align='center'>
		    <a href="javascript:printStatement()"><img src="../images/printer.png" title='Print Purchase Order' alt='Print Purchase Order' border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
		    &nbsp;
		    <a href="javascript:printPriceList()"><img src="../images/printer.png" title='Print Price List' alt='Print Price List' border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
		    &nbsp;
		    <a href="javascript:generateOrder()"><img border='0' src='../images/page.png' alt='Generate Purchase Order' onmouseover="javascript:mouseGoesOver(this, '../images/page_over.png')" onmouseout="javascript:mouseGoesOut(this, '../images/page.png')"></a>
		</td>
		<td colspan='5'>
		    <input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadStatement()'>
		</td>
		
	</tr>
    </table>

</form>
</body>
</html>
