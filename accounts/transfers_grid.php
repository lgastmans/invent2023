<?php 
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");	
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	
	$int_access_level = (getModuleAccessLevel('Accounts'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
	
	/*
		CUSTOM FIELDS
	*/
	$_SESSION['int_accounts_selected'] = 2;
	
	$grid_name = 'fsaccounts_transfers';
	$default_alias = 'tr';
	if (IsSet($_POST['cur_alias']))
		$default_alias = $_POST['cur_alias'];
	$user_id = $_SESSION['int_user_id'];
	
	
	$arr_fields =
		array (
			0 => array (
				'field' => 'transfer_id',
				'yui_field' => 'transfer_id',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'Y',
				'alias' => 'tr'
			),
			1 => array (
				'field' => 'date_created',
				'yui_field' => 'date_created',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			2 => array (
				'field' => 'account_from',
				'yui_field' => 'account_from',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			3 => array (
				'field' => 'account_name',
				'yui_field' => 'account_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ac'
			),
			4 => array (
				'field' => 'account_to',
				'yui_field' => 'account_to',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			5 => array (
				'field' => 'date_completed',
				'yui_field' => 'date_completed',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			6 => array (
				'field' => 'description',
				'yui_field' => 'description',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			7 => array (
				'field' => 'amount',
				'yui_field' => 'amount',
				'formatter' => 'currency',
				'is_custom_formatter' => 'N',
				'parser' => 'currency',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			8 => array (
				'field' => 'transfer_status',
				'yui_field' => 'transfer_status',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseStatus',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'tr'
			),
			9 => array (
				'field' => 'username',
				'yui_field' => 'username',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'u'
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
		DAY FILTER
	*/
	function get_mysql_date($int_day, $int_month, $int_year) {
		$str_retval = $int_year."-".sprintf("%02d", $int_month)."-".sprintf("%02d", $int_day);
		return $str_retval;
	}

	$int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
	$arr_days = array();
	for ($i=1;$i<=$int_num_days;$i++) {
		$arr_days[$i] = get_mysql_date($i, $_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
	}
	
	$date_created = '_ALL';
	if (IsSet($_POST['filter_date']))
		$date_created = $_POST['filter_date'];

	/*
		this file contains the filter initializations
	*/
	include("yui_filter_session.inc.php");
	
	/*
		STATUS FILTER
	*/
	$int_status = '_ALL';
	if (IsSet($_POST['filter_status']))
		$int_status = $_POST['filter_status'];


/*
	$_SESSION['str_transfer_message'] = 'test';

	if (IsSet($_GET['action'])) {

		if ($_GET['action'] == 'flag_no_funds') {
			
			$str = "
				SELECT *
				FROM ".Monthalize('account_transfers')."
				WHERE (transfer_status = ".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS.") OR (transfer_status = ".ACCOUNT_TRANSFER_ERROR.")";
			$qry_update = new Query($str);
			$int_records = $qry_update->RowCount();
			
			$qry_update = new Query("
				UPDATE ".Monthalize('account_transfers')."
				SET transfer_status = ".ACCOUNT_TRANSFER_PENDING."
				WHERE (transfer_status = ".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS.") OR (transfer_status = ".ACCOUNT_TRANSFER_ERROR.")"
			);

			
			if ($qry_update->b_error == true) {
				$_SESSION['str_transfer_message'] = "Error updating the status of ".$int_records." transfers";
			}
			else {
				$_SESSION['str_transfer_message'] = "Updated the status to 'Pending' of ".$int_records." transfers successfully";
			}

		}
	}	
*/
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
			}
		*/
		</style>

		<script language="javascript">
			/*
				CUSTOM FIELDS
			*/
			var SQL = "SELECT tr.*, u.username, ac.account_name FROM  <? echo Monthalize('account_transfers') ?> tr INNER JOIN user u ON u.user_id = tr.user_id INNER JOIN account_cc ac ON (ac.cc_id = tr.cc_id_from)";
			var ID = "transfer_id";
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
				
				var oFilterStatus = document.getElementById('filter_status');
				var oSelectStatus = document.getElementById('select_status');
				oFilterStatus.value = oSelectStatus.value;

				var oFilterDate = document.getElementById('filter_date');
				var oSelectDate = document.getElementById('select_date');
				oFilterDate.value = oSelectDate.value;
				
				document.forms[0].submit();
			}
			
			function editRecord() {
				if (selectedID > 0) {
					myWin = window.open("viewaccount.php?id="+selectedID, 'edit', 'width=800,height=700,resizable=yes');
					myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
					myWin.focus();
				}
				else
					alert('select a currency to edit');
			}
			
			function newRecord() {
				myWin = window.open("viewaccount.php",'insert','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=800,height=700,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
				myWin.focus();
			}
			
			function changeStatus() {
				if (selectedID > 0) {
					myWin = window.open("transfer_status.php?id="+selectedID,'transfer_status','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=no,width=500,height=400,top=0,left=0');
					myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 400/2));
					myWin.focus();
				}
				else
					alert("Select a transfer");
			}
			
			function changeNoFunds() {

				if (confirm("All transfers marked 'Insufficient Funds' or 'Error' will be reset to 'Pending'. Are you sure ?")) {

					if (document.location.href.indexOf("?")<0) {
						url = document.location.href+"?action=flag_no_funds";
					} else {
						url = document.location.href+"&action=flag_no_funds";
					}

					document.location = url;
				}
			}
			
			
			function getFilters() {

				var oSelectStatus = document.getElementById('select_status');
				
				if (oSelectStatus.value == '_ZERO')
					strRetVal = 'transfer_status,0';
				else if (oSelectStatus.value == '_ALL')
					strRetVal = '';
				else
					strRetVal = 'transfer_status,'+oSelectStatus.value;
				strRetval = strRetVal+'|';
				
				return strRetVal;
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
					"&additional_filters="+getFilters();
				//console.log(strURL);
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
		<td><?php
			/*
			if ($int_access_level > ACCESS_READ) {
				echo "&nbsp;<a href=\"javascript:newRecord();\"><img src=\"../images/page_white_add.png\" border=\"0\" title=\"add a new currency\"></a>\n";
				echo "&nbsp;<a href=\"javascript:editRecord();\"><img src=\"../images/page_white_edit.png\" border=\"0\" title=\"edit the details of the selected currency\"></a>\n";
			}
			*/
			if ($int_access_level > ACCESS_READ) {
				if ($int_access_level==ACCESS_ADMIN) {
					echo "&nbsp;<a href=\"javascript:changeStatus();\"><img src=\"../images/transmit_edit.png\" border=\"0\" title=\"Change the status of the transfer\"></a>\n";
					echo "&nbsp;<a id='btn_change_status' href=\"#\"><img src=\"../images/transmit_go.png\" border=\"0\" title=\"Flag all No Funds transfers as Pending\"></a>\n";
				}
			}
			
		?>
			&nbsp;
			<font class="normaltext">Status:</font>
			<select id="select_status" name="select_status" onchange="javascript:refreshPage()">
				<option value="_ALL" <?php if ($int_status=='_ALL') echo "selected";?>>All</option>
				<option value="_ZERO" <?php if ($int_status=='_ZERO') echo "selected";?>>Pending</option>
				<option value="1" <?php if ($int_status==1) echo "selected";?>>No Funds</option>
				<option value="2" <?php if ($int_status==2) echo "selected";?>>Error</option>
				<option value="3" <?php if ($int_status==3) echo "selected";?>>Cancelled</option>
				<option value="4" <?php if ($int_status==4) echo "selected";?>>Hold</option>
				<option value="5" <?php if ($int_status==5) echo "selected";?>>Complete</option>
				<option value="6" <?php if ($int_status==6) echo "selected";?>>Review</option>
			</select>
			&nbsp;
			<font class="normaltext">Day:</font>
			<select id="select_date" name="select_date" onchange="javascript:refreshPage()">
				<option value="_ALL" <?php if ($date_created=='_ALL') echo "selected";?>>All</option>
				<?php
					foreach ($arr_days as $key=>$value) {
						if ($value == $date_created)
							echo "<option value=\"$value\" selected>$key</option>\n";
						else
							echo "<option value=\"$value\">$key</option>\n";
					}
				?>
			</select>
			&nbsp;
			<a href="javascript:printGrid('Y');"><img src="../images/printer.png" border="0" title="print the content of the grid"></a>
			&nbsp;
			<a href="javascript:printGrid('CSV');"><img src="../images/csv_export.png" border="0" title="export the content to a CSV file (tab delimited)"></a>
		</td>
		<td>
			<span id='sum_total'>Total</span>
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
			<input type="hidden" id="filter_status" name="filter_status" value="_ALL">
			<input type="hidden" id="filter_date" name="filter_date" value="_ALL">
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

<script src="../include/js/jquery-3.2.1.min.js"></script>

<script type="text/javascript">

	var selectedID;
	var sum_total;

	YAHOO.util.Event.addListener(window, "load", function() {
/*		var myBody = document.getElementById('myBody');
		myBody.className = myBody.className + ' ' + browserType();*/
		
		myGrid = function() {
			
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('field');
			var oSelectMode = document.getElementById('filter_mode');
			var oDefaultSort = '<?echo $str_sort;?>';
			
			function getFilters() {

				var oSelectStatus = document.getElementById('select_status');
				if (oSelectStatus.value == '_ZERO')
					strRetVal = 'transfer_status,0|';
				else if (oSelectStatus.value == '_ALL')
					strRetVal = '';
				else
					strRetVal = 'transfer_status,'+oSelectStatus.value+'|';

				var oSelectDate = document.getElementById('select_date');
				if (oSelectDate.value == '_ALL')
					strRetVal += '';
				else
					strRetVal += 'date_created,'+oSelectDate.value+'|';


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
			
			parseStatus = function(oData) {
				switch (oData) {
					case '0': return "Pending";break;
					case '1': return "No Funds";break;
					case '2': return "Error";break;
					case '3': return "Cancelled";break;
					case '4': return "Holding";break;
					case '5': return "Complete";break;
					case '6': return "Review";break;
					default: return "Other";
				}
			}
			
			
			myDataSource.responseSchema = {
				resultsList: "records",
				fields: <? get_grid_schema($grid_name, $user_id);?>,
				metaFields: {
					totalRecords: "totalRecords",
					sumTotal: "sumTotal"
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
					"&sum=amount" +
					"&additional_filters="+getFilters();
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
					"&sum=amount" +
					"&additional_filters="+getFilters(),
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
				
				myWin = window.open("viewaccount.php?action=view&id="+selectedID,'tax_grid_details','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=600');
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
				//alert(selectedID);
			});
			
			// Update totalRecords on the fly with value from server
			myDataTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
				oPayload.totalRecords = oResponse.meta.totalRecords;
				
				sum_total = oResponse.meta.sumTotal;

				$('#sum_total').html('Amount Total: Rs ' + sum_total );

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


<script src="../include/js/jquery-3.2.1.min.js"></script>


<script language="javascript">

	$(document).ready(function(){

		$(" #btn_change_status ").click(function() {

			if (confirm("All transfers marked 'Insufficient Funds' or 'Error' will be reset to 'Pending'.\nAre you sure ?")) {

				$.ajax({
					method 	: "POST",
					url		: "transfers_update_status.php",
					data 	: {product_code:$(this).val()}
				})
				.done( function( msg ) {

					obj = $.parseJSON(msg);

					alert(obj.msg);				

				});
			}
		})

	});	

</script>


</body>
</html>

