<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_filter = '';
	if (IsSet($_GET['filter']))
		$str_filter = $_GET['filter'];
	
	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'del') {
			$int_id = 0;
			if (IsSet($_GET['id']))
				$int_id = $_GET['id'];
			
			if ($int_id > 0) {
				$str_query = "
					DELETE FROM tbl_news
					WHERE news_id = $int_id
					LIMIT 1
				";
				$qry = new Query($str_query);
			}
		}
	}
	
?>
<html>
	<head>
		<title>products</title>
		
		<!-- Dependencies -->
		<script type="text/javascript" src="../yui/build/yahoo-dom-event/yahoo-dom-event.js"></script>
		<script type="text/javascript" src="../yui/build/element/element-beta-min.js"></script>
		<script type="text/javascript" src="../yui/build/datasource/datasource-beta-min.js"></script>
		<!-- OPTIONAL: JSON Utility -->
		<script type="text/javascript" src="../yui/build/json/json-min.js"></script>
		<!-- OPTIONAL: Connection (enables XHR) -->
		<script type="text/javascript" src="../yui/build/connection/connection-min.js"></script>
		<!-- OPTIONAL: Drag Drop (enables resizeable or reorderable columns) -->
		<script type="text/javascript" src="../yui/build/dragdrop/dragdrop-min.js"></script>
		<!-- OPTIONAL: Calendar (enables calendar editors) -->
		<script type="text/javascript" src="../yui/build/calendar/calendar-min.js"></script>
		<!-- Source files -->
		<script type="text/javascript" src="../yui/build/datatable/datatable-beta-min.js"></script>
		<script type="text/javascript" src="../yui/build/yuiloader/yuiloader-beta-min.js" ></script> 
		<script type="text/javascript" src="../yui/build/selector/selector-beta-min.js" ></script> 
		<script type="text/javascript" src="../yui/build/dom/dom-min.js" ></script>
		<script type="text/javascript" src="../yui/build/yahoo/yahoo-min.js" ></script>
		
		
		<!-- CSS -->
		<link type="text/css" rel="stylesheet" href="../yui/build/datatable/assets/skins/sam/datatable.css">
		<link type="text/css" rel="stylesheet" href="../yui/build/reset-fonts-grids/reset-fonts-grids.css">
		
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
	
	<body class="yui-skin-sam" id="body_bgcolor">
	
	<table width='100%' height='100%' border="1">
		<tr align='center' valign='top'>
			<td width='100%' height='100%' align="center">
				<div id="tableContainer"></div>
			</td>
		</tr>
	</table>
	
	<script type="text/javascript">

		var selectedID;
		var hideColumnName = 'product_id';
		var myDataTable;
		var totalRecs = 0;
		
		(function () {
		
			var oTextFilter = parent.frames['menu'].document.getElementById('filter');
			var oSelectField = parent.frames['menu'].document.getElementById('select_field');
			var oSelectView = parent.frames['menu'].document.getElementById('select_view');
			
			var loader = new YAHOO.util.YUILoader();
			loader.loadOptional = true;
			loader.insert({
				onSuccess: function() {
					//var myDataTable;
						
					var strURL = 'json_products.php?';
					var myDataSource = new YAHOO.util.DataSource(strURL,{
						responseType:YAHOO.util.DataSource.TYPE_JSON
					});
					
					myDataSource.doBeforeParseData = function  (oRequest, oFullResponse) {
						if (oFullResponse.meta) {
							var f = [];
							var len = oFullResponse.meta.length;
							for(var i = 0; i < len; i++) {
								var c = oFullResponse.meta[i];
								
								myDataTable.insertColumn(c,i);
								
								switch (c.type) {
									case 'number':
										f.push({key:c.key,parser:YAHOO.util.DataSource.parseNumber});
										break;
									default:
										f.push(c.key);
								}
							}
							this.responseSchema = {
								resultsList: 'records',
								fields: f,
								metaFields: {
									totalRecords: "totalRecords"
								}
							};
							
							myDataTable.removeColumn(myDataTable.getColumn(len));
						}
						
						var oColumn = myDataTable.getColumn(hideColumnName);
						myDataTable.hideColumn(oColumn);
						
						totalRecs = oFullResponse.totalRecords;
						
						return oFullResponse;
					};
					
					myDataSource.doBeforeCallback = function(oRequest, oFullResponse, oParsedResponse) {
						oParsedResponse.totalRecords = parseInt(oFullResponse.totalRecords,10);
						return oParsedResponse;
					};
					
					myDataTable = new YAHOO.widget.DataTable(
						'tableContainer',
						[{key:'this_is_just_a_fake_column_key_to_keep_the_system_happy',label:' '}],
						myDataSource,
						{
							initialRequest: 'startIndex=0&results=20&meta=true'+
								'&filter='+oTextFilter.value+
								'&field='+oSelectField.value+
								'&view='+oSelectView.value,
							paginated: true,
							paginator: new YAHOO.widget.Paginator({
								rowsPerPage:20,
								rowsPerPageOptions : [10,15,20],
								template : "{FirstPageLink} {PreviousPageLink} {PageLinks} {NextPageLink} {LastPageLink} Show {RowsPerPageDropdown} per page",
								firstPageLinkLabel : "<img src='../images/resultset_first.png' border='0'>",
								lastPageLinkLabel : "<img src='../images/resultset_last.png' border='0'>",
								previousPageLinkLabel : "<img src='../images/resultset_previous.png' border='0'>",
								nextPageLinkLabel : "<img src='../images/resultset_next.png' border='0'>"
								/*,
								pageLinks : YAHOO.widget.Paginator.VALUE_UNLIMITED,
								
								pageLabelBuilder : function (page,paginator) {
									var recs = paginator.getPageRecords(page);
									return (recs[0] + 1) + ' - ' + (recs[1] + 1);
								}
								*/
							}),
							
							paginationEventHandler: YAHOO.widget.DataTable.handleDataSourcePagination,
							
							generateRequest: function (oData, oDataTable) {
								var newRequest = 'filter='+oTextFilter.value+
									'&field='+oSelectField.value+
									'&view='+oSelectView.value+
									'&startIndex=' + oData.pagination.recordOffset +
									'&results=' + oData.pagination.rowsPerPage;
								var sortedBy = oDataTable.get('sortedBy');
								
								if (sortedBy) {
									newRequest += '&sort=' + sortedBy.key + 
										'&dir=' + sortedBy.dir.replace('yui-dt-','');
								}
								return newRequest;
							}
						}
					);
					
					myDataTable.set("selectionMode","single");
					myDataTable.subscribe("rowClickEvent", myDataTable.onEventSelectRow);
					
					myDataTable.subscribe('rowClickEvent',function(ev) {
						var target = YAHOO.util.Event.getTarget(ev);
						var record = this.getRecord(target);
						selectedID = record.getData('product_id');
						//alert(selectedID);
					});
					
					myDataTable.subscribe("rowMouseoverEvent", myDataTable.onEventHighlightRow);
					myDataTable.subscribe("rowMouseoutEvent", myDataTable.onEventUnhighlightRow);
					
					myDataTable.sortColumn = function(oColumn, sDir) {
						if(oColumn && (oColumn instanceof YAHOO.widget.Column)) {
							if(!oColumn.sortable) {
								YAHOO.util.Dom.addClass(this.getThEl(oColumn), YAHOO.widget.DataTable.CLASS_SORTABLE);
							}
							
							if(sDir && (sDir !== YAHOO.widget.DataTable.CLASS_ASC) && (sDir !== YAHOO.widget.DataTable.CLASS_DESC)) {
								sDir = null;
							}
							
							var sortDir = sDir || this.getColumnSortDir(oColumn);
		
							var oSortedBy = this.get("sortedBy") || {};
							
							if (!(oSortedBy.key === oColumn.key && oSortedBy.dir === sortDir)) {
							
								this.initializeTable();
								
								this.set("sortedBy", {key:oColumn.key, dir:sortDir, column:oColumn});
								
								var oPaginator = this.get('paginator');
								oPaginator.fireEvent('changeRequest',oPaginator.getState({'page':1}));
								
								this.render();
								
								this.fireEvent("columnSortEvent",{column:oColumn,dir:sortDir});
							}
						}
					};
				}
			});
		})();
		
	</script>
</body>
</html>
