<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$_SESSION["int_bills_menu_selected"] = 3;
?>

<!DOCTYPE html>
<html lang="en">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<head>

    <!-- Bootstrap -->
    <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

	<link rel="stylesheet" type="text/css" href="../include/datatables/datatables.min.css"/>

    <link href="../include/js/jquery-typeahead-2.10.1/dist/jquery.typeahead.min.css" rel="stylesheet">

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

		<div class="row">

			<div class="col-md-12">
				<div class="typeahead__container">
					<label>Client</label>
					<div class="typeahead__field">
						<span class="typeahead__query">
							<input class="typeahead-product" id="product_code" name="product[query]" type="search" placeholder="Search" autocomplete="off">
						</span>
					</div>
				</div>
			</div>

		</div>


	  	<div id="dt_grid">
			<table id="grid-hsn" class="table table-striped table-condensed " cellspacing="0" width="100%">
		        <thead><tr></tr></thead>
		        <tfoot><tr></tr></tfoot>
		    </table>

	  	</div> <!-- stock grid -->


	</div> <!-- container -->



    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

    <script src="../include/js/jquery-typeahead-2.10.1/dist/jquery.typeahead.min.js"></script>

	<script type="text/javascript" src="../include/datatables/datatables.min.js"></script>

	<script type="text/javascript" src="../include/js/bootbox.min.js"></script>
	<script type="text/javascript" src="../include/js/accounting.js"></script>

	<script>

		$(document).ready(function(){

			/*
				Billing Enter
				Product Code Typeahead 
			*/
			$.typeahead({
			    input 		: ".typeahead-product",
			    order		: "asc",
			    display 	: ["company", "city"],
			    templateValue: "{{code}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    maxItem		: 12,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/get_clients.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			 			
			 			//$( ".typeahead-product" ).focusout();

			            getClient( item.id );
			        }
				}

			});


			getClient = function(id='') {

				var strPassValue = '';

				if (id.value != '') {

					strPassValue = id;

					$.ajax({
						method 	: "GET",
						url		: "data/client_details.php",
						data 	: { id: id }
					})
					.done(function( msg ) {

						$("#result-container-description").text("Given product not found.");


					});

				}

			}






        	var data,
                tableName = '#grid-hsn',
                columns,
                str;

				$.ajax({
					method 	: "POST",
					url 	: "get_data_hsn.php",
					data 	: { "action" : "columns" }
				})
				.done ( function( msg ) {

					$(" #info-board ").hide();

					data = JSON.parse(msg);

	                // Iterate each column and print table headers for Datatables
	                $.each(data.columns, function (k, colObj) {
	                    str = '<th>' + colObj.name + '</th>';
	                    $(str).appendTo(tableName+'>thead>tr');
	                    $(str).appendTo(tableName+'>tfoot>tr');
	                });

	                // Add some Render transformations to Columns
	                // Not a good practice to add any of this in API/ Json side
	                /*
	                data.columns[3].render = function (data, type, row) {
	                	return '<h4>' + data + '</h4>';
	                    //return accounting.formatMoney(data, "", 2, ",", ".");
	                }
	                */
	                

					$(tableName).dataTable({
	                    "data"			: data.data,
	                    "columns"		: data.columns,
	                    "footer"		: data.footer,
						"scrollY" 		: '80vh',
				        "scrollCollapse": true,
				        "paging"		: false,				        
				        "searching"	: false,
				        
						"columnDefs": [
						  { className: "dt-right", "targets": '_all' }
						],
						
						"fnInitComplete": function () {
	                        // Event handler to be fired when rendering is complete (Turn off Loading gif for example)
	                        console.log('Datatable rendering complete');
	                    },

						"oLanguage"	: {
							"sInfo": "", //"_TOTAL_ entries",
							"sInfoEmpty": "No entries",
							"sEmptyTable": "Zero discrepancies",
						}
	                });

				});

		});

	</script>



</body>
</html>
