<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	
	$int_access_level = (getModuleAccessLevel('Admin'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
	
	/*
		CUSTOM FIELDS
	*/
	$_SESSION['int_admin_selected'] = 3;
	
	$grid_name = 'admin_product';
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
				'field' => 'product_bar_code',
				'yui_field' => 'product_bar_code',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			3 => array (
				'field' => 'product_description',
				'yui_field' => 'product_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			4 => array (
				'field' => 'category_description',
				'yui_field' => 'category_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sc'
			),
			5 => array (
				'field' => 'mrp',
				'yui_field' => 'mrp',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			6 => array (
				'field' => 'is_available',
				'yui_field' => 'is_available',
				'formatter' => 'boolean',
				'is_custom_formatter' => 'N',
				'parser' => 'boolean',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			7 => array (
				'field' => 'is_av_product',
				'yui_field' => 'is_av_product',
				'formatter' => 'boolean',
				'is_custom_formatter' => 'N',
				'parser' => 'boolean',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			8 => array (
				'field' => 'is_perishable',
				'yui_field' => 'is_perishable',
				'formatter' => 'boolean',
				'is_custom_formatter' => 'N',
				'parser' => 'boolean',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			9 => array (
				'field' => 'list_in_purchase',
				'yui_field' => 'list_in_purchase',
				'formatter' => 'boolean',
				'is_custom_formatter' => 'N',
				'parser' => 'boolean',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			10 => array (
				'field' => 'minimum_qty',
				'yui_field' => 'minimum_qty',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sp'
			),
			11 => array (
				'field' => 'tax_description',
				'yui_field' => 'tax_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'st'
			),
			12 => array (
				'field' => 'measurement_unit',
				'yui_field' => 'measurement_unit',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'mu'
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
			),
			14 => array (
				'field' => 'supplier2_name',
				'yui_field' => 'supplier2_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ss2'
			),
			15 => array (
				'field' => 'image_filename',
				'yui_field' => 'image_filename',
				'formatter' => 'formatImage',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sp'
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
		
		<!-- personalized style sheet -->
		<link rel="stylesheet" type="text/css" href="../include/styles.css">
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
			/*
			.ff th.yui-dt-hidden,
			tr.yui-dt-odd .yui-dt-hidden,
			tr.yui-dt-even .yui-dt-hidden {
				display:none;
			}
			*/
		</style>

		<script language="javascript">
			/*
				CUSTOM FIELDS
			*/
			var SQL = "SELECT *, ss.supplier_name, ss2.supplier_name AS supplier2_name FROM stock_product sp INNER JOIN stock_measurement_unit mu ON sp.measurement_unit_id=mu.measurement_unit_id LEFT JOIN <?php echo Monthalize('stock_tax') ?> st ON st.tax_id=sp.tax_id INNER JOIN stock_category sc ON sc.category_id = sp.category_id LEFT JOIN stock_supplier ss ON ss.supplier_id = sp.supplier_id LEFT JOIN stock_supplier ss2 ON ss2.supplier_id = sp.supplier2_id";
			var ID = "product_id";
			var uniqueFilter = "sp.deleted = 'N'";
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
			
			function editRecord() {
				if (selectedID > 0) {
					myWin = window.open("product_edit.php?id="+selectedID+'&record_id='+currentRecordID, 'edit', 'width=1000,height=700,resizable=yes,scrollbars=yes');
					myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 700/2));
					myWin.focus();
				}
				else
					alert("select a product to edit");
			}
			
			var handleSuccess = function(o) {
				var r = YAHOO.lang.JSON.parse(o.responseText);
				myTable.updateRow(currentRecordID, r);
			}
			
			var handleFailure = function(o) {
				alert('failed to update row information');
			}
			
			var callback =
			{
				success:handleSuccess,
				failure: handleFailure
			};
			
			function updateRow() {
				var sUrl = '../include/json_row_data.php';
				var postData = "filter=product_id|"+selectedID+"&gridname=<?php echo $grid_name;?>&sql="+SQL;
				var request = YAHOO.util.Connect.asyncRequest('POST', sUrl, callback, postData); 
			}
			
			function newRecord() {
				myWin = window.open("product_edit.php",'insert','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=1000,height=700,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 700/2));
				myWin.focus();
			}
			
			function replaceTax() {
				myWin = window.open("taxes_replace.php",'taxes_replace','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=500,height=250,top=0');
				myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 250/2));
				myWin.focus();
			}
			
			function productDuplicate() {
				if (selectedID>0) {
					if (confirm('Are you sure?')) {
						myWin = window.open("product_duplicate.php?id="+selectedID,'product_duplicate','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=500,height=250,top=0');
						myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 250/2));
						myWin.focus();
					}
				}
				else
					alert("Select a product to duplicate");
			}
			
			function printBarcode() {
				if (selectedID > 0) {
					myWin = window.open("print_barcodes.php?id="+selectedID+"&printer=Adobe PDF", 'print_barcode', 'width=400,height=250,resizable=yes');
					myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 250/2));
					myWin.focus();
				}
				else
					alert("select a product to edit");
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
						"&filter=" + oTextFilter.value;
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

<table width="100%" height="30px" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td><?
			if ($int_access_level > ACCESS_READ) {
				echo "&nbsp;<a href=\"javascript:newRecord();\"><img src=\"../images/page_white_add.png\" border=\"0\" title=\"add a new product\"></a>\n";
				echo "&nbsp;<a href=\"javascript:editRecord();\"><img src=\"../images/page_white_edit.png\" border=\"0\" title=\"edit the details of the selected product\"></a>\n";
				echo "&nbsp;<a href=\"javascript:replaceTax();\"><img src=\"../images/table_relationship.png\" border=\"0\" title=\"Replace taxes\"></a>\n";
				echo "&nbsp;<a href=\"javascript:productDuplicate();\"><img src=\"../images/arrow_divide.png\" border=\"0\" title=\"Create a duplicate entry for this product\"></a>\n";
				echo "&nbsp;<a href=\"javascript:printBarcode();\"><img src=\"../images/barcode.png\" border=\"0\" title=\"print barcode for selected product\"></a>\n";
			}
		?>
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
	var currentRecordID;
	var myTable;

	YAHOO.util.Event.addListener(window, "load", function() {
//		var myBody = document.getElementById('myBody');
//		myBody.className = myBody.className + ' ' + browserType();
		
		myGrid = function() {
			
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('field');
			var oSelectMode = document.getElementById('filter_mode');
			var oDefaultSort = '<?echo $str_sort;?>';
			
			YAHOO.widget.DataTable.Formatter.formatImage = function(el, oRecord, oColumn, oData) {
				if (YAHOO.lang.isString(oData)) {
					var eImage = oRecord.getData(oColumn.key);
					if (YAHOO.lang.isString(eImage) && eImage.length) {
						el.innerHTML = "<img src='../images/products/" + eImage + "' width='50px'>";
					} else {
						el.innerHTML = oData;
					}
				} else {
					el.innerHTML = YAHOO.lang.isValue(oData) ? oData : "";
				}
			};
			
			var myColumnDefs = <? get_grid_fields($grid_name, $user_id); ?>;
			myColumnDefs.push({key:"delete", label:' ', className:'delete-button', action:'delete'});
			
			var myDataSource = new YAHOO.util.DataSource(
				'../include/json_grid_data.php?',{
				responseType:YAHOO.util.DataSource.TYPE_JSON
			});
			
			parseYN = function(oData) {
				if (oData == 'Y')
					return "Yes";
				else
					return "No";
			}
			
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
					"&uniqueFilter="+uniqueFilter+
					"&mode="+oSelectMode.value+
					"&field=" + oSelectField.value+
					"&filter=" + oTextFilter.value;
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
					"&uniqueFilter="+uniqueFilter+
					"&filter="+oTextFilter.value+
					"&field="+oSelectField.value+
					"&mode="+oSelectMode.value,
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
			
			myTable = myDataTable;
			
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
									'stockdelete_permanent.php?action=delete&id='+selectedID,
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
				
				myWin = window.open("product_edit.php?id="+selectedID, 'edit', 'width=1000,height=700,resizable=yes');
				myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 700/2));
				myWin.focus();
			}
			
			myDataTable.subscribe('cellClickEvent', onCellClick);
			myDataTable.subscribe('cellDblclickEvent', onCellDblClick);
			
			myDataTable.subscribe("rowMouseoverEvent", myDataTable.onEventHighlightRow);
			myDataTable.subscribe("rowMouseoutEvent", myDataTable.onEventUnhighlightRow);
			myDataTable.set("selectionMode","single");
			myDataTable.subscribe("rowClickEvent", myDataTable.onEventSelectRow);
			
			myDataTable.subscribe('rowClickEvent',function(ev) {
				var target = YAHOO.util.Event.getTarget(ev);
				var record = this.getRecord(target);
				selectedID = record.getData(ID);
				currentRecordID = this.getRecordIndex(record);
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
