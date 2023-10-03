<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
?>

<script language="javascript">

	function mouseGoesOver(element, aSource)
	{
		element.src = aSource;
	}
	
	function mouseGoesOut(element, aSource)
	{
		element.src = aSource;
	}

	function printStatement() {
		var oListSupplier = document.StatementTotalsMenu.select_supplier;
		var str_dest = "statement_totals_print.php?supplier_type=" +oListSupplier.options[oListSupplier.options.selectedIndex].value;
		window.open(str_dest, "print_window");
	}


</script>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />

        <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

    </head>

<body id='body_bgcolor' style="padding-top:7px;">


	<div class="container">


		Supplier : 

		<div class="btn-group" id="supplier" data-toggle="buttons">
			
			<label class="btn btn-primary btn-xs active">
				<input type="radio" name="direct" value="N" id="btn-direct" autocomplete="off" checked> Direct
			</label>

			<label class="btn btn-primary btn-xs">
				<input type="radio" name="consignment" value="Y" id="btn-consignment" autocomplete="off"> Consignment
			</label>

		</div>


		&nbsp;
		<a href="#" id="btn-print"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>

	</div>



    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

    <script>

		$(document).ready(function(){

			$('#btn-direct').on('change', function () {
				parent.frames["content"].document.location = "statement_totals.php?supplier_type=N";
			});

			$('#btn-consignment').on('change', function () {
				parent.frames["content"].document.location = "statement_totals.php?supplier_type=Y";
			});

			$('#btn-print').on('click', function () {
				var sel =  $('#supplier label.active input ').val();
				window.open("statement_totals_print.php?supplier_type="+sel, "print_window");
			});
		});

	</script>

</body>
</html>