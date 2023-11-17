<?
/**
* 
* @version 	$Id: login.php,v 1.2 2006/02/14 09:55:35 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Login Page
* @name  	login.php
*
* The login page clears any existing sessions and prompts the user for 
* a login name and password.  It then submits the variables to itself
* and compares them with the database.  If they match, the following
* session variables are set and the user is redirected to index.php:
*
* $bool_logged_in		True if the user is registerd
* $int_page_size_detail		Number of rows in the detail grids
* $int_page_size		Number of rows in the main grids
* $int_user_id			User Id of logged in user
* $str_user_name		User name
* $int_user_type		User Type (1 = normal, 2=admin)
* $int_month_loaded		Month that information is displayed for
* $int_year_loaded		Year for information displayed

*/

//  require_once("/var/www/html/Gubed/Gubed.php");
session_start();
session_unset();

$bool_root_folder=true;

require "include/const.inc.php";
require_once("db.inc.php");
require_once("Config.php");

	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$templateSection = $arrConfig->getItem("section", 'billing');
	if ($templateSection === false) {
		$settingsSection = $arrConfig->createSection('billing');
		$settingsSection->createDirective("connect_method", CONNECT_ONLINE);
		$config->writeConfig($str_root."include/config.ini", "IniFile");
		
		$_SESSION['connect_mode'] = CONNECT_ONLINE;
	}
	else {
		$connect_method_directive =& $templateSection->getItem("directive", "connect_method");
		if ($connect_method_directive === false) {
			$templateSection->createDirective("connect_method", CONNECT_ONLINE);
			$connect_method_directive =& $templateSection->getItem("directive", "connect_method");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		
		$_SESSION['connect_mode'] = $connect_method_directive->getContent();
	}

//
// clear logged in session
//
$_SESSION["bool_logged_in"] = false;



$company = new Query("
	SELECT *
	FROM company
");

//
// if we are attempting to log in
//
if (!empty($_POST["username"])) {

	$str_password = base64_encode($_POST["password"]);
	
	$query = new Query("
		SELECT * 
		FROM user
		WHERE deleted='N'
			AND username='".$_POST["username"]."'
			AND password='".$str_password."'
	");
	
	if ($query->RowCount()>0) {

		$_SESSION["bool_logged_in"] = true;
		
		$_SESSION["int_user_id"] = $query->FieldByName("user_id");
		$_SESSION["str_user_name"] = $_POST["username"];

		// current date
 
		$_SESSION["int_month_loaded"] = Date("n",time());
		$_SESSION["int_year_loaded"] = Date("Y",time());

		$_SESSION["int_page_size_detail"] = 50;
		$_SESSION["int_page_size"] = 20;

		$_SESSION["int_user_type"] = $query->FieldByName("user_type");
		$_SESSION["int_current_storeroom"] = $query->FieldByName("default_storeroom_id");
		$_SESSION["int_user_prediction_method"] = $query->FieldByName("po_prediction_method");
		
		$_SESSION["str_user_color_scheme"] = $query->FieldByName("color_scheme");
		$_SESSION["str_user_font_size"] = $query->FieldByName("font_size");
		$_SESSION["int_user_printing_type"] = $query->FieldByName("printing_type");
		$_SESSION['str_user_can_change_bill_date'] = $query->FieldByName('can_change_bill_date');
		$_SESSION['str_user_can_change_price'] = $query->FieldByName('can_change_price');
		$_SESSION['str_user_supplier_access'] = $query->FieldByName('supplier_access');
		
		$_SESSION['global_current_supplier_id'] = 0;
		$_SESSION['global_current_product_id'] = 0;


		$_SESSION["company_gstin"] = $company->FieldByName('gstin');
		

		/*
			database backup
		*/
		require_once('admin/mysql_backup.php');
		backup_db('N', 'db_backup_');
		//backup_db('N', '', date('Y')."_".date('m').".sql.gz");


		/*
			default settings for billing screen
		*/
		$salespersons = new Query("SELECT * FROM salespersons ORDER BY first");
		$salespersons->First();

		$_SESSION['current_bill_day'] = date('j');
		$_SESSION['current_bill_type'] = BILL_CASH;
		$_SESSION["sales_promotion"] = 0;
		$_SESSION['bill_salesperson'] = $salespersons->FieldByName("id");
		$_SESSION['client_id'] = 0;


		$_SESSION['save_counter'] = 0;
		
		//================================
        // set program account information
        //--------------------------------
		$qry_settings = new Query("
			SELECT *
			FROM user_settings
			WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
		");

		$_SESSION['int_application_pid'] = base64_decode($qry_settings->FieldByName('fs_user'));
		$_SESSION['int_application_pin'] = base64_decode($qry_settings->FieldByName('fs_password'));


		//=======================
		// set module permissions
		//-----------------------
		$_SESSION["arr_modules"]=array();
		require_once("include/module.inc.php");
		$qry_base = new Query("SELECT * FROM module WHERE active='Y'");
		$bool_register_modules=true;
		for ($i=0;$i<$qry_base->RowCount();$i++) {
			require_once($qry_base->FieldByName("module_folder")."module.inc.php");
			$qry_base->Next();
		}

		$bool_register_modules=false;
		
		//===================================
		// reset cancelled orders active
		// where the cancel till date is less
		// than today's date
		//-----------------------------------
		$qry_base->Query("
			UPDATE ".Monthalize('orders')."
			SET order_status = 4
			WHERE order_status = 2 AND CURDATE() > date_cancel_till
		");
		
		//============================================
		// create the order bills for the current date
		// in case the Orders module is enabled
		//--------------------------------------------
/*		$qry_base->Query("SELECT * FROM module WHERE module_id = 7");
		if ($qry_base->RowCount() > 0) {
			require "orders/order_functions.inc.php";
			create_order_bills(time());
		}
*/		
		//==================================
		// update the last login information
		//----------------------------------
		$query->Query("
			UPDATE user
			SET last_login=\"".Date('Y-m-d H:i:s',time())."\"
			WHERE user_id = " . $_SESSION["int_user_id"]);

//    		die();
    ?><html><body>
<script language=javascript>

	
//    mywin=window.open("index.php",'main','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes');
//	mywin.moveTo(0,0);
//    mywin.focus();
	
	window.location = "index.php";

</script></body></html>
    <?
    //exit;
  	}

}



?>

<html>
<head>
	<TITLE><? echo $company->FieldByName('title'); ?></TITLE>
	<link href="include/styles.css" rel="stylesheet" type="text/css">
	<link href="include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
@import url(http://fonts.googleapis.com/css?family=Roboto);

/****** LOGIN MODAL ******/
.loginmodal-container {
  padding: 30px;
  max-width: 350px;
  width: 100% !important;
  background-color: #F7F7F7;
  margin: 0 auto;
  border-radius: 2px;
  box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  font-family: roboto;
}

.loginmodal-container h1 {
  text-align: center;
  font-size: 1.8em;
  font-family: roboto;
}

.loginmodal-container input[type=submit] {
  width: 100%;
  display: block;
  margin-bottom: 10px;
  position: relative;
}

.loginmodal-container input[type=text], input[type=password] {
  height: 44px;
  font-size: 16px;
  width: 100%;
  margin-bottom: 10px;
  -webkit-appearance: none;
  background: #fff;
  border: 1px solid #d9d9d9;
  border-top: 1px solid #c0c0c0;
  /* border-radius: 2px; */
  padding: 0 8px;
  box-sizing: border-box;
  -moz-box-sizing: border-box;
}

.loginmodal-container input[type=text]:hover, input[type=password]:hover {
  border: 1px solid #b9b9b9;
  border-top: 1px solid #a0a0a0;
  -moz-box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
  -webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.loginmodal {
  text-align: center;
  font-size: 14px;
  font-family: 'Arial', sans-serif;
  font-weight: 700;
  height: 36px;
  padding: 0 8px;
/* border-radius: 3px; */
/* -webkit-user-select: none;
  user-select: none; */
}

.loginmodal-submit {
  /* border: 1px solid #3079ed; */
  border: 0px;
  color: #fff;
  text-shadow: 0 1px rgba(0,0,0,0.1); 
  background-color: #4d90fe;
  padding: 17px 0px;
  font-family: roboto;
  font-size: 14px;
  /* background-image: -webkit-gradient(linear, 0 0, 0 100%,   from(#4d90fe), to(#4787ed)); */
}

.loginmodal-submit:hover {
  /* border: 1px solid #2f5bb7; */
  border: 0px;
  text-shadow: 0 1px rgba(0,0,0,0.3);
  background-color: #357ae8;
  /* background-image: -webkit-gradient(linear, 0 0, 0 100%,   from(#4d90fe), to(#357ae8)); */
}

.loginmodal-container a {
  text-decoration: none;
  color: #666;
  font-weight: 400;
  text-align: center;
  display: inline-block;
  opacity: 0.6;
  transition: opacity ease 0.5s;
} 

.login-help{
  font-size: 12px;
}	
</style>

</head>

<body leftmargin='20' rightmargin='20' topmargin='20' bottommargin='20'>

	<form method="POST" name="loginForm" action="login.php">

		<div class="page-header text-center">
		  <h1><? echo $company->FieldByName('title'); ?></h1>
		  <h1><small><? echo $company->FieldByName('legal_name')." - ".$company->FieldByName('trade_name'); ?></small></h1>
		</div>
		<div class="modal-dialog">
			<div class="loginmodal-container">
				<h1>Login </h1>
				<br>
			  <form>
				<input type="text" name="username" placeholder="Username">
				<input type="password" name="password" placeholder="Password">
				<input type="submit" name="login" class="login loginmodal-submit" value="Login">
			  </form>
				
			  <div class="login-help text-center">
				<!-- <a href="#">Register</a> - <a href="#">Forgot Password</a> -->
			  </div>
			</div>
		</div>

	</form>		  


<!-- Footer -->
<!-- <footer class="page-footer font-small blue pt-4">

    <div class="footer-copyright text-center py-3">
    	<p><?php echo $company->FieldByName('address'); ?></p>
    	<p><?php echo $company->FieldByName('phone'); ?></p>
    </div>

</footer>
 -->



	<script language="javascript">
		document.loginForm.username.focus();
	</script>

</body>
</html>