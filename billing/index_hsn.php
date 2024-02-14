<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("bill_funcs.inc.php");
	
	$_SESSION["int_bills_menu_selected"] = 2;
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

		.container {
			width:auto;
		}
	</style>

</head>

<body>

	<div class="container">

		<button type="button" class="btn btn-primary" id="btn-load">Load</button>
		
		<div id="info-board" class="jumbotron" style="display:none;">
			<p class="text-center">
				<button class="btn btn-lg btn-warning">
					<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span> Processing...
				</button>
			</p>
		</div>


	  	<div id="dt_grid">

			<table id="grid-hsn" class="table table-striped table-condensed " cellspacing="0" width="100%">
		        <thead><tr></tr></thead>
		        <!-- <tfoot><tr></tr></tfoot> -->
		    </table>

	  	</div> <!-- stock grid -->


	</div> <!-- container -->


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

	<script type="text/javascript" src="../include/datatables/datatables.min.js"></script>

	<script type="text/javascript" src="../include/js/bootbox.min.js"></script>
	<script type="text/javascript" src="../include/js/accounting.js"></script>

	<script>

		$(document).ready(function(){

        	var data,
                tableName = '#grid-hsn',
                columns,
                str;

            $("#btn-load").on("click", function() {
            	
            	$(" #info-board ").show();

				$.ajax({
					method 	: "POST",
					url 	: "get_data_hsn.php",
					data 	: { "action" : "columns" }
				})
				.done ( function( msg ) {

					setTimeout('$(" #info-board ").hide();', 2000);

					data = JSON.parse(msg);

					
	                // Iterate each column and print table headers for Datatables
	                $.each(data.columns, function (k, colObj) {
	                    str = '<th>' + colObj.name + '</th>';
	                    $(str).appendTo(tableName+'>thead>tr');
	                    $(str).appendTo(tableName+'>tfoot>tr');
	                });

					$(tableName).dataTable({
	                    "data"			: data.data,
	                    "columns"		: data.columns,
	                    "footer"		: data.footer,
						//"scrollY" 		: '80vh',
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

						"fnRowCallback": function (nRow, aData, iDisplayIndex) {
							if (aData.hsn=='TOTALS')
						    	$(nRow).css("font-weight", "bold");
						},

						"oLanguage"	: {
							"sInfo": "", //"_TOTAL_ entries",
							"sInfoEmpty": "No entries",
							"sEmptyTable": "Zero discrepancies",
						}
	                });
	                

				});
			})
		});

	</script>



</body>
</html>
