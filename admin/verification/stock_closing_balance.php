<?php

	include("../../include/const.inc.php");
	include("session.inc.php");
	include("include/db.inc.php");

?>

<!DOCTYPE html>
<html lang="en">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<head>

    <!-- Bootstrap -->
    <link href="../../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

	<link rel="stylesheet" type="text/css" href="../../include/datatables/datatables.min.css"/>

	<style type="text/css">
		body {
			margin:20px;
		}
		.glyphicon-refresh-animate {
		    -animation: spin .7s infinite linear;
		    -webkit-animation: spin2 .7s infinite linear;
		}

		@-webkit-keyframes spin2 {
		    from { -webkit-transform: rotate(0deg);}
		    to { -webkit-transform: rotate(360deg);}
		}

		@keyframes spin {
		    from { transform: scale(1) rotate(0deg);}
		    to { transform: scale(1) rotate(360deg);}
		}		
	</style>

</head>

<body>

<div class="container">


<form id="frm-example" action="update_stock.php" method="POST">


	<?php if ((isset($_GET['action'])) && ($_GET['action']=='batches')) { ?>
		<div id="msg-result" class="alert alert-success" role="alert">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			Batches have been updated successfully
		</div>
	<?php } elseif ((isset($_GET['action'])) && ($_GET['action']=='stock')) { ?>
		<div id="msg-result" class="alert alert-success" role="alert">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			Stock mismatches have been updated successfully
		</div>
	<?php } ?>


	<div id="jumbotron" class="jumbotron" style="display:none;">
		<p class="text-center">
			<button class="btn btn-lg btn-warning">
				<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span> Updating stock ...
			</button>
		</p>
	</div>


	<div id="message" class="alert alert-danger" role="alert">
	</div>


	<div class="row">
		<div class="col-md-3">
			<button type="button" id="btn-update" class="btn btn-primary btn-lg btn-block">Correct Inconsistencies</button>
		</div>
	</div>


  	<div id="stock_grid">

		<table id="grid-products" class="table table-striped table-condensed " cellspacing="0" width="100%">
	        <thead>
	            <tr>
	            	<th><input name="select_all" value="1" type="checkbox"></th>
	                <th>Code</th>
	                <th>Description</th>
 	                <th>Closing Balance</th>
	                <th>Current Stock</th>
	                <th>Batches Stock</th>
	                <th>Adjusted Stock</th>
	            </tr>
	        </thead>
	    </table>

	    <span id="table_details"></span>

  	</div> <!-- stock grid -->

  	<input type="hidden" id="action" name="action" value="">

<!-- 	<b>Data submitted to the server:</b><br>

	<pre id="example-console"></pre>
 -->
</form>

</div> <!-- container -->


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../../include/js/jquery-3.2.1.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

	<script type="text/javascript" src="../../include/datatables/datatables.min.js"></script>

	<script type="text/javascript" src="../../include/js/bootbox.min.js"></script>

    <script>



    	/*
    		The checkbox code here:
    			https://jsfiddle.net/gyrocode/abhbs4x8/
    	*/

		//
		// Updates "Select all" control in a data table
		//
		function updateDataTableSelectAllCtrl(table){
		   var $table             = table.table().node();
		   var $chkbox_all        = $('tbody input[type="checkbox"]', $table);
		   var $chkbox_checked    = $('tbody input[type="checkbox"]:checked', $table);
		   var chkbox_select_all  = $('thead input[name="select_all"]', $table).get(0);

		   // If none of the checkboxes are checked
		   if($chkbox_checked.length === 0){
		      chkbox_select_all.checked = false;
		      if('indeterminate' in chkbox_select_all){
		         chkbox_select_all.indeterminate = false;
		      }

		   // If all of the checkboxes are checked
		   } else if ($chkbox_checked.length === $chkbox_all.length){
		      chkbox_select_all.checked = true;
		      if('indeterminate' in chkbox_select_all){
		         chkbox_select_all.indeterminate = false;
		      }

		   // If some of the checkboxes are checked
		   } else {
		      chkbox_select_all.checked = true;
		      if('indeterminate' in chkbox_select_all){
		         chkbox_select_all.indeterminate = true;
		      }
		   }
		}



		$(document).ready(function(){

			setTimeout(function() {
				$(" #msg-result ").fadeTo(2000, 500).hide();
			}, 10000);

			/*
				Billing Grid
				https://www.datatables.net/examples/basic_init/scroll_y_dynamic.html
			*/
			var rows_selected = [];

			var billTable = $('#grid-products').DataTable({

		        scrollY 		: '100%',
		        paging			: false,
		        searching		: false,
		        sAjaxSource		: "get_data.php",
		        bProcessing		: true,
				bServerSide		: true,
		        fnServerData	: function ( sSource, aoData, fnCallback ) {
					$.getJSON( sSource, aoData, function (json) { 
						
						//console.log(json);
						//console.log('discrepancies'+json.discrepancies);

						if (json.discrepancies > 0) {
							$(" #message ").html(json.message);
							$('#btn-update').prop('disabled',false);
						}
						else {
							$(" #message ").removeClass( "alert-danger" ).addClass( "alert-success" );
							$(" #message ").html('No stock mismatches found.');

							$('#btn-update').prop('disabled',true);
						}

						$(" #action ").val(json.action);

						if (json.action == 'batches') {
							billTable.column(0).visible(false);
							billTable.column(3).visible(false);
							billTable.column(4).visible(false);
							billTable.column(6).visible(false);
						}

						/* Do whatever additional processing you want on the callback, then tell DataTables */
						fnCallback(json);
					});
				},
				/*
				fnDrawCallback	: function() {
					this.fnSetColumnVis( 4, false );
				},
				*/
		        'columnDefs'	: [
			        {
						'targets': 0,
						'searchable':false,
						'orderable':false,
						'width':'1%',
						'className': 'dt-body-center',
						'render': function (data, type, full, meta){
							return '<input type="checkbox">';
					 	}
					},
			        {
						'targets': 5,
						'searchable':false,
						'orderable':false,
						//'width':'1%',
						'className': 'dt-body-center',
						'render': function (data, type, full, meta){
							return '<input type="text" value="'+data+'">';
					 	}
					}
				],
				'rowCallback'	: function(row, data, dataIndex){
					// Get row ID
					var rowId = data[0];

					// If row ID is in the list of selected row IDs
					if($.inArray(rowId, rows_selected) !== -1){
						$(row).find('input[type="checkbox"]').prop('checked', true);
						$(row).addClass('selected');
					}
				},
				/*
		        columns: [
	                { data: "product_id", visible: false },
	                { data: "product_code"},
	                { data: "product_description"},
	                { data: "closing_balance" },
	                { data: "stock_current" },
	                { data: "total_batch_stock" },
	                { data: "stock_adjusted" }
	            ],
	            */
				oLanguage		: {
					"sInfo": "", //"_TOTAL_ entries",
					"sInfoEmpty": "No entries",
					"sEmptyTable": "Zero discrepancies",
				}
		    });

			// Handle click on checkbox
			$('#grid-products tbody').on('click', 'input[type="checkbox"]', function(e){
				var $row = $(this).closest('tr');

				// Get row data
				var data = billTable.row($row).data();

				// Get row ID
				var rowId = data[0];
				var rowStock = data[5];

				// Determine whether row ID is in the list of selected row IDs 
				//var index = $.inArray(rowId, rows_selected);
				var index=-1;
				for( var i = 0, len = rows_selected.length; i < len; i++ ) {
				    if( rows_selected[i][0] === rowId) {
				        index = i; //rows_selected[i];
				        break;
				    }
				}

				// If checkbox is checked and row ID is not in list of selected row IDs
				if(this.checked && index === -1){
					//rows_selected.push(rowId);  
					rows_selected.push([rowId,rowStock]);

				// Otherwise, if checkbox is not checked and row ID is in list of selected row IDs
				} else if (!this.checked && index !== -1){
					rows_selected.splice(index, 1);
				}

				if(this.checked){
					$row.addClass('selected');
				} else {
					$row.removeClass('selected');
				}

				// Update state of "Select all" control
				updateDataTableSelectAllCtrl(billTable);

				// Prevent click event from propagating to parent
				e.stopPropagation();
			});

			// Handle click on table cells with checkboxes
			//$('#grid-products').on('click', 'tbody td, thead th:first-child', function(e){
			//	$(this).parent().find('input[type="checkbox"]').trigger('click');
			//});


			// Handle click on "Select all" control
			$('thead input[name="select_all"]', billTable.table().container()).on('click', function(e){
				if(this.checked){
					$('#grid-products tbody input[type="checkbox"]:not(:checked)').trigger('click');
				} else {
					$('#grid-products tbody input[type="checkbox"]:checked').trigger('click');
				}

				// Prevent click event from propagating to parent
				e.stopPropagation();
			});


			// Handle table draw event
			billTable.on('draw', function(){
			  // Update state of "Select all" control
			  updateDataTableSelectAllCtrl(billTable);
			});


			// Handle form submission event 
			$('#frm-example').on('submit', function(e){

				// Prevent actual form submission
				e.preventDefault();

				$(" #jumbotron ").show();

				$.ajax({
					method 	: "POST",
					url 	: "update_stock.php",
					data 	: { "action" : $(" #action ").val(), "ids" : rows_selected }
				})
				.done ( function( msg ) {

					//setTimeout('$(" #jumbotron ").hide();', 3000);
					setTimeout(function() {
							$(" #jumbotron ").hide();
							billTable.ajax.reload();
						}, 3000
					);
					
					//billTable.ajax.reload();

					console.log(msg);

				});

			});


			$(" #btn-update ").on("click", function(e) {
				bootbox.confirm("Are you sure ?", function(result) {
					if (result)
						$( "#frm-example" ).submit();
				});
			});


		}); // end document ready

    </script>

</body>
</html>
