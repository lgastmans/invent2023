<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	
	$int_access_level = (getModuleAccessLevel('Clients'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
		
	/*
		CUSTOM FIELDS
	*/
	$_SESSION['int_clients_menu_selected'] = 1;
	
	$grid_name = 'admin_customer';
	$default_alias = 'c';
	if (IsSet($_POST['cur_alias']))
		$default_alias = $_POST['cur_alias'];
	$user_id = $_SESSION['int_user_id'];
	
	$arr_fields =
		array (
			0 => array (
				'field' => 'id',
				'yui_field' => 'id',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'Y',
				'alias' => 'c'
			),
			1 => array (
				'field' => 'company',
				'yui_field' => 'company',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			2 => array (
				'field' => 'address',
				'yui_field' => 'address',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			3 => array (
				'field' => 'city',
				'yui_field' => 'city',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			4 => array (
				'field' => 'email',
				'yui_field' => 'email',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			5 => array (
				'field' => 'contact_person',
				'yui_field' => 'contact_person',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			6 => array (
				'field' => 'username',
				'yui_field' => 'username',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			7 => array (
				'field' => 'is_active',
				'yui_field' => 'is_active',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseYN',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			8 => array (
				'field' => 'can_view_price',
				'yui_field' => 'can_view_price',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseYN',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			9 => array (
				'field' => 'currency_name',
				'yui_field' => 'currency_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'sc'
			),
			10 => array (
				'field' => 'cell',
				'yui_field' => 'cell',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'c'
			),
			11 => array (
				'field' => 'phone1',
				'yui_field' => 'phone1',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'c'
			)
		);
	init_grid_fields($arr_fields, $grid_name, 'default', $user_id);
	
	$arr_fields = get_filter_fields($grid_name, $user_id);

	$str_filter = '';
	if (IsSet($_POST['filter']))
		$str_filter = $_POST['filter'];

	/*
		CUSTOM FIELD
	*/
	$str_sort = get_default_sort($grid_name, 'default', $user_id); //$arr_fields[0]['yui_fieldname'];
	if (IsSet($_POST['sortKey']))
		$str_sort = $_POST['sortKey'];

	$str_dir = get_default_dir($grid_name, 'default', $user_id);
	if (IsSet($_POST['dir'])) {
		if ($_POST['dir'] == 'yui-dt-desc')
			$str_dir = 'DESC';
		else
			$str_dir = 'ASC';
	}
	
	/*
		CUSTOM FIELD
	*/
	$str_field = $arr_fields[0]['yui_fieldname'];
	if (IsSet($_POST['field']))
		$str_field = $_POST['field'];

	$str_mode = 'contains';
	if (IsSet($_POST['filter_mode']))
		$str_mode = $_POST['filter_mode'];
	
	/*
		this file contains the filter initializations
	*/
	//include("yui_filter_session.inc.php");
	
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
			
/*			.ff th.yui-dt-hidden,
			tr.yui-dt-odd .yui-dt-hidden,
			tr.yui-dt-even .yui-dt-hidden {
				display:none;
			}*/
		</style>

		<script language="javascript">
			var SQL = "SELECT * FROM customer c LEFT JOIN stock_currency sc ON (c.currency_id = sc.currency_id)";
			var ID = "id";
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
					myWin = window.open("viewcustomer.php?id="+selectedID, 'edit', 'width=1000,height=800,resizable=yes');
					myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 800/2));
					myWin.focus();
				}
				else
					alert('select a client to edit');
			}
			
			function newRecord() {
				myWin = window.open("viewcustomer.php",'insert','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=1000,height=800,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 800/2));
				myWin.focus();
			}
			
			function printGrid(strAction) {
				var oTextFilter = document.getElementById('filter');
				var oSelectField = document.getElementById('field');
				var oSelectMode = document.getElementById('filter_mode');
				
				var strURL = "../include/json_grid_data.php?"+
						"print="+strAction+
						"&sort=<?echo $str_sort?>"+
						"&alias=<?echo $default_alias;?>"+
						"&dir=<?echo $str_dir?>"+
						"&SQL="+ SQL +
						"&gridname=<?echo $grid_name;?>"+
						"&field=" + oSelectField.value+
						"&filter=" + oTextFilter.value;
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
		<td>
			&nbsp;
			<a href="javascript:newRecord();"><img src="../images/user_add.png" border="0" title="add a new client"></a>
			&nbsp;
			<a href="javascript:editRecord();"><img src="../images/user_edit.png" border="0" title="edit the details of the selected client"></a>
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
					if ($str_field == $arr_fields[$i]['fieldname'])
						echo "<option value='".$arr_fields[$i]['fieldname']."' selected>".$arr_fields[$i]['columnname']."</option>\n";
					else
						echo "<option value='".$arr_fields[$i]['fieldname']."'>".$arr_fields[$i]['columnname']."</option>\n";
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

	YAHOO.util.Event.addListener(window, "load", function() {
/*		var myBody = document.getElementById('myBody');
		myBody.className = myBody.className + ' ' + browserType();*/
		
		myGrid = function() {
			
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('field');
			var oSelectMode = document.getElementById('filter_mode');
			var oDefaultSort = '<?echo $str_sort;?>';
			
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
				var sort = (oState.sortedBy) ? oState.sortedBy.key : oDefaultSort;
				var dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "DESC" : "ASC";
				var startIndex = (oState.pagination) ? oState.pagination.recordOffset : 0;
				var results = (oState.pagination) ? oState.pagination.rowsPerPage : 10;
				var oCol = myDataTable.getColumn(oState.sortedBy.key);
				
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
			
			var onCellClick = function(oArgs) {
				var target = oArgs.target;
				var column = this.getColumn(target);
				var record = this.getRecord(target);
				
				var selectedID = record.getData(ID);
				
				switch (column.action) {
					case 'delete':
						if (confirm('Are you sure you want to delete this row?')) {
							
							YAHOO.util.Connect.asyncRequest(
								'GET',
								'client_delete.php?action=delete&id='+selectedID,
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
										alert('failure :'+ r.replyText+':');
									},
									scope:this
								}
							);
						}
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
				
				myWin = window.open("viewcustomer.php?action=view&id="+selectedID,'bills_grid_details','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=600');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
				myWin.focus();
			}
			
			myDataTable.subscribe('cellClickEvent',onCellClick);
			myDataTable.subscribe('cellDblclickEvent', onCellDblClick);
			
			myDataTable.subscribe("rowMouseoverEvent", myDataTable.onEventHighlightRow);
			myDataTable.subscribe("rowMouseoutEvent", myDataTable.onEventUnhighlightRow);
			myDataTable.set("selectionMode","single");
			myDataTable.subscribe("rowClickEvent", myDataTable.onEventSelectRow);
			
			myDataTable.subscribe('rowClickEvent',function(ev) {
				var target = YAHOO.util.Event.getTarget(ev);
				var record = this.getRecord(target);
				selectedID = record.getData('id');
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
