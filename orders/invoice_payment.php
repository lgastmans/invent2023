<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");


	$invoice_id=0;
	if (IsSet($_GET['id'])) 
		$invoice_id = $_GET['id']; 


	/*
		invoice details
	*/
	$qry = $conn->query("
		SELECT *
		FROM ".Monthalize('bill')."
		WHERE bill_id = $invoice_id
			AND storeroom_id = ".$_SESSION['int_current_storeroom']
	);

	if (!$qry) die('error retrieving invoice details');

	$invoice = $qry->fetch_object();


	/*
		customer details
	*/
	$sql = "
		SELECT *
		FROM `customer` c
		WHERE c.id = ".$invoice->CC_id;
	$qry = $conn->query($sql);

	if (!$qry) die('error retrieving customer details');

	$customer = $qry->fetch_object();


?>

<html>

<head>
	<link rel="stylesheet" type="text/css" href="../include/datatables2/datatables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../include/datatables2/DataTables-1.10.18/css/dataTables.bootstrap4.min.css"/>

	<link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

	<style>
		body {
			margin:15px;
		}
	</style>
</head>

<body>

	<div class="container">

		<div class="panel panel-default">
			<div class="panel-heading"><b>Invoice # <?php echo $invoice->bill_number;?></b></div>
			<div class="panel-body">
				<h5><?php echo $customer->company; ?></h5>
				<h5><?php echo $customer->address." ".$obj->address2." ".$obj->city." ".$obj->zip; ?></h5>
				<h5><?php echo $customer->contact_person; ?></h5>
			</div>
		</div>

		<div class="row">
			<table id="invoices" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
				<thead>
					<tr>
						<th>id</th>
						<th>Amount</th>
						<th>Reference</th>
						<th>Type</th>
						<th>Date</th>
						<th></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>

		<hr />

		<button type="button" class="btn btn-primary btn-lg" id="btn-add">
			Add Payment
		</button>

	</div>


	<!-- Modal -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">

	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title" id="myModalLabel">Payment Details</h4>
	      </div>

	      <div class="modal-body">


	      	<form class="form-horizontal">
				<div class="form-group">
					<label for="amount" class="col-sm-2 control-label">Amount</label>
					<div class="col-sm-10">
						<input type="number" class="form-control" id="amount" placeholder="Amount">
					</div>
				</div>
				<div class="form-group">
					<label for="reference" class="col-sm-2 control-label">Reference</label>
					<div class="col-sm-10">
						<input type="text" class="form-control" id="reference" placeholder="Reference">
					</div>
				</div>
				<div class="form-group">
					<label for="reference" class="col-sm-2 control-label">Type</label>
					<div class="dropdown col-sm-10" id="btn-payment_type">
						<button class="btn btn-default dropdown-toggle" type="button"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
							<span class="span-payment_type">Select</span>
							<span class="caret"></span>
						</button><!-- 'Cash','Bank Transfer','Cheque','Financial Service','Wire Transfer','Other' -->
						<ul class="dropdown-menu" aria-labelledby="payment_type" id="payment_type">
							<li><a href="#" id="Cash">Cash</a></li>
							<li><a href="#" id="Bank Transfer">Bank Transfer</a></li>
							<li><a href="#" id="Cheque">Cheque</a></li>
							<li><a href="#" id="Financial Service">Financial Service</a></li>
							<li><a href="#" id="Wire Transfer">Wire Transfer</a></li>
							<li><a href="#" id="Other">Other</a></li>
						</ul>
					</div>
				</div>
				<div class="form-group">
					<label for="payment-date" class="col-sm-2 control-label">Date</label>
					<div class="col-sm-10">
						<input type="date" class="form-control" id="payment-date" placeholder="Date">
					</div>
				</div>
	
			</form>

	      </div>

	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	        <button type="button" id="btn-save" class="btn btn-primary" data-dismiss="modal">Save changes</button>
	      </div>

	    </div>
	  </div>
	</div>


<script src="../include/js/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="../include/datatables2/datatables.min.js"></script>
<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<script src="../include/js/bootbox.min.js"></script>



<script>

	var payment_type = 'Other';
	var payment_id = 0;

    $( document ).ready(function() {

		var table = $('#invoices').DataTable({
	        scrollY 		: '40vh',
	        scrollCollapse	: true,
	        paging			: false,
	        searching		: false,
	        fixedColumns	: true,
	        ajax			: { 
	        	"url"	: "data/invoice_details.php",
	        	"type"	: "POST",
	        	data 	: function (d) {
	        		d.invoice_id = <?php echo $invoice_id; ?>;
	        	}
	        },
	        columns: [
                { data: "id", visible: false },
	            { data: "amount" },
                { data: "payment_reference" },
                { data: "payment_type" },
                { data: "payment_date" },
				{ 
					targets: -1, 
					data: null, 
					defaultContent: "<input type='button' id='btn-edit' class='btn btn-success btn-xs' value='edit' />",
				},
				{ 
					targets: -1, 
					data: null, 
					defaultContent: "<input type='button' id='btn-delete' class='btn btn-danger btn-xs' value='delete' />",
				}

            ],
			oLanguage		: {
				"sInfo": "", //"_TOTAL_ entries",
				"sInfoEmpty": "No entries",
				"sEmptyTable": "No payments received",
			}
	    });


		/*
			add a payment
		*/
		$('#btn-add').on('click', function () {

            payment_id = null;
            
            $('#amount').val('');
            $('#reference').val('');
            $('#payment_type li a').parents("#btn-payment_type").find('.span-payment_type').text('Select');
            $('#payment-date').val('');

			$('#myModal').modal('show');

		});


		/*
			edit a payment
		*/
		$('#invoices tbody').on('click', '[id*=btn-edit]', function () {

            var data = table.row($(this).parents('tr')).data();

            payment_id = data.id;
            
            $('#amount').val(data.amount);
            $('#reference').val(data.payment_reference);
            $('#payment_type li a').parents("#btn-payment_type").find('.span-payment_type').text(data.payment_type);
            $('#payment-date').val(data.payment_date);

			$('#myModal').modal('show');

        });


        /*
        	delete a payment
        */
		$('#invoices tbody').on('click', '[id*=btn-delete]', function () {

            var data = table.row($(this).parents('tr')).data();

            payment_id = data.id;
            
			bootbox.confirm("Are you sure ?", function(result) { 

				if (result) {

					$.ajax({
						method 	: "POST",
						url 	: "data/invoice_delete.php",
						data 	: { payment_id : payment_id }
					})
					.done ( function( msg ) {
						table.ajax.reload();
					});

				}
				
			});

        });




		$("#payment_type li a").click(function(){

			payment_type = $(this).attr('id');

			$(this).parents("#btn-payment_type").find('.span-payment_type').text($(this).text());

		});


		/*
			 for one invoice there are multipe payments
			 to add an invoice
			 	the invoice_id is passed and the payment_id is auto-gen
			 to edit / delete an invoice
			 	the invoice_id is passed and the payment_is is passed
		*/

		$('#btn-save').click(function() {

			$.ajax({
				method: "POST",
				url: "data/invoice_save.php",
				data: {
					id: <?php echo $invoice_id;?>, 
					payment_id: payment_id,
					amount: $("#amount").val(),
					reference: $("#reference").val(),
					payment_type: payment_type,
					payment_date: $('#payment-date').val()
				}
			}).done(function (msg) {
				table.ajax.reload();
				$('#myModal').modal('hide');
			});

		});

    });

</script>


</body>
</html>			