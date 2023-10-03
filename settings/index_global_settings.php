<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$int_access_level = (getModuleAccessLevel('Admin'));
	
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	} 
	
	$_SESSION['int_settings_menu_selected']=2;
	
	$bool_can_update = false;
	if (($int_access_level > 2) && ($_SESSION["int_user_type"] > 1))
		$bool_can_update = true;


	if (isset($_POST['action'])) {

		if ($_POST['action'] == 'company') {

			$sql = "
					UPDATE company
					SET title 		= '".addslashes($_POST['companyTitle'])."',
						legal_name 	= '".$_POST['companyLegalName']."',
						trade_name 	= '".$_POST['companyTradeName']."',
						trust 		= '".$_POST['companyTrust']."',
						gstin 		= '".$_POST['companyGSTIN']."',
						address 	= '".addslashes($_POST['companyAddress'])."',
						phone 		= '".$_POST['companyPhone']."',
						email 		= '".$_POST['companyEmail']."',
						footer 		= '".addslashes($_POST['companyFooter'])."'
				";
			
			$qry_update = new Query($sql);


		} else if($_POST['action'] == 'general') {

			$int_decimals = $_POST['generalDecimalsPrices'];
			if ($_POST['generalDecimalsPrices'] == '_NONE')
				$int_decimals = 0;
				
			$sql = "
					UPDATE user_settings
					SET bill_decimal_places = $int_decimals,
						bill_print_lines_to_eject = ".$_POST["generalBlankLines"].",
						bill_closing_time = '".$_POST['generalClosingTime']."',
						admin_product_type = ".$_POST['generalProductType']."
					WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];

			$qry_update = new Query($sql);

			/*
				the following settings get saved in the config.ini file
				located in the "include" folder
			*/
			$templateSection = $arrConfig->getItem("section", 'settings');
			
			if ($templateSection === false) {
				// create section
				$settingsSection = $arrConfig->createSection('settings');
				
				// create variables/values
				$settingsSection->createDirective("grid_font_size", intval($_POST['grid_font_size']));
				$settingsSection->createDirective("grid_rows", intval($_POST['generalGridRows']));
				$settingsSection->createDirective("decimals", intval($_POST['generalDecimalsQuantities']));
				$settingsSection->createDirective("currency_decimals", intval($int_decimals));
				$settingsSection->createDirective("code_sorting", $_POST['generalSorting']);
			}
			else {
				$font_size_directive =& $templateSection->getItem("directive", "grid_font_size");
				$font_size_directive->setContent($_POST['grid_font_size']);

				$rows_directive =& $templateSection->getItem("directive", "grid_rows");
				$rows_directive->setContent($_POST['generalGridRows']);

				$decimals_directive =& $templateSection->getItem("directive", "decimals");
				$decimals_directive->setContent(intval($_POST['generalDecimalsQuantities']));

				$currency_decimals_directive =& $templateSection->getItem("directive", "currency_decimals");
				$currency_decimals_directive->setContent(intval($int_decimals));
				
				$code_sorting_directive =& $templateSection->getItem("directive", "code_sorting");
				$code_sorting_directive->setContent($_POST['generalSorting']);
			}
			
			$config->writeConfig($str_root."include/config.ini", "IniFile");

		}

	} // action 


$sql = "SELECT * FROM company";
$qry_company = new Query($sql);


	
	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'Save Settings') {

			$int_decimals = $_POST['select_decimals'];
			if ($_POST['select_decimals'] == '_NONE')
				$int_decimals = 0;
				
			$str_update = "
					UPDATE user_settings
					SET bill_decimal_places = $int_decimals,
						bill_print_lines_to_eject = ".$_POST["bill_lines"].",
						bill_closing_time = '".$_POST['select_time']."',
						admin_product_type = ".$_POST['select_product_type']."
					WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			
			$qry_update = new Query($str_update);
			
			/*
				the following settings get saved in the config.ini file
				located in the "include" folder
			*/
			$templateSection = $arrConfig->getItem("section", 'settings');
			
			if ($templateSection === false) {
				// create section
				$settingsSection = $arrConfig->createSection('settings');
				
				// create variables/values
				//$settingsSection->createDirective("grid_font_size", intval($_POST['grid_font_size']));
				$settingsSection->createDirective("grid_rows", intval($_POST['grid_rows']));
				$settingsSection->createDirective("decimals", intval($_POST['select_decimals']));
				$settingsSection->createDirective("currency_decimals", intval($_POST['select_currency_decimals']));
				$settingsSection->createDirective("code_sorting", $_POST['code_sorting']);
			}
			else {
				//$font_size_directive =& $templateSection->getItem("directive", "grid_font_size");
				//$font_size_directive->setContent($_POST['grid_font_size']);
				$rows_directive =& $templateSection->getItem("directive", "grid_rows");
				$rows_directive->setContent($_POST['grid_rows']);
				$decimals_directive =& $templateSection->getItem("directive", "decimals");
				$decimals_directive->setContent(intval($_POST['select_decimals']));
				$currency_decimals_directive =& $templateSection->getItem("directive", "currency_decimals");
				$currency_decimals_directive->setContent(intval($_POST['select_currency_decimals']));
				$code_sorting_directive =& $templateSection->getItem("directive", "code_sorting");
				$code_sorting_directive->setContent($_POST['code_sorting']);
			}
			
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
	}
	
	$qry_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
	);
	
	$templateSection = $arrConfig->getItem("section", 'settings');
	
	if ($templateSection === false) {
		$int_grid_font_size = 12;
		$int_grid_rows = 15;
		$int_decimals = 2;
		$int_currency_decimals = 2;
		$str_code_sorting = 'STANDARD';
	}
	else {
		$font_size_directive =& $templateSection->getItem("directive", "grid_font_size");
		if ($font_size_directive === false) {
			$templateSection->createDirective("grid_font_size", 12);
			$font_size_directive =& $templateSection->getItem("directive", "grid_font_size");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		
		$grid_rows_directive =& $templateSection->getItem("directive", "grid_rows");
		if ($grid_rows_directive === false) {
			$templateSection->createDirective("grid_rows", 15);
			$grid_rows_directive =& $templateSection->getItem("directive", "grid_rows");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		
		$decimals_directive =& $templateSection->getItem("directive", "decimals");
		if ($decimals_directive === false) {
			$templateSection->createDirective("decimals", 2);
			$decimals_directive =& $templateSection->getItem("directive", "decimals");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		
		$currency_decimals_directive =& $templateSection->getItem("directive", "currency_decimals");
		if ($currency_decimals_directive === false) {
			$templateSection->createDirective("currency_decimals", 2);
			$currency_decimals_directive =& $templateSection->getItem("directive", "currency_decimals");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		
		$code_sorting_directive =& $templateSection->getItem("directive", "code_sorting");
		if ($code_sorting_directive === false) {
			$templateSection->createDirective("code_sorting", "STANDARD");
			$code_sorting_directive =& $templateSection->getItem("directive", "code_sorting");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		
		$int_grid_font_size = $font_size_directive->getContent();
		$int_grid_rows = $grid_rows_directive->getContent();
		$int_decimals = $decimals_directive->getContent();
		$int_currency_decimals = $currency_decimals_directive->getContent();
		$str_code_sorting= $code_sorting_directive->getContent();
	}
?>

<html>
<head>
    <link href="../include/styles.css" rel="stylesheet" type="text/css">

    <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body style="margin-top: 20px;">



<div class="container">

	  	<!--
	  		Message
	  	-->
	  	<div id="billing_error">
			<div class="row">
				<div class="col-md-12">
					<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:none;">
					  <var id="settings_alert_msg"></var>
					</div>
				</div>
			</div>
	  	</div> <!-- billing error -->



  		<div class="row">

			<div class="panel panel-primary">

				<div class="panel-heading">
					Company Details
				</div>

				<div class="panel-body">
				
				  	<div class="col-md-2">
				  	</div>

				  	<div class="col-md-8">

						<form class="form-horizontal" name="company" method="post">

							<div class="form-group">
								<label for="companyTitle" class="col-sm-3 control-label">Company Title</label>
								<div class="col-sm-9">
									<input type="text" name="companyTitle" class="form-control" id="companyTitle" readonly value="<?php echo $qry_company->FieldByName('title');?>" placeholder="Company Title">
								</div>
							</div>
							<div class="form-group">
								<label for="companyLegalName" class="col-sm-3 control-label">Legal Name</label>
								<div class="col-sm-9">
									<input type="text" name="companyLegalName" class="form-control" id="companyLegalName" value="<?php echo $qry_company->FieldByName('legal_name');?>" placeholder="Legal Name">
								</div>
							</div>
							<div class="form-group">
								<label for="companyTradeName" class="col-sm-3 control-label">Trade Name</label>
								<div class="col-sm-9">
									<input type="text" name="companyTradeName" class="form-control" id="companyTradeName" value="<?php echo $qry_company->FieldByName('trade_name');?>" placeholder="Auroville Foundation">
								</div>
							</div>
							<div class="form-group">
								<label for="companyTrust" class="col-sm-3 control-label">Trust</label>
								<div class="col-sm-9">
									<input type="text" name="companyTrust" class="form-control" id="companyTrust" value="<?php echo $qry_company->FieldByName('trust');?>" placeholder="Trust">
								</div>
							</div>

							<div class="form-group">
								<label for="companyGSTIN" class="col-sm-3 control-label">GSTIN</label>
								<div class="col-sm-9">
									<input type="text" name="companyGSTIN" class="form-control" id="companyGSTIN" value="<?php echo $qry_company->FieldByName('gstin');?>" placeholder="GSTIN">
								</div>
							</div>
							<div class="form-group">
								<label for="companyAddress" class="col-sm-3 control-label">Address</label>
								<div class="col-sm-9">
									<input type="text" name="companyAddress" class="form-control" id="companyAddress" value="<?php echo $qry_company->FieldByName('address');?>" placeholder="Address">
								</div>
							</div>
							<div class="form-group">
								<label for="companyPhone" class="col-sm-3 control-label">Phone</label>
								<div class="col-sm-9">
									<input type="text" name="companyPhone" class="form-control" id="companyPhone" value="<?php echo $qry_company->FieldByName('phone');?>" placeholder="Phone">
								</div>
							</div>
							<div class="form-group">
								<label for="companyEmail" class="col-sm-3 control-label">Email</label>
								<div class="col-sm-9">
									<input type="email" name="companyEmail" class="form-control" id="companyEmail" value="<?php echo $qry_company->FieldByName('email');?>" placeholder="Email">
								</div>
							</div>
							<div class="form-group">
								<label for="companyFooter" class="col-sm-3 control-label">Footer</label>
								<div class="col-sm-9">
									<input type="text" name="companyFooter" class="form-control" id="companyFooter" value="<?php echo $qry_company->FieldByName('footer');?>" placeholder="Footer for Invoice">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3"></label>
								<div class="col-sm-9">
									<button type="submit" value="company" name="action" id="btn-company" class="btn btn-primary">Save</button>
								</div>
							</div>

						</form>
						
					</div>

				  	<div class="col-md-2">
				  	</div>

				</div> <!-- panel-body -->
			</div> <!-- panel -->

		</div>


  		<div class="row">

			<div class="panel panel-primary">

				<div class="panel-heading">
					General Details
				</div>

				<div class="panel-body">

				  	<div class="col-md-2">
				  	</div>

				  	<div class="col-md-8">



						
						<form class="form-horizontal" name='global_settings' method='post'>

							<div class="form-group">
								<label for="generalProductType" class="col-sm-3 control-label">Product type:</label>
								<div class="col-sm-9">
									<select class="form-control" name="generalProductType" id="generalProductType">
										<option value=1 <?if ($qry_settings->FieldByName('admin_product_type') == 1) echo "selected"?>>Basic</option>
										<option value=2 <?if ($qry_settings->FieldByName('admin_product_type') == 2) echo "selected"?>>Consumable</option>
										<option value=3 <?if ($qry_settings->FieldByName('admin_product_type') == 3) echo "selected"?>>Book</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label for="generalSorting" class="col-sm-3 control-label">Product code sorting:</label>
								<div class="col-sm-9">
									<select class="form-control" name="generalSorting" id="generalSorting">
										<option value="STANDARD" <?if ($str_code_sorting == "STANDARD") echo "selected"?>>Standard</option>
										<option value="ALPHA_NUM" <?if ($str_code_sorting == "ALPHA_NUM") echo "selected"?>>Alpha-numeric</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label for="generalClosingTime" class="col-sm-3 control-label">Closing Time:</label>
								<div class="col-sm-9">
									<select class="form-control" name="generalClosingTime" id="generalClosingTime">
										<option value="11:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '11:00:00') echo "selected"; ?>>11:00 AM
										<option value="11:30:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '11:30:00') echo "selected"; ?>>11:30 AM
										<option value="12:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '12:00:00') echo "selected"; ?>>12:00 PM
										<option value="12:30:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '12:30:00') echo "selected"; ?>>12:30 PM
										<option value="13:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '13:00:00') echo "selected"; ?>>1:00 PM
										<option value="13:30:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '13:30:00') echo "selected"; ?>>1:30 PM
										<option value="13:55:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '13:55:00') echo "selected"; ?>>1:55 PM
										<option value="14:00:00" <?if ($qry_settings->FieldByName('bill_closing_time') == '14:00:00') echo "selected"; ?>>2:00 PM
									</select>
								</div>
							</div>

							<div class="form-group">
								<label for="generalBlankLines" class="col-sm-3 control-label">Number of blank lines after printing:</label>
								<div class="col-sm-9">
									<input type="text" name="generalBlankLines" class="form-control" id="generalBlankLines" value="<?php echo $qry_settings->FieldByName('bill_print_lines_to_eject')?>">
								</div>
							</div>

							<div class="form-group">
								<label for="generalGridRows" class="col-sm-3 control-label">Grid default number of rows:</label>
								<div class="col-sm-9">
									<select class="form-control" name="generalGridRows" id="generalGridRows">
										<option value="10" <?php if ($int_grid_rows==10) echo "selected";?>>10</option>
										<option value="15" <?php if ($int_grid_rows==15) echo "selected";?>>15</option>
										<option value="20" <?php if ($int_grid_rows==20) echo "selected";?>>20</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label for="generalDecimalsQuantities" class="col-sm-3 control-label">Decimal places for quantities:</label>
								<div class="col-sm-9">
									<select class="form-control" name="generalDecimalsQuantities" id="generalDecimalsQuantities">
										<option value=_NONE <?if ($int_decimals == 0) echo "selected"?>>None</option>
										<option value=1 <?if ($int_decimals == 1) echo "selected"?>>1</option>
										<option value=2 <?if ($int_decimals == 2) echo "selected"?>>2</option>
										<option value=3 <?if ($int_decimals == 3) echo "selected"?>>3</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label for="generalDecimalsPrices" class="col-sm-3 control-label">Decimal places for prices:</label>
								<div class="col-sm-9">
									<select class="form-control" name="generalDecimalsPrices" id="generalDecimalsPrices">
										<option value=_NONE <?if ($int_currency_decimals == 0) echo "selected"?>>None</option>
										<option value=1 <?if ($int_currency_decimals == 1) echo "selected"?>>1</option>
										<option value=2 <?if ($int_currency_decimals == 2) echo "selected"?>>2</option>
										<option value=3 <?if ($int_currency_decimals == 3) echo "selected"?>>3</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3"></label>
								<div class="col-sm-9">
									<button type="submit" value="general" name="action" id="btn-general" class="btn btn-primary">Save</button>
								</div>
							</div>

						</form>

					</div>

				  	<div class="col-md-2">
				  	</div>

				</div> <!-- panel-body -->
			</div> <!-- panel -->

		</div>



</div> <!-- container -->


    <script src="../include/js/jquery-3.2.1.min.js"></script>

	<script>
				
		$(" #btn-company ").on("click", function(e) {

			$(" #settings_alert_msg ").html( "Company settings saved" );
			$(" #settings_alert ").removeClass( "alert-danger" ).addClass( "alert-info" );
			$(" #settings_alert ").show();

			//setTimeout(function() {
			// 	$(" #settings_alert ").fadeTo(2000, 500).hide();
			//}, 2000);
		});
		
	</script>

</body>
</html>