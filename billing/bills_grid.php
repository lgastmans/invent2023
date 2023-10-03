<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("json_grid_fields.php");
	
	$int_grid_rows = $arr_invent_config['settings']['grid_rows'];
	$int_decimals = $arr_invent_config['settings']['decimals'];
	$int_currency_decimals = $arr_invent_config['settings']['currency_decimals'];
	$print_filename = $arr_invent_config['billing']['print_filename'];
	if (!$print_filename)
		$print_filename='print_bill.php';	

	$int_access_level = (getModuleAccessLevel('Billing'));

	$_SESSION["int_bills_menu_selected"] = 1;

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}
	$can_delete = false;
	if ($int_access_level == ACCESS_ADMIN)
	   $can_delete = true;
	
	/*
		CUSTOM FIELDS
	*/
	$grid_name = 'billing_bills';
	$default_alias = 'b';
	if (IsSet($_POST['cur_alias']))
		$default_alias = $_POST['cur_alias'];
	$user_id = $_SESSION['int_user_id'];
	
	$arr_fields =
		array (
			0 => array (
				'field' => 'bill_id',
				'yui_field' => 'bill_id',
				'formatter' => 'number',
				'is_custom_formatter' => 'N',
				'parser' => 'number',
				'filter' => 'N',
				'is_primary_key' => 'Y',
				'alias' => 'b'
			),
			1 => array (
				'field' => 'bill_number',
				'yui_field' => 'bill_number',
				'formatter' => 'number',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseBillNumber',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			2 => array (
				'field' => 'date_created',
				'yui_field' => 'date_created',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			3 => array (
				'field' => 'account_number',
				'yui_field' => 'account_number',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			4 => array (
				'field' => 'account_name',
				'yui_field' => 'account_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			5 => array (
				'field' => 'total_amount',
				'yui_field' => 'total_amount',
				'formatter' => 'currency',
				'is_custom_formatter' => 'N',
				'parser' => 'currency',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			6 => array (
				'field' => 'payment_type',
				'yui_field' => 'payment_type',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parsePaymentType',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			7 => array (
				'field' => 'payment_type_number',
				'yui_field' => 'payment_type_number',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			8 => array (
				'field' => 'bill_promotion',
				'yui_field' => 'bill_promotion',
				'formatter' => 'currency',
				'is_custom_formatter' => 'N',
				'parser' => 'currency',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			9 => array (
				'field' => 'bill_status',
				'yui_field' => 'bill_status',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseBillStatus',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			10 => array (
				'field' => 'resolved_on',
				'yui_field' => 'resolved_on',
				'formatter' => 'datetime',
				'is_custom_formatter' => 'N',
				'parser' => 'datetime',
				'filter' => 'N',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			11 => array (
				'field' => 'username',
				'yui_field' => 'username',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'u'
			),
			12 => array (
				'field' => 'is_debit_bill',
				'yui_field' => 'is_debit_bill',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseDebitBill',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			13 => array (
				'field' => 'aurocard_number',
				'yui_field' => 'aurocard_number',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			14 => array (
				'field' => 'aurocard_transaction_id',
				'yui_field' => 'aurocard_transaction_id',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			15 => array (
				'field' => 'card_name',
				'yui_field' => 'card_name',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			16 => array (
				'field' => 'card_number',
				'yui_field' => 'card_number',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			17 => array (
				'field' => 'cancelled_reason',
				'yui_field' => 'cancelled_reason',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			18 => array (
				'field' => 'table_ref',
				'yui_field' => 'table_ref',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			19 => array (
				'field' => 'is_draft',
				'yui_field' => 'is_draft',
				'formatter' => 'string',
				'is_custom_formatter' => 'Y',
				'parser' => 'parseDraftBill',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'b'
			),
			20 => array (
				'field' => 'company',
				'yui_field' => 'company',
				'formatter' => 'string',
				'is_custom_formatter' => 'N',
				'parser' => 'string',
				'filter' => 'Y',
				'is_primary_key' => 'N',
				'alias' => 'c'
			)
		);
	/*
		END CUSTOM FIELDS
	*/
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
		BILL TYPE FILTER
	*/
	// get which types that can be billed
	$sql = "
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account, can_bill_aurocard, can_bill_creditcard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")";
	$qry =& $conn->query($sql);
	$obj = mysqli_fetch_object($qry);

	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_transfer = false;
	$bool_aurocard = false;
	$bool_creditcard = false;
	$bool_upi = true;

	if ($obj->can_bill_cash == 'Y') {
		$bool_cash = true;
	}
	if ($obj->can_bill_fs_account == 'Y') {
		$bool_fs = true;
	}
	if ($obj->can_bill_pt_account == 'Y') {
		$bool_pt = true;
	}
	if ($obj->can_bill_aurocard == 'Y') {
		$bool_aurocard = true;
	}
	if ($obj->can_bill_creditcard == 'Y') {
		$bool_creditcard = true;
	}
	
	$arr_filter_type = array();
	if ($bool_cash == true)
		$arr_filter_type['Cash'] = 1;
	if ($bool_fs == true)
		$arr_filter_type['FS Account'] = 2;
	if ($bool_pt == true)
		$arr_filter_type['PT Account'] = 3;
	if (CAN_BILL_TRANSFER_GOOD === 1)
		$arr_filter_type['Transfer of Goods'] = 6;
	if ($bool_aurocard == true)
		$arr_filter_type['Aurocard'] = 7;
	if ($bool_creditcard == true)
		$arr_filter_type['Credit Card'] = 4;
	$arr_filter_type['UPI'] = 8;

		
	$bill_type = '_ALL';
	if (IsSet($_POST['filter_type']))
		$bill_type = $_POST['filter_type'];

	$draft_filter = 'All';
	if (IsSet($_POST['toggle_draft']))
		$draft_filter = $_POST['toggle_draft'];

	/*
		CANCEL BILL
	*/
	if (IsSet($_GET["action"])) {
		if ($_GET["action"]=="del") {
			require("bill_cancel.php");
			
			$str_retval = cancelBill($_GET["delid"], $_GET['reason'], 2); // 2 = bill module id

			$arr_retval = explode('|', $str_retval);
			$_SESSION['str_bill_cancel_message'] = $arr_retval[1];
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
			
			/* Class for marked rows */
			.yui-skin-sam .yui-dt tr.mark,
			.yui-skin-sam .yui-dt tr.mark td.yui-dt-asc,
			.yui-skin-sam .yui-dt tr.mark td.yui-dt-desc,
			.yui-skin-sam .yui-dt tr.mark td.yui-dt-asc,
			.yui-skin-sam .yui-dt tr.mark td.yui-dt-desc {
				background-color: #a33;
				color: #fff;
			}
			
		</style>
		
		<script language="javascript">
			/*
				CUSTOM FIELDS
			*/
			var SQL = "SELECT b.*, u.username, c.company FROM  <?php echo Monthalize('bill');?> b INNER JOIN user u ON (u.user_id = b.user_id) LEFT JOIN customer c ON (c.id = b.customer_id)";
			var ID = "bill_id";
			var uniqueFilter = "b.storeroom_id = <?php echo $_SESSION['int_current_storeroom']?>";
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
				
				var oHiddenDir = document.getElementById('dir');
				oHiddenDir.value = oSort.dir;
				
				var oHiddenAlias = document.getElementById('cur_alias');
				var oCol = myGrid.oDT.getColumn(oSort.key);
				oHiddenAlias.value = oCol.alias;
				
				var oFilterDate = document.getElementById('filter_date');
				var oSelectDate = document.getElementById('select_date');
				oFilterDate.value = oSelectDate.value;
				
				var oFilterType = document.getElementById('filter_type');
				var oSelectType = document.getElementById('select_type');
				oFilterType.value = oSelectType.value;
				//alert(oFilterType.value +':'+ oSelectType.value);
				
				document.client_grid.submit();
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
			
			function newBill() {
				myWin = window.open("billing_frameset.php?action=clear_bill",'create_bill','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes');
				myWin.moveTo(0,0);
				myWin.focus();
			}
			
			function newBill2() {
				myBill = window.open("billing.php?action=clear_bill",'billing','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes');
				myBill.moveTo(0,0);
				myBill.focus();
			}

			function editDraft() {
				
				if (selectedID > 0) {

					$.ajax({
						method 	: "POST",
						url 	: "data/load_bill.php",
						data 	: { "action" : "is_draft", "bill_id": selectedID }
					})
					.done ( function( msg ) {

						var obj = JSON.parse( msg );
						
						console.log(obj.data['is_draft']);

						if (obj.data['is_draft']==true) {
							myBill = window.open("billing.php?action=edit_draft&draftid="+selectedID,'billing','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes');
							myBill.moveTo(0,0);
							myBill.focus();
						}
						else
							alert("Select a draft bill");

					});

				}
				else
					alert("Select a draft bill");
			}

			function changeBillType() {
				if (selectedID > 0) {
					console.log('bill id ', selectedID);
					myBill = window.open("bill_type.php?bill_id="+selectedID,'bill_type','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=800,height=600');
					myBill.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
					myBill.focus();
				}
				else
					alert('Select a bill');
			}

			function debitBill() {
				myWin = window.open("debit/billing_frameset.php?action=clear_bill",'draft_bill','toolbar=no,location=no,directories=no,status=yes,fullscreen=no,menubar=no,scrollbars=yes,resizable=yes');
				myWin.MoveTo(0,0);
				myWin.focus();
			}
			
			function cancelBill() {

				if (selectedID > 0) {

					$.ajax({
						method 	: "POST",
						url 	: "data/load_bill.php",
						data 	: { "action" : "is_draft", "bill_id": selectedID }
					})
					.done ( function( msg ) {

						var obj = JSON.parse( msg );
						
						if (obj.data['is_draft']==true) {

							if (confirm("Delete draft bill?")) {

								console.log('delete draft');

								$.ajax({
									method 	: "POST",
									url 	: "data/load_bill.php",
									data 	: { "action" : "cancel", "bill_id": selectedID }
								})
								.done ( function( msg2 ) {

									console.log( msg2 );
									location.reload();
									
								});

							}

						}
						else {

							if (confirm("Are you sure you want to cancel this bill?")) {

								var str_reply = prompt("Please give a reason for cancelling the bill", "");

								if (document.location.href.indexOf("?") < 0) {
									document.location = document.location.href+"?action=del&delid="+selectedID+"&reason="+str_reply;
								} else {
									document.location = document.location.href+"&action=del&delid="+selectedID+"&reason="+str_reply;
								}
							}
						}

					});

				}
				else
					alert("Select a bill to cancel");
			}
			
			function printBill() {
				if (selectedID > 0) {
					myWin = window.open("<?php echo $print_filename;?>?id="+selectedID, 'printwin', 'width=800,height=500,resizable=yes');
				}
				else
					alert('Select a bill to print');
			}

			function exportInvoice() {
				if (selectedID > 0) {
					myWin = window.open("export_invoice.php?id="+selectedID, 'genInvoice', 'width=800,height=500,resizable=yes,menubar=yes'); 
				}
				else
					alert('Select a bill to print');
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
				myWin = window.open("../include/yuigridcustomize.php?gridname=<?echo $grid_name?>&viewname=default",'customize','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=500');
				myWin.moveTo((screen.availWidth/2 - 700/2), (screen.availHeight/2 - 500/2));
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
						echo "&nbsp;<a href=\"javascript:newBill();\"><img src=\"../images/page_white_add.png\" border=\"0\" title=\"New Bill\"></a>\n";
						echo "&nbsp;<a href=\"javascript:newBill2();\"><img src=\"../images/page_white_add.png\" border=\"0\" title=\"New Bill\"></a>\n";
						echo "&nbsp;<a href=\"javascript:editDraft();\"><img src=\"../images/page.png\" border=\"0\" title=\"Edit draft\"></a>\n";
						echo "&nbsp;<a href=\"javascript:changeBillType();\"><span class=\"glyphicon glyphicon-transfer\"></span></a>";
						echo "&nbsp;<a href=\"javascript:debitBill();\"><img src=\"../images/page_white_edit.png\" border=\"0\" title=\"Debit (Reverse) Bill\"></a>\n";
						echo "&nbsp;<a href=\"javascript:cancelBill();\"><img src=\"../images/cancel.png\" border=\"0\" title=\"Cancel Bill\"></a>\n";
					}
				}
				echo "&nbsp;<a href=\"javascript:printBill();\"><img src=\"../images/printer.png\" border=\"0\" title=\"Print the selected bill\"></a>\n";
			?>
			&nbsp;
			<a href="javascript:exportInvoice()"><img src="../images/pdf-icon.png" border="0" title="Generate Invoice"></a>
			&nbsp;
			<a href="javascript:printGrid('Y');"><img src="../images/printer.png" border="0" title="print the content of the grid"></a>
			&nbsp;
			<a href="javascript:printGrid('CSV');"><img src="../images/csv_export.png" border="0" title="export the content to a CSV file (tab delimited)"></a>
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
			<font class="normaltext">Type:</font>
			<select id="select_type" name="select_type" onchange="javascript:refreshPage()">
				<option value="_ALL" <?php if ($bill_type=='_ALL') echo "selected";?>>All</option>
				<?php
					foreach ($arr_filter_type as $key=>$value) {
						if ($value == $bill_type)
							echo "<option value=\"$value\" selected>$key</option>\n";
						else
							echo "<option value=\"$value\">$key</option>\n";
					}
				?>
			</select>

			&nbsp;
			<font class="normaltext">View:</font>
			<select id="toggle_draft" name="toggle_draft" onchange="javascript:refreshPage()">
				<option value="_ALL" <?php if ($draft_filter=='_ALL') echo "selected";?>>All</option>
				<option value="_DRAFT" <?php if ($draft_filter=='_DRAFT') echo "selected";?>>Draft</option>
			</select>

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



    <script src="../include/js/jquery-3.2.1.min.js"></script>

	<script>
		$('#myStateButton').on('click', function () {
			$(this).button('complete') // button text will be "finished!"
		});

		$(" #btn-company ").on("click", function(e) {
			$(" #settings_alert_msg ").html( "Company settings saved" );
			$(" #settings_alert ").removeClass( "alert-danger" ).addClass( "alert-info" );
			$(" #settings_alert ").show();
		});
		
	</script>


<script type="text/javascript">



	var selectedID;

	YAHOO.util.Event.addListener(window, "load", function() {
		
		myGrid = function() {
			
			var oTextFilter = document.getElementById('filter');
			var oSelectField = document.getElementById('field');
			var oSelectMode = document.getElementById('filter_mode');
			var oDefaultSort = '<?echo $str_sort;?>';
			<? if ($str_dir == 'ASC') { ?>
				var oDefaultDir = YAHOO.widget.DataTable.CLASS_ASC;
			<? } else { ?>
				var oDefaultDir = YAHOO.widget.DataTable.CLASS_DESC;
			<? } ?>
			
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
					strRetVal += 'b.payment_type,'+oSelectType.value+'|';

				var oSelectDraft = document.getElementById('toggle_draft');
				if (oSelectDraft.value == '_ALL')
					strRetVal += '';
				else
					strRetVal += 'is_draft,1|';
					
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
					case '3': return "<font color='red'>Cancelled</fong>";break;
					case '4': return "Holding";break;
					case '5': return "Complete";break;
					case '6': return "Review";break;
					default: return "Other";
				}
			}
			
			parseBillNumber = function(oData) {
				/*
					if debit bill mark red
				*/
				return oData;
			}
			
			parseDebitBill = function(oData) {
				return oData;
				/*
				if (oData == 'Y')
					return "<font color='red'>Yes</font>";
				else
					return "No";
				*/
			}

			parseDraftBill = function(oData) {
				if (oData==true)
					return "Yes";
				else
					return "";
			}
			
			parseBillStatus = function(oData) {
				switch (oData) {
					case '1': return 'Unresolved';break;
					case '2': return 'Resolved';break;
					case '3': return '<font color="red">Cancelled</font>';break;
					case '4': return 'Processing';break;
					case '5': return 'Dispatched';break;
					case '6': return 'Delivered';break;
					default: return "unknown";
				}
			}
			
			parsePaymentType = function(oData) {
				switch (oData) {
					case '<?php echo BILL_CASH?>': return 'Cash';break;
					case '<?php echo BILL_ACCOUNT?>': return 'Account';break;
					case '<?php echo BILL_PT_ACCOUNT?>': return 'PT Account';break;
					case '<?php echo BILL_CREDIT_CARD?>': return 'Credit Card';break;
					case '<?php echo BILL_CHEQUE?>': return 'Cheque';break;
					case '<?php echo BILL_TRANSFER_GOOD?>': return 'Transfer of Goods';break;
					case '<?php echo BILL_AUROCARD?>': return 'Aurocard';break;
					case '<?php echo BILL_UPI?>': return 'UPI';break;
					default: return "unknown";
				}
			}
			
			var myRowFormatter = function(elTr, oRecord) {
				if (oRecord.getData('is_debit_bill') == 'Y') {
					YAHOO.util.Dom.addClass(elTr, 'mark');
				}
				return true;
			};
			
			
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
					"&uniqueFilter="+uniqueFilter+
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
					"&dir=<?echo $str_dir?>"+
					"&startIndex=0"+
					"&results=20"+
					"&filter="+oTextFilter.value+
					"&field="+oSelectField.value+
					"&mode="+oSelectMode.value+
					"&uniqueFilter="+uniqueFilter+
					"&additional_filters="+getFilters(),
				dynamicData: true,
				sortedBy : {key:oDefaultSort, dir:oDefaultDir},
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
				},
				formatRow: myRowFormatter
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
				
				myWin = window.open("bills_grid_details.php?action=view&id="+selectedID,'bills_grid_details','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=600');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
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
