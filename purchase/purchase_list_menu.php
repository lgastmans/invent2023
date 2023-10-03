<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$_SESSION["int_purchase_menu_selected"] = 1;
	
	$int_access_level = (getModuleAccessLevel('Purchase'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$qry_city = new Query("
		SELECT DISTINCT supplier_city
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_city
	");

	$qry_supplier = new Query("
		SELECT supplier_id, supplier_name
		FROM stock_supplier
		WHERE supplier_city = '".$qry_city->FieldByName('supplier_city')."'
			AND is_active = 'Y'
		ORDER BY supplier_name
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
				var oSelectSupplier = document.purchase_list_menu.select_supplier;
				
				oSelectSupplier.length = 0;
				
				str_retval = requester.responseText;
				
				arr_suppliers = str_retval.split('|');
				
				oSelectSupplier.options[0] = new Option('ALL', 'ALL');
				for (i=0; i<arr_suppliers.length; i++) {
					arr_temp = arr_suppliers[i].split('^');
					oSelectSupplier.options[i+1] = new Option(arr_temp[1], arr_temp[0]);
				}
			}
			requester = null;
			requester = createRequest();
		}
	}

	function setSuppliers() {
		var oSelectCity = document.purchase_list_menu.select_city;
		
		if (oSelectCity.value == '')
			str_value = '__BLANK';
		else
			str_value = oSelectCity.value;
		requester.onreadystatechange = statehandler;
		requester.open("GET", "../common/get_suppliers.php?live=1&city="+str_value);
		requester.send(null);
	}
		
	function loadStatement() {
		var oSelectCity = document.purchase_list_menu.select_city;
		var oSelectSupplier = document.purchase_list_menu.select_supplier;
		var oSelectDay = document.purchase_list_menu.select_day;
		var oSelectMethod = document.purchase_list_menu.select_method;
		var oSelectOrder = document.purchase_list_menu.select_order;
		var oSelectStoreroom = document.purchase_list_menu.select_storeroom;
		var oSelectShow = document.purchase_list_menu.select_show;
		var oSelectStockTotal = document.purchase_list_menu.select_stock_total;
		
		var arrCategories = [];
		arrCategories = parent.frames['purchase_list_content'].frames['details_categories'].document.getElementsByName('check_category');
		
		var strSelected = '';
		var intLen = arrCategories.length;
		var intCounter = 0;
		if (intLen > 0) {
			for (i=0; i<intLen; i++) {
				if (arrCategories[i].checked) {
					strSelected += arrCategories[i].getAttribute('id')+'|';
					intCounter++;
				}
			}
			if (intCounter > 0)
				strSelected = strSelected.substr(0,strSelected.length-1);
			else
				strSelected = 'ALL';
		}
		else
			strSelected = 'ALL';
		
		str_url = 'purchase_list_details.php?'+
			'city='+oSelectCity.value+
			'&supplier_id='+oSelectSupplier.value+
			'&days='+oSelectDay.value+
			'&method='+oSelectMethod.value+
			'&order='+oSelectOrder.value+
			'&storeroom_id='+oSelectStoreroom.value+
			'&show='+oSelectShow.value+
			'&category='+strSelected+
			'&stock_total='+oSelectStockTotal.value;
		
		parent.frames['purchase_list_content'].frames['details_content'].document.location = str_url;
	}
	
	function printStatement() {
		var oSelectCity = document.purchase_list_menu.select_city;
		var oSelectSupplier = document.purchase_list_menu.select_supplier;
		var oSelectDay = document.purchase_list_menu.select_day;
		var oSelectMethod = document.purchase_list_menu.select_method;
		var oSelectOrder = document.purchase_list_menu.select_order;
		var oSelectStoreroom = document.purchase_list_menu.select_storeroom;
		var oSelectStockTotal = document.purchase_list_menu.select_stock_total;
		var oFrameDetailsDocument = parent.frames['purchase_list_content'].frames['details_content'].document.purchase_list_details;
		
		str_id_list = '';
		for (i=0;i<oFrameDetailsDocument.length;i++) {
			if (oFrameDetailsDocument.elements[i].name.indexOf('cb_')>=0) {
				if (oFrameDetailsDocument.elements[i].checked == true) {
					str_id_list += oFrameDetailsDocument.elements[i].getAttribute('name') + '>';
					if (oFrameDetailsDocument.elements[i+1].name.indexOf('input_')>=0) {
						str_id_list += oFrameDetailsDocument.elements[i+1].value + '^';
					}
				}
			}
		}
		
		str_id_list = str_id_list.substr(0, str_id_list.length-1);
		
		str_variables = 'city='+oSelectCity.value+
		    '|supplier_id='+oSelectSupplier.value+
		    '|days='+oSelectDay.value+
		    '|method='+oSelectMethod.value+
		    '|order='+oSelectOrder.value+
		    '|storeroom_id='+oSelectStoreroom.value+
		    '|stock_total='+oSelectStockTotal.value+
			'|id_list='+str_id_list;
		    
		str_url = '../common/print_dialog.php?print_page=../purchase/purchase_list_print.php&variables='+str_variables;
		
		myWin = window.open(str_url,'print_dialog','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=300,height=150,top=0,left=0');
		myWin.moveTo((screen.availWidth/2 - 300/2), (screen.availHeight/2 - 150/2));
		myWin.focus();
		
	}
	
	function generateOrder() {
		parent.frames['purchase_list_content'].frames['details_content'].document.purchase_list_details.submit();
	}
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth="5" marginheight="5">

<form name="purchase_list_menu" onsubmit="return false">

<table class="edit" border='0' cellpadding='2' cellspacing='0'>
	<tr>
		<td align="center">
			<table border="0">
				<tr>
					<td width='50px' align='right' class='normaltext'>
						City
					</td>
					<td>
						<select name="select_city" class='select_100' onchange='setSuppliers()'>
							<?
								for ($i=1; $i<=$qry_city->RowCount(); $i++) {
									echo "<option value=".$qry_city->FieldByName('supplier_city').">".$qry_city->FieldByName('supplier_city');
									$qry_city->Next();
								}
							?>
						</select>
					</td>
					<td width='95px' align='right' class='normaltext'>
						Supplier
					</td>
					<td>
						<select name="select_supplier" class='select_400'>
<!-- 							<option value='ALL'>All -->
							<?
								for ($i=1; $i<=$qry_supplier->RowCount(); $i++) {
									echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name');
									$qry_supplier->Next();
								}
							?>
						</select>
					</td>
				</tr>
			</table>

			<table border="0">
				<tr>
					<td width='150px' align='right' class='normaltext'>
						Forecast for next
					</td>
					<td class='<?echo $str_class_header?>'>
						<input type="text" name="select_day" value="1" class="input_50">
						<? /*
						<select name='select_day' class='select_100'>
							<?
								for ($i=1;$i<=$int_num_days;$i++) {
									echo "<option value=".$i.">".$i;
								}
							?>
						</select>
						*/ ?>
						<font class='normaltext'>day(s)</font>
					</td>
					<td width='150px' align='right' class='normaltext'>
						Forecast method
					</td>
					<td class='normaltext'>
						<select name='select_method' class='select_200'>
							<option value='<? echo PO_PREDICT_NONE;?>'>None</option>
							<option value='<?echo PO_PREDICT_PREVIOUS?>' <?if (date('n') == 4) echo "disabled";?> <?if ($_SESSION["int_user_prediction_method"] == PO_PREDICT_PREVIOUS) echo "selected"?>>Previous month</option>
							<option value='<?echo PO_PREDICT_PREVIOUS_CURRENT?>' <?if (date('n') == 4) echo "disabled";?> <?if ($_SESSION["int_user_prediction_method"] == PO_PREDICT_PREVIOUS_CURRENT) echo "selected"?>>Previous & Current month</option>
							<option value='<?echo PO_PREDICT_CURRENT?>' <?if ($_SESSION["int_user_prediction_method"] == PO_PREDICT_CURRENT) echo "selected"?>>Current month</option>
						</select>
					</td>
					<td width="150px" align='right' class='normaltext'>
						Sales Storeroom
					</td>
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
				</tr>
			</table>

			<table border="0">
				<tr>
					</td>
					<td width="150px" align='right' class='normaltext'>
						Show stock of
					</td>
					<td>
						<select name='select_stock_total' class='select_200'>
							<option value="CURRENT">current storeroom</option>
							<option value="ALL">all storerooms</option>
						</select>
					</td>
					<td align='right' class='normaltext'>
						Show
					</td>
					<td>
						<select name='select_show' class='select_100'>
							<option value='ALL'>All</option>
							<option value='NON_ZERO' selected>Non-zero</option>
							<option value="BELOW_MINIMUM">Below minimum</option>
						</select>
					</td>
					<td align='right' class='normaltext'>
						Order by
					</td>
					<td>
						<select name='select_order' class='select_100'>
							<option value='product_code'>Code</option>
							<option value='product_description'>Description</option>
						</select>
					</td>
					<td width='60px' align='center'>
						<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
					</td>
					<td>
						<input type="button" name="action" value="Create Purchase Order" class='settings_button' onclick="javascript:generateOrder()">
					</td>
					<td colspan='5'>
						<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadStatement()'>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
	
</form>

</body>
</html>