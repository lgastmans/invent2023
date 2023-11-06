<?
/**
* 
* @version 	$Id: toolmaster.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		14 Oct 2005
* @module 	Measurement Unit Master
* @name  	measurement.php
* 
* This file uses the Grid component to generate the measurement unit grid
*/

	$str_cur_module='Admin';
	
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("../common/functions.inc.php");
	require_once("db.inc.php");
	require_once("grid.inc.php");
	require_once("browser_detection.php");
//	require_once("DB.php");
//	require_once("db_params.php");

	$int_access_level = (getModuleAccessLevel('Admin'));
	
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	} 
	
	$_SESSION['int_admin_selected']=5;
	
	/*
		get the reseller settings from the config file
	*/
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$templateSection = $arrConfig->getItem("section", 'reseller');
	if ($templateSection === false)
		$templateSection = $arrConfig->createSection('reseller');
	
	$active_directive =& $templateSection->getItem("directive", "active");
	if ($active_directive === false) {
		$templateSection->createDirective("active", 'N');
		$active_directive =& $templateSection->getItem("directive", "active");
		$config->writeConfig($str_root."include/config.ini", "IniFile");
	}
	$reseller_active = $active_directive->getContent();
	
?>

<!DOCTYPE html>
<html lang="en">
<head><TITLE></TITLE>
	
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<link rel="stylesheet" href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css">

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>


	<!-- <script src="jquery.blockUI.js"></script> -->
	<!--<script src="http://malsup.github.io/jquery.blockUI.js"></script>-->
	<script src="../include/js/blockUI.js"></script>
	<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

	<link href="../include/styles.css" rel="stylesheet" type="text/css">

	<style>
		.loader {
		  border: 6px solid #f3f3f3; /* Light grey */
		  border-top: 6px solid #3498db; /* Blue */
		  border-radius: 50%;
		  width: 45px;
		  height: 45px;
		  animation: spin 2s linear infinite;
		}

		@keyframes spin {
		  0% { transform: rotate(0deg); }
		  100% { transform: rotate(360deg); }
		}

	</style>		

</head>
<body margin=20 padding=20><br>


<div class="container">

	<form name='toolmaster' method='get'>

		<h1>Administration Interface</h1>
			


		<?
			if ($reseller_active == 'Y') {
		?>

			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Reseller</h3>
				</div>
				<div class="panel-body">

					Send updates to reseller
					<input type="button" name="reseller_send" value="send" onclick="javascript:document.location='server/send_data.php'">
					<br>

					Received orders for reseller
					<input type="button" name="reseller_receive" value="receive" onclick="javascript:document.location='server/receive_data.php'">
					<br>

					Export statistics data for Idoya
					<input type="button" name="reseller_statistics" value="Export to Excel" onclick="javascript:document.location='server/export_stat_data.php'">
					<br>

				</div>
			</div>


		<?
			}
		?>


		<br>


		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Software Update</h3>
			</div>
			<div class="panel-body">
				Check for updates to the software...
				<br><br>
				<input type='button' name='action' value='Check' onclick="javascript:document.location='software_updater.php'" class='settings_button'>
			</div>
		</div>


		<br>


		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">New Month</h3>
			</div>
			<div class="panel-body">
				Opens a new month.  Use this at the beginning of every month.
				<br><br>
				<input type='button' name='action' value='Create Month' onclick="document.location='newmonth.php'" class='settings_button'>

			</div>
		</div>


		<br>


		<div id="fs_alert" class="alert alert-warning alert-dismissible" role="alert">
		  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		  <strong>Completed:</strong> Financial Service accounts updated successfully.
		</div>


		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Load FS Accounts</h3>
			</div>
			<div class="panel-body">
				Download the latest list Financial Service accounts.
				<br><br>

				<div id="load_msg"><span></span></div>
				<input type='button' name='action' id="load_all" value='Load All' class='settings_button'>
				<!-- <input type='button' name='action' value='Set FS Login' onclick='javascript:set_fs_login();' class='settings_button'> -->
				<br><br>

				<!-- <div id="progressbar">status</div> -->

				<div id="progressCaption" style="display:none;"><font class='normaltext'>Importing FS accounts</font></div>

				<div class="progress" style="display:none;">
					<div id="progressbar" class="progress-bar" style="min-width:2em;width:0%;"></div>
				</div>
				

			</div>
		</div>


		<br>


		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Export Transfers</h3>
			</div>
			<div class="panel-body">
				Exports pending transfers to the financial service.
				<br><br>
				<input type='button' name='action' value='Export' onclick="javascript:export_all();" class='settings_button'>
				<select name='select_export' class='select_200'>
					<option value='pending'>Pending</option>
					<option value='Insufficient Funds'>Insufficient Funds</option>
				</select>
			</div>
		</div>


		<br>


		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Backup Database</h3>
			</div>
			<div class="panel-body">
				Backup up the database to a zipped file that can be uploaded to another storage medium.
				<br><br>
				<input type='button' name='action' value='Backup Now' onclick="javascript:document.location='mysql_backup.php?action=backup'" class='settings_button'>
			</div>
		</div>


	</form>

</div>



	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>


	<!-- <script src="jquery.blockUI.js"></script> -->
	<!--<script src="http://malsup.github.io/jquery.blockUI.js"></script>-->
	<script src="../include/js/blockUI.js"></script>
	<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>


<script type="text/javascript"> 

	$(document).ready(function() {


        var progressbar = {};

        $(function () {

            progressbar = {

                /** initial progress */
                progress: 0,

                /** maximum width of progressbar */
                progress_max: 0,

                /** The inner element of the progressbar (filled box). */
                $progress_bar: $('#progressbar'),

                /** Method to set the progressbar.
                 */
                set: function (num) {
                    if (this.progress_max && num) {
                        this.progress = num / this.progress_max * 100;
                        //console.log('percent: ' + this.progress + '% - ' + num + '/' + this.progress_max);
                        this.$progress_bar.width(String(this.progress) + '%');
                        if ((num % 50) == 0)
                        	$('#progressCaption').html('Updated ' + num + ' out of ' + this.progress_max);
                    }
                }

            };

        });


		
		$('#fs_alert').hide();

        $('#load_all').click(function() { 
            
        	$.blockUI({ message: "<br>Please wait while the FS accounts <br> are downloaded from the server.<br><br> This can take a few minutes.<br><br>" }); 
        	
            $.ajax({ 
	            type : 'POST',
	            url  : 'accountloadall.php',
	            //async: false,
            })
	        .done(function( msg ) {

				
				$.unblockUI({ 

                	onUnblock: function(){ 

			            if (msg!=='ERROR') {

			            	$('.progress').show();
							$('#load_all').prop('disabled', true);
							$('#progressbar').css('background-color', 'dark-green');
		            		$('#progressCaption').show();

			            	var data = jQuery.parseJSON( msg );

							var iterations = Object.keys(data).length / 5;

							progressbar.progress_max = iterations;

							var loop = function (value, data) {

							    progressbar.set(value);

							    if (value < iterations) setTimeout(function () {

							        loop(value + 1, data)

									/*
										Sample
											"R0_Name":"NAGAPPAN & SUGANYA",
											"R0_Number":"102080",
											"R0_Type":"3",
											"R0_Disable":"0",
											"R0_CCID":"691"
									*/

							        $.ajax({ 
							            type : 'POST',
							            //async: false,
							            //cache: false,
							            url  : 'accountsave.php',
							            data : { RName: data["R"+value+"_Name"], RNumber: data["R"+value+"_Number"], RType: data["R"+value+"_Type"], RDisable: data["R"+value+"_Disable"], RCCID: data["R"+value+"_CCID"]},
							        })
							        .done(function( msg ){
							        	console.log('Updated ' + msg)    
							        })
									

							    }, 30);

							    else {
							    	
							        //$('#progressbar').css('background-color', 'grey');
							        $('#load_all').prop('disabled',false);
        		            		$('#progressCaption').html('Completed updating FS accounts');
        		            		progressbar.set(0);
        		            		setTimeout(function(){
        		            			$('#progressCaption').html('');
        		            			$('#progressCaption').hide();
        		            			$('.progress').hide();
        		            		}, 5000)
							    }
							}

							loop(1, data);

			        	}
			        	else {
			        		//$('#progressCaption').show();
			        		//$('#progressCaption').html(msg);
			        	}
				 	}
            	});
			});
		}); 

		

	}); 
</script> 

<script language='javascript'>

	function load_modified() {
		myWin = window.open("accountload.php",'import','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=550,height=350,top=0');
		myWin.moveTo((screen.availWidth/2 - 550/2), (screen.availHeight/2 - 350/2));
		myWin.focus();
	}
	
	function load_all() {

		// myWin = window.open("accountloadall.php",'import','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=350,top=0');
		// myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 350/2));
		// myWin.focus();

	}
	
	function update_accounts() {
		myWin = window.open("update_fsaccounts.php",'import','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=350,top=0');
		myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 350/2));
		myWin.focus();
	}
	
	function export_all() {
		var oSelectTransfer = document.toolmaster.select_export;
		myWin = window.open("accounttransfer.php?transfer_type="+oSelectTransfer.value,'export','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=350,top=0');
		myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 350/2));
		myWin.focus();
	}
	
	function set_fs_login() {
		myWin = window.open("setFSLogin.php",'set_fs_login','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=350,top=0');
		myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 350/2));
		myWin.focus();
	}
	
	function selectDatabase() {
		var oSelectDatabase = document.getElementById('select_database');
		document.location = 'toolmaster.php?'+
			'action=select'+
			'&database='+oSelectDatabase.value;
	}
	function load_from_file() {
		myWin = window.open("accountloadfile.php",'import','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=350,top=0');
		myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 350/2));
		myWin.focus();
	}
</script>



</body>
</html>