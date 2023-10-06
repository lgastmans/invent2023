<?
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	
	$int_access_level = (getModuleAccessLevel('Stock'));
	
	$_SESSION["int_stock_selected"] = 2;
	
	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
	
	/*
		USER DEFINED SETTINGS
	*/
	$qry =& $conn->query("
		SELECT bill_decimal_places
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
	);
	$obj = mysqli_fetch_object($qry);
	$int_decimals = $obj->bill_decimal_places;
	
	/*
		CUSTOM FIELDS
	*/
	$grid_name = 'stock_batches';
	$default_alias = 'sp';
	if (IsSet($_POST['cur_alias']))
		$default_alias = $_POST['cur_alias'];
	$user_id = $_SESSION['int_user_id'];
	
	$arr_fields =
		array (
			0 => array (
				'field' => 'batch_id',
				'yui_field' => 'batch_id',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'Y',
				'alias' => 'sb'
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
				'field' => 'batch_code',
				'yui_field' => 'batch_code',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sb'
			),
			5 => array (
				'field' => 'buying_price',
				'yui_field' => 'buying_price',
				'formatter' => 'currency',
				'is_custom_formatter' => 'N',
				'parser' => 'currency',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'sb'
			),
			6 => array (
				'field' => 'selling_price',
				'yui_field' => 'selling_price',
				'formatter' => 'currency',
				'is_custom_formatter' => 'N',
				'parser' => 'currency',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sb'
			),
			7 => array (
				'field' => 'date_created',
				'yui_field' => 'date_created',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sb'
			),
			8 => array (
				'field' => 'stock_available',
				'yui_field' => 'stock_available',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssb'
			),
			9 => array (
				'field' => 'measurement_unit',
				'yui_field' => 'measurement_unit',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'mu'
			),
			10 => array (
				'field' => 'stock_reserved',
				'yui_field' => 'stock_reserved',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssb'
			),
			11 => array (
				'field' => 'stock_ordered',
				'yui_field' => 'stock_ordered',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'ssb'
			),
			
			12 => array (
				'field' => 'tax_description',
				'yui_field' => 'tax_description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'st'
			),
			13 => array (
				'field' => 'is_active',
				'yui_field' => 'is_active',
				'formatter' => 'boolean',
				'is_custom_formatter' => 'N',
				'parser' => 'boolean',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ssb'
			),
			14 => array (
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
?>

<html>
	<head>
		<!--CSS file (default YUI Sam Skin) -->
		<link type="text/css" rel="stylesheet" href="../../yui2.7.0/build/datatable/assets/skins/sam/datatable.css">
		<link rel="stylesheet" type="text/css" href="../../yui2.7.0/build/paginator/assets/skins/sam/paginator.css" />

		<!-- Dependencies -->
		<script type="text/javascript" src="../../yui2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
		<script type="text/javascript" src="../../yui2.7.0/build/element/element-min.js"></script>
		<script type="text/javascript" src="../../yui2.7.0/build/datasource/datasource-min.js"></script>


		<!-- OPTIONAL: JSON Utility (for DataSource) -->
		<script type="text/javascript" src="../../yui2.7.0/build/json/json-min.js"></script>

		<!-- OPTIONAL: Connection Manager (enables XHR for DataSource) -->
		<script type="text/javascript" src="../../yui2.7.0/build/connection/connection-min.js"></script>

		<!-- OPTIONAL: Get Utility (enables dynamic script nodes for DataSource) -->
		<script type="text/javascript" src="../../yui2.7.0/build/get/get-min.js"></script>

		<!-- OPTIONAL: Drag Drop (enables resizeable or reorderable columns) -->
		<script type="text/javascript" src="../../yui2.7.0/build/dragdrop/dragdrop-min.js"></script>

		<!-- OPTIONAL: Calendar (enables calendar editors) -->
		<script type="text/javascript" src="../../yui2.7.0/build/calendar/calendar-min.js"></script>

		<!-- Source files -->
		<script type="text/javascript" src="../../yui2.7.0/build/datatable/datatable-min.js"></script>

		<script type="text/javascript" src="../../yui2.7.0/build/paginator/paginator-min.js"></script>
		
		<!-- browser detection script -->
		<script type="text/javascript" src="../../include/browser_detection.js"></script>
		
		<!-- personalized style sheet -->
		<link rel="stylesheet" type="text/css" href="../../include/styles.css">
		<link rel="stylesheet" type="text/css" href="../../include/yui_grid_styles.php">
		
		<style type="text/css">
			body {
				margin:0;
				padding:0;
				background-color:#E1E1E1;
			}
			
			.delete-button {
				cursor: pointer;
				background: transparent url(../../images/cross.png) no-repeat center center;
				width: 16px;
				height: 16px;
			}
			
/*			.ff th.yui-dt-hidden,
			tr.yui-dt-odd .yui-dt-hidden,
			tr.yui-dt-even .yui-dt-hidden {
				display:none;
			}*/
		</style>

		<script language="javascript">
			/*
				CUSTOM FIELDS
			*/
			var SQL = "SELECT sp.product_id, sp.product_code, sp.product_description, sc.category_description, sb.batch_code, "+
				"sb.batch_id, sb.date_created, sb.deleted, ss.supplier_name, sb.buying_price, sb.selling_price, "+
				"sb.tax_id, st.tax_description, ssb.shelf_id, ssb.stock_available, ssb.stock_reserved, ssb.stock_ordered, "+
				"ssb.storeroom_id, ssb.is_active, mu.is_decimal, mu.measurement_unit "+
				"FROM <?php echo Yearalize("stock_batch"); ?> sb "+
				"INNER JOIN <?php echo Monthalize('stock_storeroom_batch'); ?> ssb ON (ssb.batch_id = sb.batch_id) "+
				"INNER JOIN stock_product sp ON (sp.product_id = sb.product_id) "+
				"INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) "+
				"LEFT JOIN stock_supplier ss ON (ss.supplier_id = sb.supplier_id) "+
				"LEFT JOIN <?php echo Monthalize('stock_tax');?> st ON (st.tax_id=sb.tax_id) "+
				"INNER JOIN stock_measurement_unit mu ON (sp.measurement_unit_id=mu.measurement_unit_id)";
			var ID = "batch_id";
			//var uniqueFilter = "sb.storeroom_id = <?php echo $_SESSION['int_current_storeroom']?>";
			var uniqueFilter = "(sb.storeroom_id = <?php echo $_SESSION['int_current_storeroom']?>) AND (sp.deleted='N') AND (sp.is_available='Y')";
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
				myWin = window.open("modify_batch.php?id="+selectedID,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=500,height=600,top=0');
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
			
			var handleSuccess = function(o) {
				var r = YAHOO.lang.JSON.parse(o.responseText);
				myDataTable.updateRow(currentRowID, r);
			}
			
			var handleFailure = function(o) {
				alert('failed to update row information');
			}
			
			var callback =
			{
				success:handleSuccess,
				failure: handleFailure
			};
			
			function updateData() {
				if (selectedID) {
					var sUrl = '../../include/json_row_data.php';
					var postData = "filter=sb.batch_id|"+selectedID+"&gridname=<? echo $grid_name?>&sql="+SQL;
					var request = YAHOO.util.Connect.asyncRequest('POST', sUrl, callback, postData);
				}
			}
				
			function printGrid(strAction) {
				var oTextFilter = document.getElementById('filter');
				var oSelectField = document.getElementById('field');
				var oSelectMode = document.getElementById('filter_mode');
				
				var oState = myGrid.oDT.getState();
				var dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "DESC" : "ASC";
				var oCol = myGrid.oDT.getColumn(oState.sortedBy.key);
				
				var strURL = "../../include/json_grid_data.php?"+
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
				myWin = window.open("../../include/yuigridcustomize.php?gridname=<?echo $grid_name?>&viewname=default",'customize','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=600,height=500');
				myWin.moveTo((screen.availWidth/2 - 600/2), (screen.availHeight/2 - 500/2));
				myWin.focus();
			}
		</script>
	</head>

<body id="myBody" class="yui-skin-sam">

<form name="client_grid" method="POST">

<table width="100%" height="30px" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					if ($int_access_level > ACCESS_READ) {
						if ($int_access_level==ACCESS_ADMIN) {
							echo "&nbsp;<a href=\"javascript:modifyRecord();\"><img src=\"../../images/page_white_edit.png\" border=\"0\" title=\"Edit batch details\"></a>\n";
						}
					}
				}
			?>
			&nbsp;
			<a href="javascript:printGrid('Y');"><img src="../../images/printer.png" border="0" title="print the content of the grid"></a>
			&nbsp;
			<a href="javascript:printGrid('CSV');"><img src="../../images/csv_export.png" border="0" title="export the content to a CSV file (tab delimited)"></a>
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
			<input type="hidden" id="sortKey" name="sortKey" value="product_code">
			<input type="hidden" id="dir" name="dir" value="ASC">
			<input type="hidden" id="cur_alias" name="cur_alias" value="<?php echo $default_alias;?>">
			<input type="button" name="action" value="filter" onclick="javascript:refreshPage()">
		</td>
		<td class="normaltext">
			<a href="javascript:customize();"><img src="../../images/table.png" border="0"></a>
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
	var myDataTable;
	var currentRowID;

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
						el.innerHTML = "<img src='../../images/" + eImage + "'>";
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
				'../../include/json_grid_data.php?',{
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
						firstPageLinkLabel : "<img src='../../images/resultset_first.png' border='0'>",
						lastPageLinkLabel : "<img src='../../images/resultset_last.png' border='0'>",
						previousPageLinkLabel : "<img src='../../images/resultset_previous.png' border='0'>",
						nextPageLinkLabel : "<img src='../../images/resultset_next.png' border='0'>"
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
			myDataTable = new YAHOO.widget.DataTable(
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
				currentRowID = this.getRecordIndex(record);
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
