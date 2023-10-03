<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	
	$int_access_level = (getModuleAccessLevel('Stock'));
	
	$_SESSION["int_purchase_menu_selected"] = 7;
	
	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
	
	/*
		CUSTOM FIELDS
	*/
	$grid_name = 'purchase_statement';
	$default_alias = 'po';
	if (IsSet($_POST['cur_alias']))
		$default_alias = $_POST['cur_alias'];
	$user_id = $_SESSION['int_user_id'];
	
	$arr_fields =
		array (
			0 => array (
				'field' => 'purchase_order_id',
				'yui_field' => 'purchase_order_id',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'Y',
				'alias' => 'po'
			),
			1 => array (
				'field' => 'date_received',
				'yui_field' => 'date_received',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'po'
			),
			2 => array (
				'field' => 'purchase_order_ref',
				'yui_field' => 'purchase_order_ref',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'po'
			),
			3 => array (
				'field' => 'supplier_name',
				'yui_field' => 'supplier_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'ss'
			),
			4 => array (
				'field' => 'invoice_number',
				'yui_field' => 'invoice_number',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'po'
			),
			5 => array (
				'field' => 'invoice_date',
				'yui_field' => 'invoice_date',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'po'
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
			require("purchase_order_delete.php");
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
		
		<!-- personalized style sheet -->
		<link rel="stylesheet" type="text/css" href="../include/styles.css">
		<link rel="stylesheet" type="text/css" href="../include/yui_grid_styles.php">



		<link rel="stylesheet" type="text/css" href="../include/datatables/datatables.min.css"/>
	    <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">



		
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

			#details_grid {
				background-color: lightgrey;
				border-radius: 7px;
				padding: 50px;
				margin-left:100px;
				margin-right:100px;
			}

			td.details-control {
			    background: url('../images/resultset_next.png') no-repeat center center;
			    cursor: pointer;
			}
			tr.shown td.details-control {
			    background: url('../images/cross.png') no-repeat center center;
			}

			#extra-table tr {
				border-top: none;
				padding-top: 0px;
				padding-bottom:	0px;
				margin-top:	0px;
				margin-bottom: 0px;
				font-size: 11px;
				font-style: normal;

			}

		</style>

		<script language="javascript">
			/*
				CUSTOM FIELDS
			*/
			var SQL = "SELECT * "+
				"FROM <?php echo Yearalize('purchase_order');?> po "+
            	"LEFT JOIN stock_supplier ss ON (ss.supplier_id = po.supplier_id) ";
			var ID = "purchase_order_id";
			var uniqueFilter = "";
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

			function exportOrder() {
				if (selectedID > 0) {
					myWin = window.open("export_order.php?id="+selectedID, 'genOrderPDF', 'width=800,height=500,resizable=yes,menubar=yes'); 
				}
				else
					alert('Select a purchase order');
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

<table width="100%" height="30px" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					if ($int_access_level > ACCESS_READ) {
						if ($int_access_level==ACCESS_ADMIN) {
						}
					}
				}
			?>
			&nbsp;
			<a href="javascript:exportOrder();"><img src="../images/pdf-icon.png" border="0" title="view the purchase order details"></a>
			&nbsp;
			<a href="javascript:printGrid('Y');"><img src="../images/printer.png" border="0" title="print the content of the grid"></a>
			&nbsp;
			<a href="javascript:printGrid('CSV');"><img src="../images/csv_export.png" border="0" title="export the content to a CSV file (tab delimited)"></a>
			&nbsp;
			<button type="button" id="btn-return" class="btn btn-primary btn-xs" name="button_return">Return to supplier</button>
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


  	<!--
  		details grid
  	-->
  	<div id="details_grid">

		<table id="grid-products" class="table table-striped table-condensed " cellspacing="0" width="100%">
	        <thead>
	            <tr>
	            	<th>id</th>
	            	<th></th>
	                <th>code</th>
 	                <th>batch</th>
	                <th>description</th>
	                <th>supplier</th>
	                <th>quantity</th>
	                <th>sold</th>
	                <th>balance</th>
	                <th>price</th>
	            </tr>
	        </thead>
	    </table>
	    <span id="table_details"></span>
  	</div> <!-- billing grid -->



    <script src="../include/js/jquery-3.2.1.min.js"></script>

	<script type="text/javascript" src="../include/datatables/datatables.min.js"></script>

<script type="text/javascript">

	var selectedID;
	var billTable;

	YAHOO.util.Event.addListener(window, "load", function() {
/*		var myBody = document.getElementById('myBody');
		myBody.className = myBody.className + ' ' + browserType();*/
		
		myGrid = function(billTable) {
			
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
				
				//var test = $("grid-products").Datatable();

				console.log('selected PO Id: ' + billTable);

				billTable.ajax.reload();

			});
			
			// Update totalRecords on the fly with value from server
			myDataTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
				oPayload.totalRecords = oResponse.meta.totalRecords;
				return oPayload;
			}
			


			/*
				Billing Grid
				https://www.datatables.net/examples/basic_init/scroll_y_dynamic.html
			*/
			var billTable = $('#grid-products').DataTable({
		        scrollY 		: '40vh',
		        scrollCollapse	: true,
		        paging			: false,
		        searching		: false,
		        ajax			: { 
		        	"url"	: "data/update_list.php",
		        	"type"	: "POST",
		        	data 	: function (d) {
		        		d.purchase_order_id = selectedID 
		        	}
		        },
		        columns: [
	                { data: "id", visible: false },
					{
		                "class":          'details-control',
		                "orderable":      false,
		                "data":           null,
		                "defaultContent": ''
		            },
		            { data: "code" },
	                { data: "batch" },
	                { data: "description" },
	                { data: "supplier" },
	                { data: "quantity" },
	                { data: "sold" },
	                { data: "balance" },
	                { data: "price" }
	            ],
				oLanguage		: {
					"sInfo": "", //"_TOTAL_ entries",
					"sInfoEmpty": "No entries",
					"sEmptyTable": "Zero products billed",
				}
		    });

			// Add event listener for opening and closing details
		    $('#grid-products tbody').on('click', 'td.details-control', function () {
		        var tr = $(this).closest('tr');
		        var row = billTable.row( tr );
		 
		        if ( row.child.isShown() ) {
		            // This row is already open - close it
		            row.child.hide();
		            tr.removeClass('shown');
		        }
		        else {
		            // Open this row
		            row.child( format(row.data()) ).show();
		            tr.addClass('shown');
		        }
		    } );

			function format ( d ) {
			    // `d` is the original data object for the row
			    return '<table id="extra-table" cellpadding="0" cellspacing="0" border="0" style="padding-left:50px;">'+
			    	d.extra+
			    '</table>';
			}


			return {
				oDS: myDataSource,
				oDT: myDataTable
			};

		}(); // myGrid



		$(" #btn-return ").on("click", function(e) {

			if (selectedID > 0) {

				if (confirm("Are you sure you want to return this PO ?")) {
					$.ajax({
						method	: "POST",
						url		: "purchase_return.php",
						data 	: { ID: selectedID }
					})
					.done(function( msg ) {

						var obj = JSON.parse( msg );

						console.log( 'msg ' + obj.msg);

						alert(obj.msg);

					});
				}
			}
			else
				alert('Select a Purchase Order');


		});

	});


	// $(document).ready(function(){
	// });


</script>

</form>
</body>
</html>
