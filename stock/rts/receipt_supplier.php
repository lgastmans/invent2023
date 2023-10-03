<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

  	
	if (IsSet($_GET["action"])) {

		if ($_GET["action"] == 'cancel') {

			unset($_SESSION["arr_item_batches"]);	
			unset($_SESSION["arr_total_qty"]);
			$_SESSION['current_bill_day'] = date('j');
			$_SESSION['bill_total'] = 0;
			$_SESSION['current_supplier_id'] = 0;
			$_SESSION['current_discount'] = 0;
			$_SESSION['current_note'] = '';

			echo "<script language=\"javascript\">;";
			echo "parent.frames[\"frame_list\"].document.location = \"receipt_list.php\";";
			echo "</script>";
		}
	}
	
	// get the list of suppliers
	$qry = new Query("
		SELECT *
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");
?>

<script language="javascript">

	function setSupplier() {
		oTextBoxSupplier = document.receipt_supplier.list_supplier;
		oTextBoxDay = document.receipt_supplier.list_day;
		oTextBoxNote = document.receipt_supplier.note;

		parent.frames["frame_enter"].document.location = "receipt_enter.php?action=set_supplier&supplier_id=" + oTextBoxSupplier.value+"&receipt_day="+oTextBoxDay.value;
		parent.frames["frame_action"].document.location = "receipt_action.php?action=set_supplier&supplier_id="+oTextBoxSupplier.value+"&receipt_day="+oTextBoxDay.value+"&note="+oTextBoxNote.value;
	}

/*
	function setReceiptDay() {
		oTextBoxSupplier = document.receipt_supplier.list_supplier;
		oTextBoxDay = document.receipt_supplier.list_day;
		oTextBoxNote = document.receipt_supplier.note;

		parent.frames["frame_enter"].document.location = "receipt_enter.php?action=set_supplier&supplier_id=" + oTextBoxSupplier.value+"&receipt_day="+oTextBoxDay.value;
		parent.frames["frame_action"].document.location = "receipt_action.php?action=set_supplier&supplier_id="+oTextBoxSupplier.value+"&receipt_day="+oTextBoxDay.value+"&note="+oTextBoxNote.value;
	}
	
	function setNote() {
		oTextBoxBillNumber = document.receipt_supplier.bill_number;
		oTextBoxSupplier = document.receipt_supplier.list_supplier;
		oTextBoxDay = document.receipt_supplier.list_day;
		oTextBoxNote = document.receipt_supplier.note;

		var url = "receipt_action.php?action=set_supplier&supplier_id="+oTextBoxSupplier.value+"&receipt_day="+oTextBoxDay.value+"&note="+oTextBoxNote.value+"&bill_number="+oTextBoxBillNumber.value;

		parent.frames["frame_action"].document.location = url;
	}
*/

</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=0 topmargin=2>

<form name="receipt_supplier" method="GET">

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr><td align='center'>
<?
	boundingBoxStart("750", "../../images/blank.gif");
?>

	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	
	<tr>
		<td class="normaltext">
			D.N. Reference:
			<input type="text" value="<?php echo $_SESSION['current_bill_number']; ?>" name="bill_number" id="bill_number" class="input_100">
		</td>
		<td class="normaltext">
			<select name="list_day" id="list_day" class="select_50" <?php if (!is_null($_SESSION['stock_rts_id'])) echo "disabled"; ?> >
				<?
				$day = date('j');
				if ($_SESSION['current_supplier_id'] != 0)
					$day = $_SESSION['current_bill_day'];

				for ($i=1; $i<=date('j'); $i++) {
					if ($i == $day)
						echo "<option value=".$i." selected=\"selected\">".$i;
					else
						echo "<option value=".$i.">".$i;
				}
				?>
			</select>
			<? echo getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"]."&nbsp;"; ?>
		</td>
	</tr>

	<tr>
		<td class="normaltext" align="right">
			Return to supplier :&nbsp;
		</td>
		<td>
			<select name="list_supplier" id="list_supplier" class="select_400" <?php if (!is_null($_SESSION['stock_rts_id'])) echo "disabled"; ?>>
				<? 
					for ($i=0;$i<$qry->RowCount();$i++) {
						if ($qry->FieldByName('supplier_id') == $_SESSION['current_supplier_id'])
							echo "<option value=".$qry->FieldByName('supplier_id')." selected >".$qry->FieldByName('supplier_name')."</option>";
						else
							echo "<option value=".$qry->FieldByName('supplier_id').">".$qry->FieldByName('supplier_name')."</option>";
						$qry->Next();
					}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td class="normaltext" align="right">
			Note:&nbsp;
		</td>
		<td colspan='2'>
			<input type="text" value="<?php echo $_SESSION['current_note']; ?>" name="note" id="note" class="input_200">
		</td>
	</tr>
	</table>


<!-- 	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td class="normaltext" align="right">Inv No:</td>
			<td><input type="text" value="<?php echo $_SESSION['current_invoice_number'];?>" name="invoice_number" id="invoice_number" class="input_100"></td>
			<td class="normaltext" align="right">Inv Date:</td>
			<td><input type="text" value="<?php echo $_SESSION['current_invoice_date']; ?>" name="invoice_date" id="invoice_date" class="input_100"></td>
		</tr>
	</table>
 -->	
<?
    boundingBoxEnd("750", "../../images/blank.gif");
?>
</td></tr>
</table>

</form>

	<script src="../../include/js/jquery-3.2.1.min.js"></script>

    <script>

		$(document).ready(function(){

			$(" #list_day ").change(function() {

				$.ajax({
					method	: "POST",
					url		: "session_vars.php",
					data 	: { list_day: $(this).val() }
				})
				.done(function( msg ) {
					console.log( msg );
				});

			});


			$(" #list_supplier ").change(function() {

				$.ajax({
					method	: "POST",
					url		: "session_vars.php",
					data 	: { list_supplier: $(this).val(), clear_list: true }
				})
				.done(function( msg ) {

					console.log( msg );
					parent.frames["frame_list"].document.location = "receipt_list.php";

				});

			});
/*
			$(" #note ").focusout(function() {
				$.ajax({
					method	: "POST",
					url		: "session_vars.php",
					data 	: { name: "note", value: $(this).val() }
				})
				.done(function( msg ) {
					console.log( msg );
				});
			});

			$(" #invoice_number ").focusout(function() {
				$.ajax({
					method	: "POST",
					url		: "session_vars.php",
					data 	: { name: "invoice_number", value: $(this).val() }
				})
				.done(function( msg ) {
					console.log( msg );
				});
			});

			$(" #invoice_date ").focusout(function() {
				$.ajax({
					method	: "POST",
					url		: "session_vars.php",
					data 	: { name: "invoice_date", value: $(this).val() }
				})
				.done(function( msg ) {
					console.log( msg );
				});
			});
*/

		});

	</script>



<script language="JavaScript">
		oTextBoxBillNumber = document.receipt_supplier.bill_number;
		oTextBoxSupplier = document.receipt_supplier.list_supplier;
		oTextBoxDay = document.receipt_supplier.list_day;
		parent.frames["frame_enter"].document.location = "receipt_enter.php?action=set_supplier&supplier_id=" + oTextBoxSupplier.value+"&receipt_day="+oTextBoxDay.value+"&bill_number="+oTextBoxBillNumber.value;
</script>

</body>
</html>
