<?php
	/*

		billing/
		billing/billing.php

		billing/data/...
	
		include/bootstrap-3.3.4-dist
		include/datatables
		
		include/js/jquery-typeahead-2.10.1
		include/js/accounting.js
		include/js/bootbox.min.js
		include/js/jquery-1.11.1.min.js

	*/


	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../common/tax.php");
	require_once("get_bill_number.php");
	require_once("Config.php");
	require_once("data/load_bill.php");


	/*
		if the page was loaded with the request to load a draft bill
		then load the details
	*/
	$draft_details = array();
	$is_draft_bill = false;
	$draft_bill_id = 0;

	if ((isset($_GET['action'])) && ($_GET['action']=='edit_draft')) {

		$draft_details = load_bill($_GET['draftid']);
		$is_draft_bill = true;
		$draft_bill_id = $_GET['draftid'];

	}


	/*
		Configuration Settings from 
		config.ini file
	*/
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$templateSection = $arrConfig->getItem("section", 'billing');
	/*
		if the "billing" section does not exist in the config.ini file
		set to default values
	*/
	if ($templateSection === false) {
		$str_display_abbreviation = 'Y';
	}
	else {
		$useScale = $templateSection->getItem("directive", "use_scale")->getContent();

		$display_abbreviation_directive =& $templateSection->getItem("directive", "display_abbreviation");
		if ($display_abbreviation_directive === false) {
			$templateSection->createDirective("display_abbreviation", 'Y');
			$display_abbreviation_directive =& $templateSection->getItem("directive", "display_abbreviation");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		$str_display_abbreviation = $display_abbreviation_directive->getContent();

	}

	$print_filename = $arr_invent_config['billing']['print_filename'];
	if (!$print_filename)
		$print_filename='print_bill.php';


	
	/*
		company details
			gstin
	*/
	$result = $conn->query("
		SELECT gstin
		FROM company
	");
	$obj = $result->fetch_object();

	$company_gstin = '';
	if (isset($obj->gstin))
		$company_gstin = $obj->gstin;



	/*
		Configuration Settings from 
		user_settings table
	*/
	$result = $conn->query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$int_decimals = 2;
	$int_default_discount = 10;
	$str_batches_enabled = 'N';
	$str_display_messages = 'N';
	$str_edit_price = 'N';
	$str_adjusted_enabled = 'Y';
	$bill_fs_discount = 0;
	$str_calc_tax_first = 'N'; 
	$low_balance = 0;

	if ($result->num_rows > 0) {

		$obj = $result->fetch_object();

		$int_decimals = $obj->bill_decimal_places;
		$int_default_discount = $obj->bill_default_discount;
		$str_batches_enabled = $obj->bill_enable_batches;
		$str_display_messages = $obj->bill_display_messages;
		$str_edit_price = $obj->bill_edit_price;
		$str_adjusted_enabled = $obj->bill_adjusted_enabled;
		$bill_fs_discount = $obj->bill_fs_discount;
		$low_balance = $obj->bill_fs_low_balance;

		//$str_calc_tax_first = $obj->calculate_tax_before_discount;

	}



	/*
		Configuration Settings from 
		stock_storeroom table
	*/
	$result = $conn->query("
		SELECT *
		FROM stock_storeroom
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$enabled_table_billing = 'N';
	if ($result->num_rows > 0) {
		$obj = $result->fetch_object();

		$enabled_table_billing = $obj->enabled_table_billing;
	}



	/*
		Billing Types

			define('BILL_CASH', 1);
			define('BILL_ACCOUNT', 2);
			define('BILL_PT_ACCOUNT', 3);
			define('BILL_CREDIT_CARD', 4);
			define('BILL_CHEQUE', 5);
			define('BILL_TRANSFER_GOOD', 6);
			define('BILL_AUROCARD', 7);
	*/

	$result = $conn->query("
		SELECT can_bill_cash, can_bill_creditcard, can_bill_fs_account, can_bill_pt_account, can_bill_aurocard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$obj = $result->fetch_object();

	$bill_type = array();
	$bill_type[1]['enabled'] = ($obj->can_bill_cash == 'Y' ? true : false);
	$bill_type[1]['description'] = 'Cash';
	$bill_type[1]['active'] = ($_SESSION['current_bill_type'] == 1 ? true : false);

	$bill_type[2]['enabled'] = ($obj->can_bill_fs_account == 'Y' ? true : false);
	$bill_type[2]['description'] = 'Financial Service';
	$bill_type[2]['active'] = ($_SESSION['current_bill_type'] == 2 ? true : false);

	$bill_type[4]['enabled'] = ($obj->can_bill_creditcard == 'Y' ? true : false);
	$bill_type[4]['description'] = 'Credit Card';
	$bill_type[4]['active'] = ($_SESSION['current_bill_type'] == 4 ? true : false);

	$bill_type[3]['enabled'] = ($obj->can_bill_pt_account == 'Y' ? true : false);
	$bill_type[3]['description'] = 'Pour Tous';
	$bill_type[3]['active'] = ($_SESSION['current_bill_type'] == 3 ? true : false);

	$bill_type[7]['enabled'] = ($obj->can_bill_aurocard == 'Y' ? true : false);
	$bill_type[7]['description'] = 'AuroCard';
	$bill_type[7]['active'] = ($_SESSION['current_bill_type'] == 7 ? true : false);

	$bill_type[8]['enabled'] = 'Y'; //($obj->can_bill_aurocard == 'Y' ? true : false);
	$bill_type[8]['description'] = 'UPI';
	$bill_type[8]['active'] = ($_SESSION['current_bill_type'] == 8 ? true : false);

	$bill_type[9]['enabled'] = 'Y';
	$bill_type[9]['description'] = 'Bank Transfer';
	$bill_type[9]['active'] = ($_SESSION['current_bill_type'] == 9 ? true : false);

	if ((isset($_GET['action'])) && ($_GET['action']=='edit_draft')) {
		set_bill_type($bill_type, $draft_details['data']['bill_type']);
	}


	function current_bill_type($arr) {
		foreach ($arr as $value) {
			if ($value['active'])
				return $value['description'];
		}
	}


	function set_bill_type(&$arr, $val) {

		foreach ($arr as $key=>$value) {
			$arr[$key]['active'] = false;
		}

		$_SESSION['current_bill_type'] = $val;

		$arr[$val]['active'] = true;
	}



	/*
		Bill Number
	*/
	$int_bill_number = get_bill_number_no_update($_SESSION['current_bill_type']);


	/*
		Financial Service Connect Method
			define('CONNECT_OFFLINE_LIMITED_ACCESS',2);
			define('CONNECT_ONLINE',3);
	*/


	/*
		Billing Salespersons
	*/
	$sql = "SELECT * FROM salespersons ORDER BY first";
	$salespersons = $conn->query($sql);

	function current_salesperson($salespersons) {
		while ($obj = $salespersons->fetch_object())
			if ($obj->id == $_SESSION['bill_salesperson'])
				return $obj->first." ".$obj->last;
	}


	/*
		if set, get Client details 
	*/
	$str_client_details1 = '';
	$str_client_details2 = '';
	$str_client_company = '';
	$same_gstin = 'N';

	if ((isset($_SESSION['client_id'])) && (!empty($_SESSION['client_id']))) {

		$sql = "
			SELECT c.id, customer_id, company, address, city, zip, gstin, contact_person, sc.state AS state
			FROM customer c
			LEFT JOIN state_codes sc ON (sc.id = c.state)
			WHERE c.id = '".$_SESSION['client_id']."'
		";
		$customer = $conn->query($sql);
		$obj = $customer->fetch_object();

		$str_client_details1 = $obj->company . '<br>' . $obj->address . '<br>' . $obj->city . ' ' . $obj->zip . '<br>' . $obj->state;
		$str_client_details2 = $obj->gstin . '<br>' . $obj->contact_person;
		$str_client_company = $obj->company;

		if ($obj->gstin == $company_gstin)
			$same_gstin = 'Y';
	}


	
	$arr_storeroom_list = getStoreroomList();


?>


<!DOCTYPE html>

<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Billing</title>

    <!-- Bootstrap -->
    <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="../include/js/jquery-typeahead-2.10.1/dist/jquery.typeahead.min.css" rel="stylesheet">
    <!-- <link href="../include/js/jquery-typeahead-2.10.6/dist/jquery.typeahead.min.css" rel="stylesheet"> -->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

	<link rel="stylesheet" type="text/css" href="../include/datatables/datatables.min.css"/>

    <style>
    	body {
    		margin:5px;
    		/*background-color: lightblue;*/
    	}

		h2 { 
			margin-top: 5px;
			margin-bottom: 5px
		}

		.row-bg {
			background-color: lightgrey;
			border-radius: 7px;
			padding: 7px;
			margin-top: 5px;
			margin-bottom: 5px
		}

		#billing_grid {
			background-color: lightgrey;
			border-radius: 7px;
			padding: 7px;
		}

		.address {
			font-size: 10px;
			font-style: italic;
		}

    	.typeahead__list {
    		min-width:300px !important;
    	}

    	#result-container-description {
    		font-style: normal;
    	}
	</style>

	<script>

		var arr_retval = new Array();
		
		var fs_discount = <?php echo $bill_fs_discount; ?>;
		var bool_is_decimal = false;
		var int_decimals = <?echo $int_decimals;?>;
		var str_batches_enabled = '<? echo $str_batches_enabled; ?>';
		var str_display_messages = '<? echo $str_display_messages; ?>';
		var str_edit_price = '<?echo $str_edit_price; ?>';
		var str_adjusted_enabled = '<?echo $str_adjusted_enabled?>';
		var str_use_scale = '<?echo $useScale?>';
		var flt_previous_qty = 0;
		var can_bill = false;
		var flt_total_batch_quantity = 0;
		var arr_batches = new Array();
		var arr_batch_codes = new Array();
		var arr_batch_ids = new Array();
		var arr_batch_quantities = new Array();
		var arr_batch_prices = new Array();
		var save_clicked = false;
		var display_abbreviation = '<?php echo $str_display_abbreviation; ?>';
		var can_view_status = <? echo DOWNLOAD_ALL; ?>;


	</script>

</head>
<body>

	
	<input type="hidden" id="is_draft_bill" name="is_draft_bill" value="<?php echo $is_draft_bill;?>">


<div class="container">

  	<!--
  		BILLING TYPE
  	-->
  	<div id="billing_type">

  		<div class="row">

  			 <!-- Billing Type -->
		  	<div class="col-md-2">
		  		<div class="dropdown" id="dropdown-billtype">
					<button class="btn btn-warning btn-lg dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<span class="billType"><?php echo current_bill_type($bill_type);?></span>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu" id="dropdown-menu-billtype" aria-labelledby="dropdownBillType">
						<?php
							foreach ($bill_type as $key=>$value)
								if ($value['enabled'])
									echo '<li><a id="'.$key.'" href="#">'.$value['description'].'</a></li>';
						?>
					</ul>
				</div>
			</div>


	  		<!-- Salesperson -->

	  		<div class="col-md-2">
		  		<div class="dropdown" id="dropdown-salesperson">
					<button class="btn btn-warning btn-lg dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<span class="billSalesperson"><?php echo current_salesperson($salespersons); ?></span>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu" id="dropdown-menu-salesperson" aria-labelledby="dropdownSalesperson">
						<?php
							$salespersons->data_seek(0);
							while ($obj = $salespersons->fetch_object())
								echo '<li><a id="'.$obj->id.'" href="#">'.$obj->first." ".$obj->last.'</a></li>';
						?>
					</ul>
				</div>
		  	</div>


		  	<!-- Table -->

		  	<div class="col-md-1">
		  		<?php if ($enabled_table_billing=='Y') { ?>

					<div class="form-group">
						<!-- <label for="tableRef"></label> -->
						<input type="text" value="<?php echo $_SESSION['bill_table_ref']; ?>" class="form-control" id="table_ref" placeholder="Table Ref">
					</div>

		  		<?php } ?>
			</div>


		  	<!-- Bill Number -->

		  	<div class="col-md-1">
		  		<h3 style="margin:0px;font-size:34px;"><span id="current_bill_number" class="label label-warning"><?php echo $int_bill_number;?></span></h3>
			</div>



		  	<!-- Storeroom -->
<!--
			<div class="col-md-2">

		  		<div class="dropdown" id="dropdown-storeroom">
					<button class="btn btn-warning btn-lg dropdown-toggle" type="button" id="dropdownStoreroom" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<span class="curStoreroom"><?php echo $arr_storeroom_list[$_SESSION['int_current_storeroom']]; ?></span>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu" id="dropdown-menu-storeroom" aria-labelledby="dropdownStoreroom">
						<?php  
							foreach ($arr_storeroom_list as $key=>$value) {
								echo "<li><a id='$key' href='#'>$value</a></li>";
							}
						?>
						
					</ul>
				</div>

			</div>
-->

		  	<!-- FS Online / Offline -->

		  	<div class="col-md-2">
		  		<div class="dropdown" id="dropdown-online" style="display:<?php echo ($_SESSION['current_bill_type'] == 2 ? 'block' : 'none'); ?>">
					<button class="btn <?php echo ($_SESSION['connect_mode'] == 2 ? 'btn-danger' : 'btn-success'); ?> btn-lg dropdown-toggle" type="button" id="dropdownOnline" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<span class="billOnline"><?php echo ($_SESSION['connect_mode']==CONNECT_ONLINE ? 'Online' : 'Offline'); ?></span>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu" id="dropdown-menu-online" aria-labelledby="dropdownBillOnline">
						<li><a id="<?php echo CONNECT_ONLINE; ?>" href="#">Online</a></li>
						<li><a id="<?php echo CONNECT_OFFLINE_LIMITED_ACCESS; ?>" href="#">Offline</a></li>
					</ul>
				</div>
			</div>

			<!-- Bill Date -->
		  	<div class="col-md-1">
		  		<div class="dropdown" id="dropdown-billdate">
					<button class="btn btn-warning btn-lg dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<span class="billDate"><?php echo $_SESSION['current_bill_day']; ?></span>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu" id="dropdown-menu-billdate" aria-labelledby="dropdownBillDate">
						<?php
							$num_days = date('t');
							for ($i=1;$i<=$num_days;$i++) {
								echo "<li><a id='".$i."' href='#'>".$i."</a></li>";
							}
						?>
					</ul>
				</div>
			</div>
			<div col="col-md-1">
				<span><h4><?php echo date("M Y"); ?></h4></span>
			</div>

		</div>

  	</div> <!-- billing type -->



  	<!--
  		BILLING ERROR
  	-->
  	<div id="billing_error">
		<div class="row">
			<div class="col-md-12">
				<div id="bill_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:none;">
				  <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button> -->
				  <var id="bill_alert_msg"></var>
				</div>
			</div>
		</div>
  	</div> <!-- billing error -->



  	<!--
  		BILLING CLIENT
  	-->
  	<div id="billing_client" class="row-bg">

		<a class="btn btn-primary btn-xs" role="button" data-toggle="collapse" href="#collapseExample" aria-expanded="<?php echo ($str_client_details1 == '') ? "false" : "true"; ?>" aria-controls="collapseExample">
		  Client
		</a>
		<div style="float:right;">
			<button type="button" id="btn-client" class="btn btn-default btn-xs">Clear</button>
		</div>

		<div class="row <?php echo ($_SESSION['client_id'] > 0) ? '' : 'collapse'; ?>" id="collapseExample" aria-expanded="true">

			<div class="col-md-4">
				<div class="typeahead__container">
					<div class="typeahead__field">
						<span class="typeahead__query">
							<input class="typeahead-client" value="<?php echo $str_client_company;?>" id="client_name" name="client[query]" type="search" placeholder="Client" autocomplete="off">
						</span>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="address">
					<span id="client_details1">
						<?php 
							echo $str_client_details1;
						?>
					</span>
				</div>
			</div>

			<div class="col-md-4">
				<div class="address">
					<span id="client_details2">
						<?php 
							echo $str_client_details2;
						?>
					</span>
				</div>
			</div>

		</div>

		
  	</div> <!-- billing client -->


  	<!--
  		BILLING ACCOUNT
  	-->
  	<div id="billing_account" class="row-bg" style="display:<?php echo ($_SESSION['current_bill_type'] == BILL_ACCOUNT) ? "block" : "none"; ?>">

		<div class="row">

			<div class="col-md-3">

					<div class="typeahead__container">
					<label>FS Account</label>
					<div class="typeahead__field">
						<span class="typeahead__query">
							<input class="typeahead-fsaccount" id="fs_account" name="fsaccount[query]" type="search" placeholder="FS Account" autocomplete="off">
						</span>
					</div>
				</div>

<!-- 				<div class="form-group">
					<label for="accountNumber">Account</label>
					<input type="text" id="account_number" value="<?echo $_SESSION['current_account_number']?>" class="form-control" placeholder="FS Account Number" <? echo ($_SESSION['bill_id']>-1) ? "readonly" : "";?> >
				</div>
 -->
			</div>

			<div class="col-md-5">
				<label></label>
				<div class="well well-sm">
					<var id="result-container-account" class="result-container">&nbsp;</var>
				</div>
			</div>

			<div class="col-md-2">
				<label></label>
				<div class="well well-sm">
					<var id="result-container-account-status" class="result-container">&nbsp;</var>
				</div>
			</div>

			<div class="col-md-2">
				<label></label>
				<div class="well well-sm">
					<var id="result-container-account-balance" class="result-container">&nbsp;</var>
				</div>
			</div>

		</div>
		
  	</div> <!-- billing account -->



  	<!--
  		BILLING CREDIT CARD
  	-->
  	<div id="billing_creditcard" class="row-bg" style="display:<?php echo ($_SESSION['current_bill_type'] == BILL_CREDIT_CARD) ? "block" : "none"; ?>">
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="cardName">Name</label>
					<input type="text" value="<?php echo $_SESSION['bill_card_name']; ?>" class="form-control" id="card_name" placeholder="Card Name">
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="cardNumber">Number</label>
					<input type="text" value="<?php echo $_SESSION['bill_card_number']; ?>" class="form-control" id="card_number" placeholder="Card Number">
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="cardValid">Valid Till</label>
					<input type="text" value="<?php echo $_SESSION['bill_card_date']; ?>" class="form-control" id="card_valid" placeholder="Valid Till">
				</div>
			</div>
		</div>
		
  	</div> <!-- billing credit card -->



  	<!--
  		BILLING AUROCARD
  	-->
  	<div id="billing_aurocard" class="row-bg" style="display:<?php echo ($_SESSION['current_bill_type'] == BILL_AUROCARD) ? "block" : "none"; ?>">
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="AurocardNumber">Aurocard Number</label>
					<input type="text" value="<?echo $_SESSION['aurocard_number']?>" class="form-control" name="aurocard_number" id="aurocard_number">
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="AurocardID">Transaction ID</label>
					<input type="text" value="<?echo $_SESSION['aurocard_transaction_id']?>" class="form-control" name="aurocard_transaction_id" id="aurocard_transaction_id" >
				</div>
			</div>
			<div class="col-md-4">
				
			</div>
		</div> 
		
  	</div> <!-- billing aurocard -->


  	<!--
  		BILLING UPI
  	-->
  	<div id="billing_upi" class="row-bg" style="display:<?php echo ($_SESSION['current_bill_type'] == BILL_UPI) ? "block" : "none"; ?>">
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="upi_transaction_id">Transaction ID</label>
						<input type="text" value="<?echo $_SESSION['upi_transaction_id']?>" class="form-control" name="upi_transaction_id" id="upi_transaction_id">
					</div>
				</div>
				<div class="col-md-4">
	 				<div class="form-group">
						<label for="upi_utr_number">UTR Number</label>
						<input type="text" value="<?echo $_SESSION['upi_utr_number']?>" class="form-control" name="upi_utr_number" id="upi_utr_number" >
					</div>
				</div>
				<div class="col-md-4">
					
				</div>
			</div> 
  	</div> <!-- billing UPI -->


  	<!--
  		BILLING BANK TRANSFER
  	-->
  	<div id="billing_bank_transfer" class="row-bg" style="display:<?php echo ($_SESSION['current_bill_type'] == BILL_BANK_TRANSFER) ? "block" : "none"; ?>">
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
					</div>
				</div>
				<div class="col-md-4">
	 				<div class="form-group">
					</div>
				</div>
				<div class="col-md-4">
					
				</div>
			</div> 
  	</div> <!-- billing BANK TRANSFER -->


  	<!--
  		BILLING PRODUCT
  	-->
  	<div id="billing_product" style="margin-top:25px;">


		<div class="row">

			<!-- <form  onsubmit="return false;"> -->

				<!-- Product Code -->
				<div class="col-md-2">

 					<div class="typeahead__container">
						<label>Product</label>
						<div class="typeahead__field">
							<span class="typeahead__query">
								<input class="typeahead-product" id="product_code" name="product[query]" type="search" placeholder="Search" autocomplete="off">
							</span>
						</div>
					</div>

				</div>


				<!-- Batches -->
				<?php if ($str_batches_enabled == 'Y') { ?>
				<div class="col-md-1">
					<label></label>
			  		<div class="dropdown" id="dropdown-productbatches" style="display:<?php echo ($str_batches_enabled == 'Y' ? 'block' : 'none');?>">

						<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuProductBatches" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
							<span class="billProductBatches">Batch</span>
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu" id="dropdown-menu-productbatches" aria-labelledby="dropdownBillProductBatches">
						</ul>
					</div>
				</div>
				<?php } ?>


				<!-- Description -->

				<div class="<?php echo ($str_batches_enabled == 'Y' ? 'col-md-4' : 'col-md-5');?>">
					<!-- <h3><span id="result-container" class="label label-default"></span></h3> -->
					<label></label>
					<div class="well well-sm">
						<var id="result-container-description" class="result-container">&nbsp;</var>
					</div>
				</div>


				<!-- Discount -->

				<div class="col-md-1">
					<div class="form-group">
						<label for="productDiscount">Discount</label>

						<?php if ($_SESSION['current_bill_type'] == BILL_ACCOUNT) { ?>
							<input type="text" value="<?php echo $bill_fs_discount; ?>" class="form-control" id="product_discount" placeholder="Discount">
						<?php } else { ?>
							<input type="text" value="0" class="form-control" id="product_discount" placeholder="Discount">
						<?php } ?>
					</div>
				</div>


				<!-- Quantity -->

				<div class="col-md-1">
					<div class="form-group">
						<label for="productQty">Qty<var id="previous_qty"></var></label>
						<input type="text" value="1" class="form-control" id="product_qty" placeholder="Qty">
					</div>
				</div>


				<!-- Price -->

				<div class="col-md-1">
					<div class="form-group">
						<?php if ($str_edit_price=='Y') { ?>
							<label for="productPrice">Price</label>
							<input type="text" value="0" class="form-control" id="product_price" placeholder="Price">
						<?php } else { ?>
							<label for="productPrice">Price</label>
							<div class="well well-sm">
								<var id="result-container-price" class="result-container">&nbsp;</var>
							</div>
						<?php } ?>
					</div>
				</div>


				<!-- Stock -->

				<div class="col-md-1">
					<div class="form-group">
						<label for="productStock">Stock</label>
						<div class="well well-sm">
							<var id="result-container-stock" class="result-container">&nbsp;</var>
						</div>
						<!-- <input type="text" class="form-control" id="product_stock" placeholder="Stock"> -->
					</div>
				</div>


				<!-- UoM -->

				<div class="col-md-1">
					<div class="form-group">
						<label for="productUnit">UoM</label>
						<div class="well well-sm">
							<var id="result-container-unit" class="result-container">&nbsp;</var>
						</div>
					</div>
				</div>

			<!-- </form> -->

		</div>  		
		
  	</div> <!-- billing product -->


  	<!--
  		BILLING GRID
  	-->
  	<div id="billing_grid">

		<table id="grid-products" class="table table-striped table-condensed " cellspacing="0" width="100%">
	        <thead>
	            <tr>
	            	<th>id</th>
	                <th>code</th>
 	                <th>batch</th>
	                <th>description</th>
	                <th>supplier</th>
	                <th>quantity</th>
	                <th>price</th>
	                <th>discount</th>
	                <th>tax</th>
	                <th>total</th>
	            </tr>
	        </thead>
	    </table>
	    <span id="table_details"></span>
  	</div> <!-- billing grid -->


  	<!--
  		BILLING FOOTER
  	-->
  	<div id="billing_footer" class="row-bg">

		<div class="row">
			<!-- <div class="col-md-6"></div> -->
			<div class="col-md-4"></div>
			<div class="col-md-1">
				<h2><span>Qty:</span></h2>
			</div>
			<div class="col-md-1">
				<h2><span id="bill_qty_total"></span></h2>
			</div>
			<div class="col-md-2">
				<h2><span>Total:</span></h2>
			</div>
			<div class="col-md-3 text-right">
				<h2><span id="bill_total"><?php echo number_format($_SESSION['bill_total'],2,'.',','); ?></span></h2>
			</div>
			<div class="col-md-1"></div>
		</div> 

		<div class="row" id="row1_promotion" style="display:<?php echo ($_SESSION['sales_promotion'] > 0) ? 'block' : 'none'; ?>">
			<div class="col-md-6"></div>
			<div class="col-md-2">
				<h2><span>Promotion:</span></h2>
			</div>
			<div class="col-md-3 text-right">
				<h2><span id="bill_promotion"><?php echo number_format($_SESSION['sales_promotion'],2,'.',','); ?></span></h2>
			</div>
			<div class="col-md-1"></div>
		</div> 

		<div class="row" id="row2_promotion" style="display:<?php echo ($_SESSION['sales_promotion'] > 0) ? 'block' : 'none'; ?>">
			<div class="col-md-6"></div>
			<div class="col-md-2">
				<h2><span>Balance:</span></h2>
			</div>
			<div class="col-md-3 text-right">
				<h2><span id="bill_balance"><?php echo number_format(($_SESSION['bill_total'] - $_SESSION['sales_promotion']),2,'.',','); ?></span></h2>
			</div>
			<div class="col-md-1"></div>
		</div> 
  		
  	</div> <!-- billing footer -->


  	<!--
  		BILLING ACTION
  	-->
  	<div id="billing_action">

		<div class="row">
			<div class="col-md-2">
				<button type="button" id="btn-save" class="btn btn-primary btn-lg btn-block">Save (F2)</button>
			</div>
			<div class="col-md-2">
				<button type="button" id="btn-draft" class="btn btn-primary btn-lg btn-block">Draft</button>
			</div>
			<div class="col-md-2">
				<button type="button" id="btn-cancel" class="btn btn-primary btn-lg btn-block">Cancel</button>
			</div>
			<div class="col-md-2">
				<button type="button" class="btn btn-primary btn-lg btn-block" data-toggle="modal" data-target="#modalChange">Change</button>
			</div>
			<div class="col-md-2">
				<button type="button" class="btn btn-primary btn-lg btn-block" data-toggle="modal" data-target="#modalPromotion">Promotion</button>
			</div>
			<div class="col-md-2">
				
			</div>
		</div>

  	</div> <!-- billing action -->



</div> <!-- class container -->


<!-- Change Modal -->
<div class="modal fade" id="modalChange" tabindex="-1" role="dialog" aria-labelledby="modalLabelChange">
	<div class="modal-dialog" role="document">
		<div class="modal-content">

			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modalLabelChange">Calculate Change</h4>
			</div>

			<div class="modal-body">
				<div class="form-group">
					<label for="productPrice">Bill Total</label>
					<div id="change_bill_total" style="font-weight: bold;font-size: 2em;"><span></span></div>
				</div>
				<div class="form-group">
					<label for="productPrice">Cash Received</label>
					<input type="text" class="form-control" id="change_cash_received" placeholder="Enter Cash Received">
				</div>
				<div class="form-group">
					<label for="productPrice">Change Due</label>
					<div id="change_cash_due" style="font-weight: bold;font-size: 2em;"><span></span></div>
				</div>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>

		</div>
	</div>
</div>


<!-- Promotion Modal -->
<div class="modal fade" id="modalPromotion" tabindex="-1" role="dialog" aria-labelledby="modalLabelPromotion">
	<div class="modal-dialog" role="document">
		<div class="modal-content">

			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modalLabelChange">Sales Promotion</h4>
			</div>

			<div class="modal-body">
				<div class="form-group">
					<label for="promotionAmount">Amount</label>
					<input type="text" class="form-control" id="promotion_amount" placeholder="Enter Sales Promotion Amount">
				</div>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button type="button" id="btn-promotion" class="btn btn-primary">Save</button>
   			</div>

		</div>
	</div>
</div>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

    <!-- <script src="../include/js/jquery-typeahead-2.10.1/dist/jquery.typeahead.min.js"></script> -->
    <!-- <script src="../include/js/jquery-typeahead-2.10.1/src/jquery.typeahead.js"></script> -->
	<script type="text/javascript" charset="utf8" src="../include/js/jquery-typeahead-2.10.6/dist/jquery.typeahead.min.js"></script>

	<script type="text/javascript" src="../include/datatables/datatables.min.js"></script>

	<script type="text/javascript" src="../include/js/accounting.js"></script>
	<script type="text/javascript" src="../include/js/bootbox.min.js"></script>

	<!-- 
		https://silviomoreto.github.io/bootstrap-select/ 

		for the batch select
	-->
	<!-- Latest compiled and minified CSS -->
	<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css"> -->

	<!-- Latest compiled and minified JavaScript -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script> -->


    <script>

		$(document).ready(function(){

			var save_clicked = false;
			var can_view_status = <?php echo DOWNLOAD_ALL; ?>;
			var selected_bill_type = <?php echo $_SESSION['current_bill_type']; ?>;
			var low_balance = <?php echo $low_balance; ?>;
			var company_gstin = '<?php echo $company_gstin; ?>';
			var gstin_is_same = '<?php echo $same_gstin; ?>';

			/*
				Bill Type dropdown
			*/
			$("#dropdown-menu-billtype li a").click(function(){

				//var sel = $(this).text();
				var sel = $(this).attr('id');
				selected_bill_type = sel;

				$(this).parents("#dropdown-billtype").find('.billType').text($(this).text());
				//$(this).parents(".dropdown").find('.billType').val($(this).text());

				$('#billing_account').css({'display':'none'});
				$('#billing_creditcard').css({'display':'none'});
				$('#billing_aurocard').css({'display':'none'});
				$('#billing_upi').css({'display':'none'});
				$('#billing_bank_transfer').css({'display':'none'});
				$('#dropdown-online').css({'display':'none'});

				if (sel == 2) { // FS Account
					$('#billing_account').css({'display':'block'});
					$('#dropdown-online').css({'display':'block'});
				}
				else if (sel == 4) // Credit Card
					$('#billing_creditcard').css({'display':'block'});
				else if (sel == 7) // Aurocard
					$('#billing_aurocard').css({'display':'block'});
				else if (sel == 8) // UPI
					$('#billing_upi').css({'display':'block'});
				else if (sel == 9) // UPI
					$('#billing_bank_transfer').css({'display':'block'});


				/*
					clear previous bill type settings
				*/
				$.ajax({
					method 	: "POST",
					url 	: "data/clear_bill.php",
					data 	: { "action" : "type" }
				})
				.done ( function( msg ) {
					clearFields();
				});


				/*
					save the current bill type to session variable
				*/
				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "current_bill_type", value: sel }
				})
				.done(function( msg ) {
					
					if (sel == 2) // Financial Service
						$(" #product_discount ").val(fs_discount);
					else
						$(" #product_discount ").val('0');

				});

				/*
					get the current bill number for the bill type selected
				*/
				$.ajax({
					method	: "GET",
					url		: 'get_bill_number.php',
					data 	: { live: "2", bill_type: sel}
				})
				.done(function( msg ) {
					$("#current_bill_number").html(msg);
				});
				

				$.ajax({
					method 	: "POST",
					url		: "data/update_list.php",
					data 	: {}
				})
				.done( function( msg ) {

					billTable.ajax.reload();

					updateFooter( msg.billtotal, msg.num_rows, msg.total_qty, msg.warn_insufficient_balance);

				});

			});


			/*
				Bill Type

				Billing Salespersons
			*/
			$("#dropdown-menu-salesperson li a").click(function(){

				var sel = $(this).attr('id');

				$(this).parents("#dropdown-salesperson").find('.billSalesperson').text($(this).text());

				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "bill_salesperson", value: sel }
				})
				.done(function( msg ) {
					console.log( msg );
				});

			});


			$("#dropdown-menu-storeroom li a").click(function() {

				var sel = $(this).attr('id');

				$(this).parents("#dropdown-storeroom").find('.curStoreroom').text($(this).text());

				console.log('storeroom '+sel);

				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "storeroom", value: sel }
				})
				.done(function( msg ) {
					console.log( 'storeroom ' + msg );
					location.reload();
				});
			});


			/*
				Bill Type

				Table reference
			*/
			$("#table_ref").blur(function(){

				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "bill_table_ref", value: $(this).val() }
				})
				.done(function( msg ) {
					console.log( msg );
				});
				
			}); 


			/*
				Bill Type

				Billing Online / Offline
			*/
			$("#dropdown-menu-online li a").click(function(){

				var sel = $(this).attr('id');

				$(this).parents("#dropdown-online").find('.billOnline').text($(this).text());

				if (sel==3)
					$(" #dropdownOnline ").removeClass( "btn-danger" ).addClass( "btn-success" );
				else
					$(" #dropdownOnline ").removeClass( "btn-success" ).addClass( "btn-danger" );

				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "connect_mode", value: sel }
				})
				.done(function( msg ) {
					console.log( msg );
				});

			});


			/*
				Bill Type

				Bill Date
			*/
			$("#dropdown-menu-billdate li a").click(function(){

				var sel = $(this).attr('id');

				$(this).parents("#dropdown-billdate").find('.billDate').text($(this).text());

				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "current_bill_day", value: sel }
				})
				.done(function( msg ) {
					console.log( msg );
				});

			});



			/*

				CLIENT
				
			*/
			//$.typeahead({
			$('.typeahead-client').typeahead({
			    //input 		: ".typeahead-client",
			    order		: "asc",
			    display 	: ["customer_id", "company"],
			    templateValue: "{{customer_id}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/clients.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			 			
			            console.log(item);

						$("#client_details1").html(item.company + '<br>' + item.address + '<br>' + item.city + ' ' + item.zip + '<br>' + item.state);
			            $("#client_details2").html(item.gstin + '<br>' + item.contact_person);

			            if (company_gstin == item.gstin)
			            	gstin_is_same = 'Y';

						$.ajax({
							method 	: "POST",
							url		: "data/session_vars.php",
							data 	: { name: "client_id", value: item.id }
						})
						.done(function( msg ) {
							obj = JSON.parse(msg);
							console.log(msg);
						});
						
			        }
				}

			});

			/*
				CLIENT

				cancel
			*/
			$(" #btn-client ").on("click", function(e) {
				
				bootbox.confirm("Are you sure ?", function(result) { 

					if (result) {

						$.ajax({
							method 	: "POST",
							url 	: "data/session_vars.php",
							data 	: { name : "client_id", value: null }
						})
						.done ( function( msg ) {
							var obj = JSON.parse( msg );

							$("#client_details1").html('');
							$("#client_details2").html('');
							$(".typeahead-client").val('');
						});

					}
					
				});

			});


			/*

				Bill Financial Service Account
				
			*/
/*
			$(" #account_number ").keypress(function( event ) {

				if ( event.keyCode == 13 ) {

					event.preventDefault();

					var fs_account = $(this).val();

					getAccountNumber(fs_account);

				}
				else if (event.keyCode == 27 ) {

					event.preventDefault();

				}

			});
*/

			$('.typeahead-fsaccount').typeahead({
			    order		: "asc",
			    display 	: ["account_number", "account_name"],
			    templateValue: "{{account_number}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    maxItem		: 12,
			    debug		: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/fsaccounts.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			 			
			 			$( ".typeahead-fsaccount" ).focusout();

			            getAccountNumber( item.account_number );
			        },
			        onsubmit: function() {
			        	event.preventDefault();
			        }
				}

			});
			
			$(" .typeahead-fsaccount ").keyup(function( event ) {

				if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

					event.preventDefault();

					$(".typeahead__result").hide();

					getAccountNumber( $(this).val() );
				}
				else
					$(".typeahead__result").show();
			});


			getAccountNumber = function(fs_account='') {

				$.ajax({
					method 	: "GET",
					url		: "../common/account.php",
					data 	: { live: 1, account_number: fs_account },
					beforeSend: function( xhr ) {
						$("#result-container-account").text('fetching data...');
						$("#result-container-account-status").text('');
						$("#result-container-account-balance").text('');
					}
				})
				.done(function( msg ) {
					obj = msg.split('|');

					console.log(msg);

					if (obj[0]=='OK') {

						$("#result-container-account").text(obj[1]);
						$("#result-container-account-status").text('');
						$("#result-container-account-balance").text('');

						$(" #product_code ").focus();
						$(" #product_code ").select();

						/*
							get additional account information
						*/
						$.ajax({
							type: 'POST',
							url: 'getFSAccountDetails.php',
							data: { fsAccount: fs_account }
						})
						.done(function( msg ) {

							arr = $.parseJSON( msg );

							if (arr['Result']=='OK') {

								amt = parseInt(arr['maxAmount']);

								if ((amt > 0) || ((amt == -1))) {
									$("#result-container-account-status").text('Balance available');
									$("#result-container-account-status").css("color", "green");
									if (can_view_status > 0) {
										//$("#result-container-account-balance").text(accounting.formatMoney(amt, "Rs ", 2, ",", "."));
										//$("#result-container-account-balance").css("color", "black");
									}
									else {
										if ((amt <= low_balance) && (amt != -1)) {
											$("#result-container-account-balance").text('Low Balance');
											$("#result-container-account-balance").css("color", "orange");
										}
									}
								}
								else {
									$("#result-container-account-status").text('Insufficient Balance');
									$("#result-container-account-status").css("color", "red");
								}
							}
							else {
								$("#result-container-account-status").text('FS Msg: ' + arr['Result']);
								$("#result-container-account-status").css("color", "orange");
							}
						});

						/*
							save the account number to session variable
						*/
						$.ajax({
							method	: "POST",
							url		: "data/session_vars.php",
							data 	: { name: "bill_account", value: fs_account }
						})
						.done(function( res ) {
							console.log( res );
						});

					}
					else
						$("#result-container-account").text('Account number not found.');
				})
			}


			/*

				Bill Aurocard
				
			*/
			$(" #aurocard_number ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "aurocard_number", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(msg);
				});
			});

			$(" #aurocard_transaction_id ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "aurocard_transaction_id", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(msg);
				});
			});


			/*

				Bill UPI
				
			*/
			$(" #upi_transaction_id ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "upi_transaction_id", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(msg);
				});
			});

			$(" #upi_utr_number ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "upi_utr_number", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(msg);
				});
			});


			/*

				Bill Credit Card
				
			*/
			$(" #card_name ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "bill_card_name", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(msg);
				});
			});

			$(" #card_number ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "bill_card_number", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(obj);
				});
			});

			$(" #card_valid ").focusout(function() {
				$.ajax({
					method 	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "bill_card_date", value: $(this).val() }
				})
				.done(function( msg ) {
					obj = JSON.parse(msg);
					console.log(msg);
				});
			});

			/*

				Billing Enter
				Functions called by Billing Enter fields

			*/


			/*
				common/product_description.php
				
				returns the product description, measurement unit, is decimal and supplier abbreviation.
				and saves the following session variables:
					$_SESSION["current_product_id"]
					$_SESSION["current_code"]
					$_SESSION["current_description"]
			*/
			getDescription = function(strProductCode='') {

				var strPassValue = '';

				if (strProductCode.value != '') {

					strPassValue = strProductCode;

					is_bar_code = 'N';
					if (strPassValue.length == 13)
						is_bar_code = 'Y';

					$.ajax({
						method 	: "GET",
						url		: "../common/product_description.php",
						data 	: { live: 1, product_code: strPassValue, is_bar_code: is_bar_code}
					})
					.done(function( msg ) {

						//console.log(msg);

						arr_retval = msg.split('|');

						if (arr_retval[0] == '__NOT_FOUND') {

							can_bill = false;					

							$("#result-container-description").text("Given product not found.");

						}
						else if (arr_retval[0] == "__NOT_AVAILABLE") {

							can_bill = false;

							$("#result-container-description").text("This product has been disabled");

							if (display_abbreviation == 'Y')
								$('#result-container-description').text( arr_retval[0] + ' ' + arr_retval[3] );
							else
								$('#result-container-description').text( arr_retval[0] );

							$("#product_code").val('');
							$("#product_code").focus();

						}
						else {
							can_bill = true;

							if (display_abbreviation == 'Y')
								$('#result-container-description').text( arr_retval[0] + ' ' + arr_retval[3] );
							else
								$('#result-container-description').text( arr_retval[0] );

							$('#result-container-unit').text(arr_retval[1]);

							bool_is_decimal = (arr_retval[2] == 'Y') ? true : false;

							if (str_batches_enabled == 'Y')
								$("#dropdownMenuProductBatches").focus();
							else
								$(" #product_qty ").focus();
							
							getBatches();
						}

					});

				}

				//if(str_use_scale=="Y")
				//	getWeight(strProductCode.value);
			}

			/*
				common/product_batches.php
			
				saves the batch codes and corresponding quantities in the session array arr_item_batches
				for the product code that is passed
					$_SESSION["arr_item_batches"][$int_counter][0] = batch code
					$_SESSION["arr_item_batches"][$int_counter][1] = quantity
					$_SESSION["arr_item_batches"][$int_counter][2] = batch_id
			*/
			getBatches = function() {
				var strPassValue = '';

				if ( $("#product_code").val() == '')
				{
				    strPassValue = 'nil';
				}
				else
				    strPassValue = $("#product_code").val();

				is_bar_code = 'N';
				if (strPassValue.length == 13)
					is_bar_code = 'Y';

				$.ajax({
					method 	: "GET",
					url 	: "../common/product_batches.php",
					data 	: {live:1, product_code: strPassValue, is_bar_code: is_bar_code}

				})
				.done( function( msg ) {

					if (str_batches_enabled == 'Y')
						$('#dropdown-menu-productbatches').empty();
					
					flt_total_batch_quantity = 0;
					
					if ((msg == 'nil') || (msg == '0')) {
						$('#result-container-stock').text('');

						/*
						$(" #bill_alert_msg ").html( "This product has no stock" );
						$(" #bill_alert ").removeClass( "alert-danger" ).addClass( "alert-warning" );
						$(" #bill_alert ").show();

						setTimeout(function() {
					        $(" #bill_alert ").fadeTo(2000, 500).hide();
					    }, 2000);
						*/

						$("#product_code").focus().select();
					}
					else {
						/*
							the return value is & delimited for batch code and quantity
							and | delimited per batch
						*/
						arr_batches.length = 0;
						arr_batch_codes.length = 0;
						arr_batch_quantities.length = 0;
						arr_batch_prices.length = 0;
						arr_batch_ids.length = 0;
						
						arr_batches = msg.split('|');
						
						for (i=0; i<arr_batches.length; i++) {
							arr_temp = arr_batches[i].split('&');
							arr_batch_codes[i] = arr_temp[0];
							arr_batch_ids[i] = arr_temp[3];
							arr_batch_quantities[i] = arr_temp[1];
							arr_batch_prices[i] = arr_temp[2];
							
							flt_total_batch_quantity += parseFloat(arr_temp[1]);

							if (str_batches_enabled == 'Y') {
								
								if (i==0) {
									var html = '<li class="active" id="'+i+'"><a id="' + arr_batch_ids[i] + '" href="#">' + arr_batch_codes[i] + '</a></li>';
									$("#dropdown-productbatches").find('.billProductBatches').text(arr_batch_codes[i]);
								}
								else
									var html = '<li id="'+i+'"><a id="' + arr_batch_ids[i] + '" href="#">' + arr_batch_codes[i] + '</a></li>';

								$("#dropdown-menu-productbatches").append(html);
							}
						}
						
						if (str_edit_price == 'Y')
							$('#product_price').val(arr_batch_prices[0]);
						else
							$("#result-container-price").text(arr_batch_prices[0]);
						
						if (str_batches_enabled == 'Y') {

							//$("#dropdown-menu-productbatches").find('.billProductBatches').text(arr_batch_codes[i]);

							if (bool_is_decimal) {
								tmp_num = parseFloat(arr_batch_quantities[0]);
								$('#result-container-stock').text(tmp_num.toFixed(int_decimals));
							}
							else {
								tmp_num = parseInt(arr_batch_quantities[0]);
								$('#result-container-stock').text(tmp_num.toFixed(0));
							}
						}
						else {

							updateBatch();

							if (str_edit_price == 'Y')
								$('#product_price').val(arr_batch_prices[0]);
							else
								$("#result-container-price").text(arr_batch_prices[0]);
							
							if (bool_is_decimal) {
								$('#result-container-stock').text(flt_total_batch_quantity.toFixed(int_decimals));
							}
							else {
								$('#result-container-stock').text(flt_total_batch_quantity.toFixed(0));
							}
						}
					}

				}); // end of ".done()"
			}


			/*
				common/update_batch.php
			
				iterates through the session array $_SESSION['arr_total_qty']
				and returns, if found,
					the index where the item was found
					the quantity of the found item
			*/
			updateBatch = function() {
				
				strPassValue = $("#product_code").val();

				is_bar_code = 'N';
				if (strPassValue.length == 13)
					is_bar_code = 'Y';
				
				var strBatchCode = '';

				if (arr_batches.length > 0) {

					if (str_batches_enabled == 'Y') {
						strBatchCode = $('#dropdown-menu-productbatches li a').attr('id');
						//var previous_tab = e.relatedTarget;
						//console.log('current batch ' + strbatchCode);
					}
					else
						strBatchCode = arr_batch_codes[0];

					$.ajax({
						method 	: "GET",
						url 	: "../common/update_batch.php",
						data 	: {live:1, product_code: strPassValue, batch_code: strBatchCode, is_bar_code: is_bar_code}
					})
					.done( function( msg ){

						console.log('update_batch' + msg);

						arr_retval = msg.split('|');
						
						if (arr_retval[0] != 'nil') {

							$(" #previous_qty ").text("("+arr_retval[1]+")");
							flt_previous_qty = arr_retval[1];

							$.ajax({
								method 	: "POST",
								url		: "data/update_list.php",
								data 	: {del:'Y'}
							})
							.done( function( msg ) {

								console.log( msg );

								billTable.ajax.reload();

								updateFooter( msg.billtotal, msg.num_rows, msg.total_qty, msg.warn_insufficient_balance );

							});
						}
						else {
							flt_previous_qty = 0;
						}

						//$(" #product_discount ").focus();
						$(" #product_qty ").focus();


					});
				}
			}


			setBatchQty = function () {

				if (arr_batches.length > 0) {
					
					var sel = $('#dropdown-menu-productbatches').find('li.active').attr('id');

				    if (str_batches_enabled == 'Y') {

						if (bool_is_decimal) {
							tmp_num = parseFloat(arr_batch_quantities[sel]);
							$('#result-container-stock').text(tmp_num.toFixed(int_decimals));
						}
						else {
							tmp_num = parseInt(arr_batch_quantities[sel]);
							$('#result-container-stock').text(tmp_num.toFixed(0));
						}

						if (str_edit_price == 'Y')
							$('#product_price').val(arr_batch_prices[sel]);
						else
							$("#result-container-price").text(arr_batch_prices[sel]);

				    }
				    else {
						if (bool_is_decimal) {
							tmp_num = parseFloat(flt_total_batch_quantity);
							$('#result-container-stock').text(tmp_num.toFixed(int_decimals));
						}
						else {
							tmp_num = parseInt(flt_total_batch_quantity);
							$('#result-container-stock').text(tmp_num.toFixed(0));
						}
				    }
				}

				// $(" #product_qty ").focus();
			}


			removeCommas = function( aNum ) {
				
				//remove any commas
				aNum=aNum.replace(/,/g,"");
				//remove any spaces
				aNum=aNum.replace(/\s/g,"");

				return aNum;
			}



			/*
				../common/product_quantities.php

				sets most of the arr_total_qty variables, 
				including the tax details:
			
					$_SESSION["arr_total_qty"][$atIndex][7] = $tax_id;
					$_SESSION["arr_total_qty"][$atIndex][8] = $tax_description;
					$_SESSION["arr_total_qty"][$atIndex][9] = $is_taxed;
			*/

			checkQty = function (aValue='') {
				
				var flt_pass_qty;
				var can_bill_adjusted = true;
				var qty = $(" #product_qty ").val();
				var intDiscount = $(" #product_discount ").val();

				can_bill = false;
				if ((qty > 0) && ($.isNumeric(qty))) {
				    can_bill = true;
				}
				else {
					alert('Quantity must be greater than zero');
				}

				/*
				if ((requester_completed == false) || (requester2_completed == false) || (requester3_completed == false)) {
					setTimeout("checkQty("+aValue+")", 1000);
					return;
				}
				*/

				if (can_bill == true) {

					var strPassValue = '';
					var is_bar_code = 'N';
					
					if (( $("#product_code").val() == '') || (aValue == '0') || (aValue == ''))
						strPassValue = 'nil'
					else
						strPassValue = $("#product_code").val();
						
					flt_pass_qty = parseFloat(aValue) + parseFloat(flt_previous_qty);
					
					if (str_display_messages == 'Y') {
						if (flt_pass_qty == flt_total_batch_quantity) {
							alert('The current stock of this product is now zero');
						}
						else if (flt_pass_qty > flt_total_batch_quantity) {
							if (str_adjusted_enabled == 'Y')
								alert('The current stock of this product is now negative');
							else {
								alert('Stock not available for the quantity specified');
								can_bill_adjusted = false;
							}
						}
					}
					else {
						if ((flt_pass_qty > flt_total_batch_quantity) && (str_adjusted_enabled == 'N')) {
							alert('Stock not available for the quantity specified');
							can_bill_adjusted = false;
						}
					}
					
					if (strPassValue.length == 13)
						is_bar_code = 'Y';
					
					if (str_edit_price == 'Y')
						strPassPrice = removeCommas( $(" #product_price ").val() );
					else {
						strPassPrice = removeCommas(arr_batch_prices[0]);
					}

					if (can_bill_adjusted) {

						if (arr_batches.length > 0) {

							if (str_batches_enabled == 'Y') {

								var sel = $('#dropdown-menu-productbatches').find('li.active').attr('id');
								//var ind = $('#dropdown-menu-productbatches').find('li.active').index();

								var data = {
									live: 1, 
									product_code: strPassValue, 
									batch_code: arr_batch_codes[sel], 
									qty: flt_pass_qty, 
									is_bar_code: is_bar_code, 
									batch_id: arr_batch_ids[sel], 
									price: strPassPrice,
									same_gstin: gstin_is_same
								};
							}
							else
								var data = {
									live: 1, 
									product_code: strPassValue, 
									batch_code: arr_batch_codes[0], 
									qty: flt_pass_qty, 
									is_bar_code: is_bar_code, 
									batch_id: arr_batch_ids[0], 
									price: strPassPrice,
									same_gstin: gstin_is_same
								};

							$.ajax({
								method 	: "GET",
								url 	: "../common/product_quantities.php",
								data 	: data
							})
							.done( function( msg ) {

								if (msg != 'nil') {

									$(" product_qty ").val(msg);

									if (can_bill == true) {

										if (str_batches_enabled == 'Y')
											int_index = sel;
										else
											int_index = 0;

										if (intDiscount > 0)
											var data2 = {code: strPassValue, set_discount:'Y', discount: intDiscount, batch_code: arr_batch_codes[int_index]}
										else
											var data2 = {code: strPassValue, set_discount:'N', discount: 0, batch_code: arr_batch_codes[int_index]}

										$.ajax({
											method 	: "GET",
											url		: "data/update_list.php",
											data 	: data2
										})
										.done( function( msg2 ) {

											//var obj = jQuery.parseJSON(msg2);

											billTable.ajax.reload();

											updateFooter( msg2.billtotal, msg2.num_rows, msg2.total_qty, msg2.warn_insufficient_balance );

										});
									}
									
								}
								else {
									$(" product_qty ").val('1');
									flt_previous_qty = 0;
								}
								
								$(" .typeahead-product ").val('');
								$(" .typeahead-product ").select();
								
							});

						}
					}
					else {
						$(" product_qty ").val('1');
						flt_previous_qty = 0;

						$(" .typeahead-product ").val('');
						$(" .typeahead-product ").select();
					}
				}
				
			}

			
			updateFooter = function( data='', rows='', total_qty='', warn_insufficient_balance='' ) {

				$("#bill_total").html(accounting.formatMoney(data, "", 2, ",", "."));

				//$("#table_details").html("Total quantity: " + total_qty);
				$("#bill_qty_total").html(total_qty);


				var total = $('#bill_total').text();
				total = accounting.unformat(total, ".");
				
				var promotion = $(" #bill_promotion ").text();
				promotion = accounting.unformat(promotion, ".");

				balance = total - promotion;

				if (warn_insufficient_balance == '_WARN') {
					$("#result-container-account-status").text('Insufficient Balance');
					$("#result-container-account-status").css("color", "red");
				}
				else {
					$("#result-container-account-status").text('Balance available');
					$("#result-container-account-status").css("color", "green");					
				}

				if (promotion > 0) {
					$(" #row1_promotion ").css("display","block");
					$(" #row2_promotion ").css("display","block");
					$(" #bill_promotion ").text(accounting.formatMoney(promotion, "", 2, ",", "."));
					$(" #bill_balance ").text(accounting.formatMoney(balance, "", 2, ",", "."));
				}
				else {
					$(" #row1_promotion ").css("display","none");
					$(" #row2_promotion ").css("display","none");
					$(" #bill_promotion ").text('');
					$(" #bill_balance ").text('');
				}

			}


			clearValues = function() {

				$('#dropdown-menu-productbatches').empty();
				//$("#dropdown-productbatches").find('.billProductBatches').text( 'Batch' );

				$('#result-container-description').html('&nbsp;');
				
				if (selected_bill_type != 2)
					$(" #product_discount ").val("0");

				$(" #previous_qty ").text("");
				$(" #product_qty ").val('1');

				$('#product_price').val(0);
				$("#result-container-price").text('0');

				$('#result-container-stock').html('&nbsp;');
				$("#result-container-unit").html('&nbsp;');

			}


			clearFields = function() {

				$(" #account_number ").val('');
				$(" #result-container-account ").text('');
				$(" #result-container-account-status ").text('');
				$(" #result-container-account-balance ").text('');

				$(" #card_name ").val('');
				$(" #card_number ").val('');
				$(" #card_valid ").val('');

				$(" #aurocard_number ").val('');
				$(" #aurocard_transaction_id ").val('');

			}

			/*
				Billing Enter
				Product Code Typeahead 
			*/


			$('.typeahead-product').typeahead({
			    //input 		: ".typeahead-product",
			    order		: "asc",
			    display 	: ["code", "description"],
			    templateValue: "{{code}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    maxItem		: 12,
			    debug		: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/products.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			 			
			 			$( ".typeahead-product" ).focusout();

			            getDescription( item.code );
			        },
			        onsubmit: function() {
			        	event.preventDefault();
			        }
				}

			});
			
			$(" .typeahead-product ").keyup(function( event ) {

				if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

					event.preventDefault();

					$(".typeahead__result").hide();

					getDescription( $(this).val() );
				}
				else
					$(".typeahead__result").show();
			});

			$(" .typeahead-product ").focusin(function() {

				$(".typeahead__result").show();

				clearValues();
			});


			/*
				Billing Enter
				Batch dropdown
			*/
			$(" #dropdown-menu-productbatches ").on("click", "li a", function(e) {

				console.log('clicked');

				var sel = $(this).attr('id');

				$(this).parents('#dropdown-menu-productbatches').find('li').removeClass("active");

				$(this).parent().addClass( "active" );

				$(this).parents("#dropdown-productbatches").find('.billProductBatches').text( $(this).text() );

				setBatchQty();

				$(" #dropdownMenuProductBatches ").focus();

			});
			$(" #dropdownMenuProductBatches ").keypress( function( event ) {
				if (( event.keyCode == 13 ) || (event.keyCode == 9)){
					event.preventDefault();
					updateBatch();
				}
				else
					console.log('event' + event.which);
			})


			/*
				Billing Product
				Keyboard input 
			*/
			$(" #product_qty ").keyup(function( event ) {
				
				if (event.keyCode == 27 ) {

					event.preventDefault();


				    if (flt_previous_qty > 0) {
						$(" #previous_qty ").text("");
						flt_previous_qty = 0;
				    }
				    else {
						clearValues();
						$(" #product_code ").focus();
						$(" #product_code ").select();
						
				    }
				}

			});


			$(" #product_qty ").keypress(function( event ) {

				if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

					event.preventDefault();

					if (str_edit_price == 'Y') {
						$("#product_price").focus();
					} else {
						checkQty( $(" #product_qty ").val() );

						$(" #product_code ").focus();
						$(" #product_code ").select();
					}
				}

			});

			$(" #product_qty ").focusin(function() {
				$( this ).val('1');
				$( this ).select();

				setBatchQty();
			});


			$(" #product_discount ").keypress(function( event ) {
				if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {
					event.preventDefault();
					$("#product_qty").focus();
				}
			});

			$(" #product_discount ").keyup(function( event ) {
				if (event.keyCode == 27 ) {
					event.preventDefault();

					clearValues();
					$(" #product_code ").focus();
					$(" #product_code ").select();
				}
			});

			$(" #product_discount ").focusin(function() {
				$( this ).select();
			})

			$(" #product_price ").keypress(function( event ) {

				if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

					event.preventDefault();

					if (str_edit_price == 'Y')
						checkQty( $(" #product_qty ").val() );

					$(" #product_code ").focus();
					$(" #product_code ").select();
				}
			});
			$(" #product_price ").keyup(function( event ) {			
				if (event.keyCode == 27 ) {

					event.preventDefault();

					if (str_edit_price == 'Y') {
						flt_previous_qty = 0;
						clearValues();
					}

					$(" #product_code ").focus();
					$(" #product_code ").select();
				}

			});

			$(" #product_price ").focusin(function() {
				$( this ).select();
			});
			/*
			$(" #product_price ").focusout(function() {
				checkQty( $(" #product_qty ").val() );
			});
			*/

			/*
				Billing Grid
				https://www.datatables.net/examples/basic_init/scroll_y_dynamic.html
			*/
			var billTable = $('#grid-products').DataTable({
		        scrollY 		: '40vh',
		        scrollCollapse	: true,
		        paging			: false,
		        searching		: false,
		        ajax			: "data/update_list.php",
		        columns: [
	                { data: "id", visible: false },
	                { data: "code" },
	                { data: "batch", visible: (str_batches_enabled=='Y') ? true : false },
	                { data: "description" },
	                { data: "supplier" },
	                { data: "quantity" },
	                { data: "price" },
	                { data: "discount" },
	                { data: "tax" },
	                { data: "total" }
	            ],
				oLanguage		: {
					"sInfo": "", //"_TOTAL_ entries",
					"sInfoEmpty": "No entries",
					"sEmptyTable": "Zero products billed",
				}
		    });



			/*
				Billing Action Keys
			*/
			$("body").keydown(function(e){

				var keyCode = e.keyCode || e.which || e.key;

				// F2
				if (keyCode == 113) {
 					e.preventDefault();

 					$(" #btn-save ").trigger( "click" );
				}
				/*
				// F7
				else if (keyCode == 116) {
					e.preventDefault();
					$('#modalDiscount').modal('show');
				}
				// F8
				else if (keyCode == 119) {
					e.preventDefault();
					$('#modalChange').modal('show');
				}
				*/
			});



			/*
				action save
			*/
			$(" #btn-save ").on("click", function(e) {

				if ( billTable.data().count() > 0 ) {

					if (save_clicked == false) {

						var cur_bill_type = selected_bill_type; //$("#dropdown-menu-billtype li a").attr('id');
						var is_draft_bill = $("#is_draft_bill").val();

						$(this).attr("disabled", true);
						save_clicked = true;

						$.ajax({
							method 	: "POST",
							url 	: "data/bill_save.php",
							data 	: { "action" : "save", "bill_type" : cur_bill_type, "is_draft_bill" : is_draft_bill, "draft_bill_id" : <?php echo $draft_bill_id;?> }
						})
						.done( function( msg ) {

							var obj = JSON.parse( msg );

							if (obj.bill_id > 0) {

								$.ajax({
									method 	: "POST",
									url 	: "data/clear_bill.php",
									data 	: { "action" : "clear", "bill_type" : cur_bill_type }
								})
								.done ( function( msg2 ) {

									var obj2 = JSON.parse( msg2 );			
									
									if (obj2.reset) {

										/*
										if (confirm("Bill saved successfully. Print the bill?")) {
											printBill(obj.bill_id);
										} 

										var url = window.location.href.split("?")[0];
										window.location = url + '?action=clear_bill';

										setTimeout("window.location.reload(true);", 5000);

										*/
										bootbox.confirm("Bill saved successfully. <br> Print the bill?", 

											function(result) { 

												if (result)
													printBill(obj.bill_id);

												var url = window.location.href.split("?")[0];
												window.location = url + '?action=clear_bill';

												setTimeout("window.location.reload(true);", 2000);
											}
										);
										
									}
									
								});
							}
							else {
								$(" #bill_alert_msg ").html( obj.message + '<br><code>ERROR ' + obj.sql + '</code>');

								$(" #bill_alert ").removeClass( "alert-warning" ).addClass( "alert-danger" );
								$(" #bill_alert ").show();

								/*
									if the bill saved is type FS, and if it was insufficient funds
									then enable save button again so that it can be saved
									as "Offline"
								*/
								if (cur_bill_type == 2) {

									$(" #btn-save").prop('disabled', false);

									save_clicked = false;

									setTimeout(function() {
								        $(" #bill_alert ").fadeTo(2000, 500).hide();
								    }, 2000);

								}
							}
						});
					}
				}
				else {

					$(" #bill_alert_msg ").html( "No items to save" );

					$(" #bill_alert ").addClass( "alert-danger" ).removeClass( "alert-warning" );
					$(" #bill_alert ").show();

					setTimeout(function() {
				        $(" #bill_alert ").fadeTo(2000, 500).hide();
				    }, 2000);

				}
			});


			
			/*
				action draft
			*/
			$(" #btn-draft ").on("click", function(e) {

				if ( billTable.data().count() > 0 ) {


					var cur_bill_type = selected_bill_type;
					var is_draft_bill = $("#is_draft_bill").val();

					$.ajax({
						method 	: "POST",
						url 	: "data/bill_save.php",
						data 	: { "action" : "draft", "bill_type" : cur_bill_type, "is_draft_bill" : is_draft_bill, "draft_bill_id" : <?php echo $draft_bill_id;?> }
					})
					.done( function( msg ) {

						var obj = JSON.parse( msg );

						if (obj.bill_id > 0) {

							$.ajax({
								method 	: "POST",
								url 	: "data/clear_bill.php",
								data 	: { "action" : "clear", "bill_type" : cur_bill_type }
							})
							.done ( function( msg2 ) {

								var obj2 = JSON.parse( msg2 );			
								
								if (obj2.reset) {

									bootbox.alert("Draft saved successfully", function() { 

										var url = window.location.href.split("?")[0];
										window.location = url + '?action=clear_bill';

									});

								}
								
							});
						}
						else {
							$(" #bill_alert_msg ").html( obj.message + '<br><code>' + obj.sql + '</code>');

							$(" #bill_alert ").removeClass( "alert-warning" ).addClass( "alert-danger" );
							$(" #bill_alert ").show();
						}
					});


				}
				else {

					$(" #bill_alert_msg ").html( "No items to save" );

					$(" #bill_alert ").addClass( "alert-danger" ).removeClass( "alert-warning" );
					$(" #bill_alert ").show();

					setTimeout(function() {
				        $(" #bill_alert ").fadeTo(2000, 500).hide();
				    }, 2000);

				}
			});



			printBill = function( aBillId='' ) {
				myWin = window.open("<?php echo $print_filename;?>?id="+aBillId, 'printwin', 'toolbar=no,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=no,width=450,height=250');
				myWin.focus();
			}


			/*
				action cancel
			*/
			$(" #btn-cancel ").on("click", function(e) {

				var is_draft_bill = $("#is_draft_bill").val();				

				if (is_draft_bill==true) {

					bootbox.confirm("Cancel this draft ?", function(result) { 

						if (result) {

							/*
								delete the draft calling "load_bill.php"
							*/
							$.ajax({
								method 	: "POST",
								url 	: "data/load_bill.php",
								data 	: { "action" : "cancel", "bill_id": <?php echo $draft_bill_id;?> }
							})
							.done ( function( msg ) {

								/*
									clear the billing window calling "clear_bill.php"
								*/
								$.ajax({
									method 	: "POST",
									url 	: "data/clear_bill.php",
									data 	: { "action" : "clear" }
								})
								.done ( function( msg2 ) {

									window.location = window.location.href.split("?")[0];
									
								});

							});
						}
					});
				}
				else {

					bootbox.confirm("Are you sure ?", function(result) { 

						if (result) {

							$.ajax({
								method 	: "POST",
								url 	: "data/clear_bill.php",
								data 	: { "action" : "clear" }
							})
							.done ( function( msg ) {
								var obj = JSON.parse( msg );
								if (obj.reset) {
									window.location.reload(true);
								}
							});
						}
					});
				}

			});

			/*
				Modal Change
			*/
			$('#modalChange').on('shown.bs.modal', function () {
				var total = $('#bill_total').text();
				total = accounting.unformat(total, ".");

				//accounting.formatMoney(4999.99, "€", 2, ".", ","); // €4.999,99
				//accounting.unformat("€ 1.000.000,00", ","); // 1000000

				var due = 0;
				var received = 0;

				$('#change_cash_received').focus();
				$('#change_cash_due span').text('');

				$('#change_bill_total span').text(accounting.formatMoney(total, "Rs ", 2, ",", "."));

				$('#change_cash_received').keyup(function () {
					if (this.value != this.value.replace(/[^0-9\.]/g, '')) {
						this.value = this.value.replace(/[^0-9\.]/g, '');
					}

					received = $(this).val();
					due = received - total;
					
					if (due > 0)
						$('#change_cash_due span').text(accounting.formatMoney(due, "Rs ", 2, ",", "."));
					else
						$('#change_cash_due span').text('');
				});				

			});



			/*
				Promotion
			*/
			$(" #modalPromotion ").on('shown.bs.modal', function () {
				var promotion = $(" #bill_promotion ").text();
				promotion = accounting.unformat(promotion, ".");
				
				$(" #promotion_amount ").val(promotion);
				$(" #promotion_amount ").focus().select();
			});

			$(" #btn-promotion ").on("click", function( event ) {

				var total = $('#bill_total').text();
				total = accounting.unformat(total, ".");
				
				var promotion = $(" #promotion_amount ").val();
				promotion = promotion.replace(/[^0-9\.]/g, ''); //$(this).val();

				balance = total - promotion;

				$.ajax({
					method	: "POST",
					url		: "data/session_vars.php",
					data 	: { name: "sales_promotion", value: promotion }
				})
				.done(function( msg ) {

					obj = JSON.parse(msg);

					if (promotion > 0) {
						$(" #row1_promotion ").css("display","block");
						$(" #row2_promotion ").css("display","block");
						$(" #bill_promotion ").text(accounting.formatMoney(promotion, "", 2, ",", "."));
						$(" #bill_balance ").text(accounting.formatMoney(balance, "", 2, ",", "."));
					}
					else {
						$(" #row1_promotion ").css("display","none");
						$(" #row2_promotion ").css("display","none");
						$(" #bill_promotion ").text('');
						$(" #bill_balance ").text('');
					}

					$(' #modalPromotion ').modal('hide');

				});


				/* 
					not supported :-(

				bootbox.prompt({
				    title: "Sales Promotion",
				    inputType: 'Amount: ',
				    callback: function (result) {
				        console.log(result);
				    }
				});
				*/

			});




			/*
				if new bill was selected,
				clear all session variables
			*/

			<?php
				if ((isset($_GET['action'])) && ($_GET['action']=='clear_bill')) {
			?>

				$.ajax({
					method 	: "POST",
					url 	: "data/clear_bill.php",
					data 	: { "action" : "clear" }
				})
				.done ( function( msg ) {
					var obj = JSON.parse( msg );
					

					if (obj.reset) {
						window.location = window.location.href.split("?")[0];
					}
				});

			<?php 
				}
			?>



			/*
				if a draft bill was selected,
				load the details
			*/
			<?php
				if ((isset($_GET['action'])) && ($_GET['action']=='edit_draft')) {
			?>

				$(" #btn-save").prop('disabled', true);
				$(" #btn-draft").prop('disabled', true);

				$.ajax({
					method 	: "POST",
					url 	: "data/load_bill.php",
					data 	: { "action" : "draft", "bill_id": <?php echo $draft_bill_id;?> }
				})
				.done ( function( msg ) {

					var obj = JSON.parse( msg );

					$(" #table_ref" ).val(obj.data['table_ref']);

					$(" #account_number ").val(obj.data['account_number']);

					getAccountNumber(obj.data['account_number']);
				});



				$.ajax({
					method 	: "POST",
					url 	: "../common/product_quantities.php",
					data 	: { "action" : "draft", "bill_id": <?php echo $draft_bill_id;?> }
				})
				.done ( function( msg2 ) {

					$.ajax({
						method 	: "POST",
						url		: "data/update_list.php",
						data 	: {}
					})
					.done( function( msg3 ) {

						billTable.ajax.reload();

						updateFooter( msg3.billtotal, msg3.num_rows, msg3.total_qty, msg3.warn_insufficient_balance);

					})
					.always( function() {

						$(" #btn-save").prop('disabled', false);
						$(" #btn-draft").prop('disabled', false);

					});

				});



			<?php
				}
			?>



			$(" #product_code ").focus();


		}); // end document ready

    </script>

</body>
</html>