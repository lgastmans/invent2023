<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	
	$int_access_level = (getModuleAccessLevel('Stock'));
	
	$_SESSION["int_stock_selected"] = 1;
	
	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
	
	/*
		CUSTOM FIELDS
	*/
	$grid_name = 'stock_products';
	$default_alias = 'sp';
	if (IsSet($_POST['cur_alias']))
		$default_alias = $_POST['cur_alias'];
	$user_id = $_SESSION['int_user_id'];
	
	$arr_fields =
		array (
			0 => array (
				'field' => 'product_id',
				'yui_field' => 'product_id',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'Y',
				'alias' => 'sp'
			),
			1 => array (
				'field' => 'product_code',
				'yui_field' => 'product_code',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			2 => array (
				'field' => 'product_description',
				'yui_field' => 'product_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			3 => array (
				'field' => 'category_description',
				'yui_field' => 'category_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sc'
			),
			4 => array (
				'field' => 'stock_current',
				'yui_field' => 'stock_current',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			5 => array (
				'field' => 'stock_adjusted',
				'yui_field' => 'stock_adjusted',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			6 => array (
				'field' => 'stock_reserved',
				'yui_field' => 'stock_reserved',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			7 => array (
				'field' => 'stock_ordered',
				'yui_field' => 'stock_ordered',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			8 => array (
				'field' => 'stock_minimum',
				'yui_field' => 'stock_minimum',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			9 => array (
				'field' => 'tax_description',
				'yui_field' => 'tax_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'st'
			),
			10 => array (
				'field' => 'measurement_unit',
				'yui_field' => 'measurement_unit',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'mu'
			),
			11 => array (
				'field' => 'sale_price',
				'yui_field' => 'sale_price',
				'formatter' => 'currency',
				'is_custom_formatter' => 'N',
				'parser' => 'currency',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			12 => array (
				'field' => 'use_batch_price',
				'yui_field' => 'use_batch_price',
				'formatter' => 'boolean',
				'is_custom_formatter' => 'N',
				'parser' => 'boolean',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ssp'
			),
			13 => array (
				'field' => 'supplier_name',
				'yui_field' => 'supplier_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ss'
			)
		);
	/*
		END CUSTOM FIELDS
	*/
	init_grid_fields($arr_fields, $grid_name, 'default', $user_id);
	
	$arr_fields = get_filter_fields($grid_name, $user_id);
	
	/*
		CUSTOM FIELD
	*/
	$str_sort = get_default_sort($grid_name, 'default', $user_id);
	if (IsSet($_POST['sortKey']))
		$str_sort = $_POST['sortKey'];

	$str_dir = get_default_dir($grid_name, 'default', $user_id);
	if (IsSet($_POST['dir']))
		$str_dir = $_POST['dir'];
	
	/*
		this file contains the filter initializations
	*/
	include("yui_filter_session.inc.php");
	
	if (!empty($_GET["action"])) {
		if ($_GET["action"]=="del") {
			require("stockdelete.php");
			$_SESSION['str_stock_message'] = deleteRecord($_GET["delid"]);
		}
	}
?>

<html>
	<head>
		<!--CSS file (default YUI Sam Skin) -->
		<link type="text/css" rel="stylesheet" href="../yui2.7.0/build/datatable/assets/skins/sam/datatable.css">
		<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/paginator/assets/skins/sam/paginator.css" />

		<!-- Dependencies -->
		<script type="text/javascript" src="../yui2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/element/element-min.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/datasource/datasource-min.js"></script>


		<!-- OPTIONAL: JSON Utility (for DataSource) -->
		<script type="text/javascript" src="../yui2.7.0/build/json/json-min.js"></script>

		<!-- OPTIONAL: Connection Manager (enables XHR for DataSource) -->
		<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>

		<!-- OPTIONAL: Get Utility (enables dynamic script nodes for DataSource) -->
		<script type="text/javascript" src="../yui2.7.0/build/get/get-min.js"></script>

		<!-- OPTIONAL: Drag Drop (enables resizeable or reorderable columns) -->
		<script type="text/javascript" src="../yui2.7.0/build/dragdrop/dragdrop-min.js"></script>

		<!-- OPTIONAL: Calendar (enables calendar editors) -->
		<script type="text/javascript" src="../yui2.7.0/build/calendar/calendar-min.js"></script>

		<!-- Source files -->
		<script type="text/javascript" src="../yui2.7.0/build/datatable/datatable-min.js"></script>

		<script type="text/javascript" src="../yui2.7.0/build/paginator/paginator-min.js"></script>
		
		<!-- browser detection script -->
		<script type="text/javascript" src="../include/browser_detection.js"></script>
		
		<link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

		<!-- personalized style sheet -->
		<!-- <link rel="stylesheet" type="text/css" href="../include/styles.css"> -->
		<link rel="stylesheet" type="text/css" href="../include/yui_grid_styles.php">



		
		<style type="text/css">
			body {
				margin:0;
				padding:0;
				background-color:#E1E1E1;
			}
			
			.delete-button {
				cursor: pointer;
				background: transparent url(../images/cross.png) no-repeat center center;
				width: 16px;
				height: 16px;
			}
		</style>

		<script language="javascript">
			/*
				CUSTOM FIELDS
			*/
			var SQL = "SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available, sp.is_av_product, sp.minimum_qty, sp.is_minimum_consolidated, sp.tax_id, sp.is_perishable, sp.measurement_unit_id, sp.category_id, mu.measurement_unit, mu.is_decimal, sc.category_description, sp.deleted, st.tax_description, ssp.stock_current, ssp.stock_reserved, ssp.stock_ordered, ssp.stock_adjusted, ssp.stock_minimum, ssp.sale_price, ssp.point_price, ssp.use_batch_price, ss.supplier_name "+
				"FROM stock_product sp "+
				"INNER JOIN stock_measurement_unit mu ON (sp.measurement_unit_id=mu.measurement_unit_id) "+
				"INNER JOIN <?php echo Monthalize('stock_storeroom_product');?> ssp ON (ssp.product_id = sp.product_id) "+
				"LEFT JOIN <?php echo Monthalize('stock_tax');?> st ON (st.tax_id=sp.tax_id) "+
				"INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) "+
				"LEFT JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)";
			var ID = "product_id";
			var uniqueFilter = "(ssp.storeroom_id = <?php echo $_SESSION['int_current_storeroom']?>) AND (sp.deleted='N') AND (sp.is_available='Y')";
			var canDelete = <? if ($can_delete) echo "true"; else echo "false";?>;
			
			function parseKeypress(evt) {
				evt = (evt) ? evt : event;
				var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
				if (charCode == 13)
					refreshPage();
			}
			
			function refreshPage() {
				var oHiddenSort = document.getElementById('sortKey');
				var oSort = myGrid.oDT.get('sortedBy');
				oHiddenSort.value = oSort.key;
				
				var oHiddenAlias = document.getElementById('cur_alias');
				var oCol = myGrid.oDT.getColumn(oSort.key);
				oHiddenAlias.value = oCol.alias;
				
				document.forms[0].submit();
			}
			
			function openRecord() {
				myWin = window.open("viewstock2.php?id="+selectedID,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=250,top=0');
				myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 250/2));
				myWin.focus();
			}
			
			function modifyRecord() {
				if (selectedID > 0) {
					openRecord();
				}
				else
					alert("Select a record to modify!");
			}
			
			function deleteRecord() {
				if (selectedID > 0) {
					if (confirm("Are you sure?")) {
						if (document.location.href.indexOf("?") < 0) {
							document.location = document.location.href+"?action=del&delid="+selectedID;
						} else {
							document.location = document.location.href+"&action=del&delid="+selectedID;
						}
					}
				}
				else
					alert("Select a record to delete!");
			}
			
			function updateGlobalPrice() {
				myWin = window.open("update_global_price.php",'update_global_price','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
				myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
				myWin.focus();
			}
			
			function stockCorrect() {
				if (selectedID > 0)
					myWin = window.open("transfers/stock_correct.php?id="+selectedID,'stock_correct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
				else
					myWin = window.open("transfers/stock_correct.php",'stock_correct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
				myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
				myWin.focus();
			}
			
			function direct_transfer() {
				myWin = window.open("transfers/direct_transfer.php",'direct_transfer','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
				myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
				myWin.focus();
			}
			
			function update_taxes() {
				myWin = window.open("update_taxes.php",'update_tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=650,height=500,top=0');
				myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
				myWin.focus();
			}

			function bulkPackage() {
				myWin = window.open("transfers/bulkPackage.php",'bulkPackage','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=500,height=400,top=0');
				myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 400/2));
				myWin.focus();
			}
			
			function printGrid(strAction) {
				var oTextFilter = document.getElementById('filter');
				var oSelectField = document.getElementById('field');
				var oSelectMode = document.getElementById('filter_mode');
				
				var oState = myGrid.oDT.getState();
				var dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "DESC" : "ASC";
				var oCol = myGrid.oDT.getColumn(oState.sortedBy.key);
				
				var strURL = "../include/json_grid_data.php?"+
						"print="+strAction+
						"&sort="+oCol.fieldname+
						"&alias="+oCol.alias+
						"&dir="+dir+
						"&SQL="+ SQL +
						"&gridname=<?echo $grid_name;?>"+
						"&field=" + oSelectField.value+
						"&filter=" + oTextFilter.value+
						"&uniqueFilter="+uniqueFilter;
				//alert(strURL);
				myWin = window.open(strURL, 'printgrid', '');
				myWin.focus();
			}
			
			function customize() {
				myWin = window.open("../include/yuigridcustomize.php?gridname=<?echo $grid_name?>&viewname=default",'customize','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=600,height=500');
				myWin.moveTo((screen.availWidth/2 - 600/2), (screen.availHeight/2 - 500/2));
				myWin.focus();
			}
		</script>
	</head>

<body id="myBody" class="yui-skin-sam">

<form name="client_grid" method="POST">


<!--
<span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\" style=\"vertical-align:middle\"></span>
<span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\" style=\"vertical-align:middle\">
<span class=\"glyphicon glyphicon-ok\" aria-hidden=\"true\" style=\"vertical-align:middle\">
<span class=\"glyphicon glyphicon-usd\" aria-hidden=\"true\" style=\"vertical-align:middle\">
<span class=\"glyphicon glyphicon-cart\" aria-hidden=\"true\" style=\"vertical-align:middle\">
<span class="glyphicon glyphicon-random" aria-hidden="true" style="vertical-align:middle">
<span class="glyphicon glyphicon-print" aria-hidden="true" style="vertical-align:middle">
<span class="glyphicon glyphicon-export" aria-hidden="true" style="vertical-align:middle">
 -->	


<table width="100%" height="30px" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					if ($int_access_level > ACCESS_READ) {
						echo "&nbsp;<a href=\"javascript:modifyRecord();\"><img src=\"../images/page_white_add.png\" border=\"0\" title=\"Edit product details for this storeroom\"></a>\n";
						if ($int_access_level==ACCESS_ADMIN) {
							echo "&nbsp;<a href=\"javascript:deleteRecord();\"><img src=\"../images/cross.png\" border=\"0\" title=\"Remove product from this storeroom\"></a>\n";
							echo "&nbsp;<a href=\"javascript:stockCorrect();\"><img src=\"../images/lock_edit.png\" border=\"0\" title=\"Correct the stock of this product\"></a>\n";
						}
						echo "&nbsp;<a href=\"javascript:updateGlobalPrice();\"><img src=\"../images/money_add.png\" border=\"0\" title=\"Update the global price\"></a>\n";
						echo "&nbsp;<a href=\"javascript:direct_transfer();\"><img src=\"../images/transmit_go.png\" border=\"0\" title=\"Direct transfer to storeroom\"></a>\n";
						echo "&nbsp;<a href=\"javascript:bulkPackage();\"><img src=\"../images/cart_remove.png\" border=\"0\" title=\"Bulk package transfer\"></a>\n";
					}
				}
			?>
			&nbsp;
			<a href="javascript:update_taxes();"><img src="../images/transmit_go.png" border="0" title="Update product taxes based on price"></a>
			&nbsp;
			<a href="javascript:printGrid('Y');"><img src="../images/printer.png" border="0" title="print the content of the grid"></a>
			&nbsp;
			<a href="javascript:printGrid('CSV');"><img src="../images/csv_export.png" border="0" title="export the content to a CSV file (tab delimited)"></a>
		</td>
		<td align="center" class="normaltext">
			Filter on:
			<select id="field" name="field">
			<?
				for ($i=0;$i<count($arr_fields);$i++) {
					if ($str_field == $arr_fields[$i]['alias'].".".$arr_fields[$i]['fieldname'])
						echo "<option value='".$arr_fields[$i]['alias'].".".$arr_fields[$i]['fieldname']."' selected>".$arr_fields[$i]['columnname']."</option>\n";
					else
						echo "<option value='".$arr_fields[$i]['alias'].".".$arr_fields[$i]['fieldname']."'>".$arr_fields[$i]['columnname']."</option>\n";
				}
			?>
			</select>
			<select id="filter_mode" name="filter_mode">
				<option value="contains" <?if ($str_mode=='contains') echo 'selected';?>>contains</option>
				<option value="equals" <?if ($str_mode=='equals') echo 'selected';?>>equals</option>
				<option value="starts" <?if ($str_mode=='starts') echo 'selected';?>>starts with</option>
			</select>
			<input type="text" id="filter" name="filter" value="<?echo $str_filter;?>" onkeypress="parseKeypress(event)">
			<input type="hidden" id="filter_date" name="filter_date" value="_ALL">
			<input type="hidden" id="filter_type" name="filter_type" value="_ALL">
			<input type="hidden" id="sortKey" name="sortKey" value="species_number">
			<input type="hidden" id="dir" name="dir" value="ASC">
			<input type="hidden" id="cur_alias" name="cur_alias" value="<?php echo $default_alias;?>">
			<input type="button" name="action" value="filter" onclick="javascript:refreshPage()">
		</td>
		<td class="normaltext">
			<a href="javascript:customize();"><img src="../images/table.png" border="0"></a>
		</td>
	</tr>
</table>
<hr>

<div id="test"></div>

<div align="center">
	<div id="basic"></div>
</div>


<script type="text/javascript">

	var selectedID;

	YAHOO.util.Event.addListener(window, "load", function() {
/*		var myBody = document.getElementById('myBody');
		myBody.className = myBody.className + ' ' + browserType();*/
		
		myGrid = function() {
			
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('field');
			var oSelectMode = document.getElementById('filter_mode');
			var oDefaultSort = '<?echo $str_sort;?>';
			
			function getFilters() {
				var oSelectDate = document.getElementById('select_date');
				if (oSelectDate.value == '_ALL')
					strRetVal = '';
				else
					strRetVal = 'date_created,'+oSelectDate.value+'|';
					
				var oSelectType = document.getElementById('select_type');
				if (oSelectType.value == '_ALL')
					strRetVal += '';
				else
					strRetVal += 'payment_type,'+oSelectType.value+'|';
					
				strRetVal = strRetVal.substr(0, strRetVal.length-1);
				
				return strRetVal;
			}
			
			YAHOO.widget.DataTable.Formatter.formatImage = function(el, oRecord, oColumn, oData) {
				if (YAHOO.lang.isString(oData)) {
					var eImage = oRecord.getData('image');
					if (YAHOO.lang.isString(eImage) && eImage.length) {
						el.innerHTML = "<img src='../images/" + eImage + "'>";
					} else {
						el.innerHTML = oData;
					}
				} else {
					el.innerHTML = YAHOO.lang.isValue(oData) ? oData : "";
				}
			};
			
			var myColumnDefs = <? get_grid_fields($grid_name, $user_id); ?>;
			//myColumnDefs.push({key:"delete", label:' ', className:'delete-button', action:'delete'});
			
			var myDataSource = new YAHOO.util.DataSource(
				'../include/json_grid_data.php?',{
				responseType:YAHOO.util.DataSource.TYPE_JSON
			});
			
			
			myDataSource.responseSchema = {
				resultsList: "records",
				fields: <? get_grid_schema($grid_name, $user_id);?>,
				metaFields: {
					totalRecords: "totalRecords"
				}
			};
			
			
			
			// DataTable configuration
			var myRequestBuilder = function(oState, oSelf) {
				// Get states or use defaults
				oState = oState || {pagination:null, sortedBy:null};
				var oCol = myDataTable.getColumn(oState.sortedBy.key);
				
				var sort = (oState.sortedBy) ? oCol.fieldname : oDefaultSort;
				var dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "DESC" : "ASC";
				var startIndex = (oState.pagination) ? oState.pagination.recordOffset : 0;
				var results = (oState.pagination) ? oState.pagination.rowsPerPage : 10;
				
				// Build custom request
				var str_retval =
					"sort=" + sort +
					"&alias=" + oCol.alias +
					"&dir=" + dir +
					"&meta=true"+
					"&SQL="+ SQL +
					"&gridname=<?echo $grid_name;?>"+
					"&startIndex=" + startIndex +
					"&results=" + results +
					"&field=" + oSelectField.value+
					"&filter=" + oTextFilter.value+
					"&mode="+oSelectMode.value+
					"&uniqueFilter="+uniqueFilter;
				//alert(str_retval);
				return str_retval;
			};
			
			var myConfigs = {
				initialRequest: "sort="+oDefaultSort+
					"&alias=<?echo $default_alias;?>"+
					"&meta=true"+
					"&SQL="+ SQL +
					"&gridname=<?echo $grid_name;?>"+
					"&dir=asc"+
					"&startIndex=0"+
					"&results=20"+
					"&filter="+oTextFilter.value+
					"&field="+oSelectField.value+
					"&mode="+oSelectMode.value+
					"&uniqueFilter="+uniqueFilter,
				dynamicData: true,
				sortedBy : {key:oDefaultSort, dir:YAHOO.widget.DataTable.CLASS_ASC},
				generateRequest : myRequestBuilder,
				paginator: new YAHOO.widget.Paginator(
					{
						rowsPerPage:<?php echo $int_grid_rows;?>,
						rowsPerPageOptions : [10,15,20],
						template : "{FirstPageLink} {PreviousPageLink} {PageLinks} {NextPageLink} {LastPageLink} show{RowsPerPageDropdown} per page",
						firstPageLinkLabel : "<img src='../images/resultset_first.png' border='0'>",
						lastPageLinkLabel : "<img src='../images/resultset_last.png' border='0'>",
						previousPageLinkLabel : "<img src='../images/resultset_previous.png' border='0'>",
						nextPageLinkLabel : "<img src='../images/resultset_next.png' border='0'>"
					}
				),
				currencyOptions: {
					prefix: "Rs.",
					decimalPlaces:<?php echo $int_currency_decimals;?>,
					decimalSeparator:".",
					thousandsSeparator:","
				},
				numberOptions: {
					decimalPlaces:<?php echo $int_decimals;?>,
					decimalSeparator:".",
					thousandsSeparator:","
				}
			};
			
			
			// DataTable instance
			var myDataTable = new YAHOO.widget.DataTable(
				"basic",
				myColumnDefs,
				myDataSource,
				myConfigs
			);
			
			var onCellClick = function(oArgs) {
				var target = oArgs.target;
				var column = this.getColumn(target);
				var record = this.getRecord(target);
				
				var selectedID = record.getData(ID);
				
				/*
					CUSTOM FILE FOR DELETING
				*/
				switch (column.action) {
					case 'delete':
						if (canDelete) {
							if (confirm('Are you sure you want to delete this row?')) {
								
								YAHOO.util.Connect.asyncRequest(
									'GET',
									'currency_delete.php?action=delete&id='+selectedID,
									{
										success: function (o) {
											var r = YAHOO.lang.JSON.parse(o.responseText);
											if (r.replyStatus == 'Ok') {
												this.deleteRow(target);
											} else {
												alert('Error - ' + r.replyText);
											}
										},
										failure: function (o) {
											alert('failure :'+ o.responseText);
										},
										scope:this
									}
								);
							}
						}
						else
							alert('You do not have permission to delete');
						break;
					default:
						/*
						oFrame = parent.document.getElementById('family_edit');
						oValue = record.getData('family_id');
						oFrame.src = 'family_edit.php?id='+oValue;
						*/
						this.onEventShowCellEditor(oArgs);
						
						break;
				}
			};
			
			var onCellDblClick = function(oArgs) {
				var target = oArgs.target;
				var column = this.getColumn(target);
				var record = this.getRecord(target);
				
				myWin = window.open("bills_grid_details.php?action=view&id="+selectedID,'tax_grid_details','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=600');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
				myWin.focus();
			}
			/*
			var myBuildUrl = function(datatable,record) {
				var url = '';
				var cols = datatable.getColumnSet().keys;
				for (var i = 0; i < cols.length; i++) {
					if (cols[i].isPrimaryKey) {
						url += '&id=' + escape(record.getData(cols[i].key));
					}
				}
				return url;
			};
			*/
			
			myDataTable.subscribe('cellClickEvent', onCellClick);
			//myDataTable.subscribe('cellDblclickEvent', onCellDblClick);
			
			myDataTable.subscribe("rowMouseoverEvent", myDataTable.onEventHighlightRow);
			myDataTable.subscribe("rowMouseoutEvent", myDataTable.onEventUnhighlightRow);
			myDataTable.set("selectionMode","single");
			myDataTable.subscribe("rowClickEvent", myDataTable.onEventSelectRow);
			
			myDataTable.subscribe('rowClickEvent',function(ev) {
				var target = YAHOO.util.Event.getTarget(ev);
				var record = this.getRecord(target);
				selectedID = record.getData(ID);
				//alert(selectedID);
			});
			
			// Update totalRecords on the fly with value from server
			myDataTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
				oPayload.totalRecords = oResponse.meta.totalRecords;
				return oPayload;
			}
			
			return {
				oDS: myDataSource,
				oDT: myDataTable
			};
		}();
	});

</script>


</form>
</body>
</html>
