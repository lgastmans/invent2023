<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	$int_access_level = (getModuleAccessLevel('Admin'));
	
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	} 
	
	$_SESSION['int_admin_selected']=11;
	
	$qry_module = new Query("SELECT * FROM module LIMIT 1");
?>

<html>
<head>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
	<script language='javascript'>
		function webTransfers() {
			var oSelectDay = document.toolmaster.select_day;
			document.location = 'verification/fs_verify_web_transfers.php?day='+oSelectDay.value;
		}
		
		function gotoPage(intPage) {
			if (confirm('Are you sure?')) {
				if (intPage == 1)
					document.location = 'verification/stock_reset_ord_res.php';
				else if (intPage == 2)
					document.location = 'verification/stock_reset_bill_reserved.php';
				else if (intPage == 3)
					document.location = 'verification/stock_setall_global_price.php';
			}
		}
		
	</script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
    if ($int_access_level != ACCESS_ADMIN) {
        die('You do not have rights to access this module');
    }
?>

<form name='toolmaster' method='get'>
<br><font class='title'>Database Integrity Verification Tools</font><br><br>

<?
//========================
// Stock module
//------------------------
$qry_module->Query("SELECT * FROM module WHERE module_id = 1");
if ($qry_module->RowCount() > 0) {
	boundingBoxStartLabel("600", "Stock", 547); ?>
	<br>
	<table border='0' cellpadding='2' cellspacing='0'>
		<tr>
			<td class='normaltext'>
				<ul>
				<li><a class='settings_link' href='verification/stock_closing_balance.php'>Check the closing balance of each product for the current month/year and storeroom</a>
				</ul>
			</td>
		</tr>
		<tr>
			<td class='normaltext'>
				<ul>
				<li><a class='settings_link' href='javascript:gotoPage(1)'>Reset the 'Reserved' and 'Ordered' quantities for the current storeroom</a>
				</ul>
			</td>
		</tr>
		<tr>
			<td class='normaltext'>
				<ul>
				<li><a class='settings_link' href='javascript:gotoPage(2)'>Reset the 'Bill Reserved' to zero for all products</a>
				</ul>
			</td>
		</tr>
		<tr>
			<td class='normaltext'>
				<ul>
				<li><a class='settings_link' href='javascript:gotoPage(3)'>Set all products to global price</a>
				</ul>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
	</table>
<?
        boundingBoxEndLabel("600");
    }
?>
<br><br>

<?
	//========================
	// FS Accounts module
	//------------------------
	$qry_module->Query("SELECT * FROM module WHERE module_id = 5");
	if ($qry_module->RowCount() > 0) {
		boundingBoxStartLabel("600", "FS&nbsp;Accounts", 505); ?>
		<br>
		<table border='0' cellpadding='2' cellspacing='0'>
			<tr>
				<td class='normaltext'>
					<ul>
					<li><a class='settings_link' href='verification/fs_duplicate_transfers.php'>Search for duplicate transfers</a>
					</ul>
				</td>
			</tr>
			<tr>
				<td class='normaltext'>
					<ul>
					<li><a class='settings_link' href='verification/fs_verify_transactions.php'>Verifies whether all the FS account bills marked 'resolved' have corresponding transfers</a>
					</ul>
				</td>
			</tr>
			<tr>
				<td class='normaltext'>
					<ul>
					<li>
						<a class='settings_link' href='javascript:webTransfers();'>Cross check all transfers marked complete with transfers on the Financial Service server</a>
						<br>
						for the following day :
						<select name='select_day'>
						<?
							if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
								for ($i=1;$i<=date('d',time());$i++) {
									echo "<option value=$i>".$i."</option>\n";
								}
							}
							else {
								for ($i=1;$i<=DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);$i++) {
									echo "<option value=$i>".$i."</option>\n";
								}
							}
						?>
						</select>
					</ul>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
<?
		boundingBoxEndLabel("600");
	}
?>
<br><br>

<?
	//========================
	// PT Accounts module
	//------------------------
	$qry_module->Query("SELECT * FROM module WHERE module_id = 6");
	if ($qry_module->RowCount() > 0) {
		boundingBoxStartLabel("600", "PT&nbsp;Accounts", 505); ?>
	<br>
		<table border='0' cellpadding='2' cellspacing='0'>
			<tr>
				<td class='normaltext'>
					<ul>
					<li><a class='settings_link' href='verification/pt_accounts_totals.php'>Check the closing balance against the bill totals per account</a>
					</ul>
				</td>
			</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		</table>
<?
        boundingBoxEndLabel("600");
    }
?>
<br><br>


<?
	//========================
	// Orders module
	//------------------------
	$qry_module->Query("SELECT * FROM module WHERE module_id = 7");
	if ($qry_module->RowCount() > 0) {
		boundingBoxStartLabel("600", "Orders", 505); ?>
	<br>
		<table border='0' cellpadding='2' cellspacing='0'>
			<tr>
				<td class='normaltext'>
					<ul>
					<li><a class='settings_link' href='verification/orders_verify_amount.php'>Check orders with amount zero and reset transfers</a>
					</ul>
				</td>
			</tr>
			<tr>
				<td class='normaltext'>
					<ul>
					<li><a class='settings_link' href='verification/orders_duplicate_transfers.php'>Check for duplicate transfers</a>
					</ul>
				</td>
			</tr>
			<tr>
				<td class='normaltext'>
					<ul>
					<li><a class='settings_link' href='verification/orders_duplicate_bills.php'>Check for duplicate bills</a>
					</ul>
				</td>
			</tr>

			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
<?
        boundingBoxEndLabel("600");
    }
?>
<br><br>

    
</form>
</body>
</html>