<?
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

	if (IsSet($_GET["id"])) 
		$int_id = $_GET["id"];
	else
		$int_id = 0;

	$int_access_level = (getModuleAccessLevel('Accounts'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}

	if (IsSet($_POST["action"])) {
		if ($_POST["action"] == "save") {
			if ((!empty($_POST["status_list"])) && (!empty($_POST["cur_id"]))) {
				if ($_POST["status_list"] == "_ZERO")
					$int_status = 0;
				else
					$int_status = $_POST["status_list"];

				$qry_update = new Query("
					UPDATE ".Monthalize('account_transfers')."
					SET transfer_status = ".$int_status."
					WHERE (transfer_id = ".$_POST["cur_id"].")
				");
				if ($qry_update->b_error == true) {
					echo "error changing status: ".mysql_error();
				}
				else {
					echo "<script language=\"javascript\">";
					echo "window.opener.document.location=window.opener.document.location.href;";
					echo "window.close();";
					echo "</script>";
				}
			}
		} // end of action = save
	} // end of IsSet

	$qry = new Query("SELECT
		tr.transfer_id,
		tr.date_created,
		tr.account_from,
		tr.cc_id_from,
		tr.cc_id_to,
		tr.account_to,
		tr.description,
		tr.date_completed,
		tr.amount,
		tr.transfer_status,
		u.username,
		ac.account_name
	FROM 
		".Monthalize('account_transfers')." tr
		INNER JOIN user u ON u.user_id = tr.user_id
		INNER JOIN account_cc ac ON ac.cc_id = tr.cc_id_from
	WHERE (tr.transfer_id = $int_id)
	");

?>

<script language="javascript">

	function CloseWindow() {
		window.opener.document.location=window.opener.document.location.href;
		window.close();
	}

	function setStatus() {
		if (confirm("Are you sure you want to change the status?"))
		  transfer_status.submit();
  }

</script>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/bill_styles.css" />
</head>
<body leftmargin=5 topmargin=5 marginwidth=0 marginheight=0 bgcolor="#DADADA">
<form name="transfer_status" method="POST" onsubmit="return false">

	<table width="98%" height="30" border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td width="120px" class="headertext_r">Account:</td>
			<td class="headertext"><?echo $qry->FieldByName('account_from')?></td>
		</tr>
		<tr>
			<td class="headertext_r">Name:</td>
			<td class="headertext"><?echo $qry->FieldByName('account_name')?></td>
		</tr>
		<tr>
			<td class="headertext_r">Amount:</td>
			<td class="headertext"><?echo $qry->FieldByName('amount')?></td>
		</tr>
		<tr>
			<td class="headertext_r">Status:</td>
			<td  bgcolor="#b9b9b9" class="headertext">
				<?
					if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_PENDING)
						echo "Pending";
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS)
						echo "No Funds";
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_ERROR)
						echo "Error";
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_CANCELLED)
						echo "Cancelled";
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_HOLD)
						echo "Hold";
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_COMPLETE)
						echo "Complete";
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_REVIEW)
						echo "Review";
				?>
			</td>
		</tr>
		<tr>
			<td class="headertext_r">Change to:</td>
			<td>
				<?
					if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_COMPLETE) {
						if ($int_access_level > ACCESS_READ) {
							?>
							<select name="status_list">
								<option value="_ZERO">Pending</option>
								<option value="<?echo ACCOUNT_TRANSFER_CANCELLED?>">Cancelled</option>
							</select>
							<?
						}
						else
							echo "<font color=\"red\">The status of this transfer cannot be modified</font><br>";
					}
					
					if ($int_access_level > ACCESS_READ) {
					if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_PENDING) {
					?>
						<select name="status_list">
							<option value="<?echo ACCOUNT_TRANSFER_CANCELLED?>">Cancelled</option>
							<option value="<?echo ACCOUNT_TRANSFER_COMPLETE?>">Complete</option>
						</select>
					<?
					}
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_CANCELLED) {
					?>
						<select name="status_list">
							<option value="_ZERO">Pending</option>
							<option value="<?echo ACCOUNT_TRANSFER_COMPLETE?>">Complete</option>
						</select>
					<?
					}
					else if ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_REVIEW) {
					?>
						<select name="status_list">
							<option value="_ZERO">Pending</option>
							<option value="<?echo ACCOUNT_TRANSFER_CANCELLED?>">Cancelled</option>
							<option value="<?echo ACCOUNT_TRANSFER_COMPLETE?>">Complete</option>
						</select>
					<?
					}
				}
  				
					if (($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_ERROR) || ($qry->FieldByName('transfer_status') == ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS)) {
					?>
						<select name="status_list">
							<? if ($int_access_level > ACCESS_READ) { ?>
								<option value="_ZERO">Pending</option>
  							<option value="<?echo ACCOUNT_TRANSFER_CANCELLED?>">Cancelled</option>
  							<option value="<?echo ACCOUNT_TRANSFER_COMPLETE?>">Complete</option>
  						<? } ?>
  						<option value="<?echo ACCOUNT_TRANSFER_REVIEW?>">Review</option>
						</select>
					<?
					}
  				else {
						if ($int_access_level <= ACCESS_READ) 
							echo "<font color=\"red\">Insufficient rights to change the status of this transfer</font><br>";
  				}
				?>
			</td>
		</tr>
		<tr>
			<td align="right">
  			<input type="hidden" name="action" value="save">
  			<input type="hidden" name="cur_id" value="<?echo $int_id?>">
  			<input type="button" class="v3button" name="Save" value="Save" onclick="setStatus()">
			</td>
			<td>
				<input type="button" class="v3button" name="Close" value="Close" onclick="CloseWindow()">
			</td>
		</tr>
	</table>

</form>
</body>
</html>