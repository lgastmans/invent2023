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
	</style>

</head>

<body>

<div class="container">

<form id="frm-example" action="stock_closing_balance.php" method="POST">


  	<div id="stock_grid">

		<table id="example" class="table table-striped table-condensed " cellspacing="0" width="100%">
	        <thead>
	            <tr>
	            	<th><input id="example-select-all" name="select_all" value="1" type="checkbox"></th>
	                <th>code</th>
	                <th>description</th>
 	                <th>closing balance</th>
	                <th>current stock</th>
	                <th>batches stock</th>
	                <th>adjusted stock</th>
	            </tr>
	        </thead>
	    </table>


  	</div> <!-- stock grid -->


	<p><button>Submit</button></p>

	<b>Data submitted to the server:</b><br>

	<pre id="example-console"></pre>

	</form>

</div> <!-- container -->


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../../include/js/jquery-3.2.1.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

	<script type="text/javascript" src="../../include/datatables/datatables.min.js"></script>

    <script>

    	/*
    		The checkbox code here:
    			https://jsfiddle.net/gyrocode/abhbs4x8/

    		and here:
    			https://jsfiddle.net/gyrocode/07Lrpqm7/
    	*/

		$(document).ready(function(){

			/*
				Billing Grid
				https://www.datatables.net/examples/basic_init/scroll_y_dynamic.html
			*/
			var rows_selected = [];
			var table = $('#example').DataTable({
		        scrollY 		: '100%',
		        //scrollCollapse	: true,
		        paging			: false,
		        searching		: false,
		        ajax			: "get_data.php",
		        'columnDefs'	: [
			        {
						'targets': 0,
						'searchable':false,
						'orderable':false,
						'width':'1%',
						'className': 'dt-body-center',
						'render': function (data, type, full, meta){
							return '<input type="checkbox" >'; // return '<input type="checkbox" name="id[]" value="' + $('<div/>').text(data).html() + '">';
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
				oLanguage		: {
					"sInfo": "", //"_TOTAL_ entries",
					"sInfoEmpty": "No entries",
					"sEmptyTable": "Zero discrepancies",
				}
		    });

			// Handle click on "Select all" control
			$('#example-select-all').on('click', function(){
			  // Check/uncheck all checkboxes in the table
			  var rows = table.rows({ 'search': 'applied' }).nodes();
			  $('input[type="checkbox"]', rows).prop('checked', this.checked);
			});

			// Handle click on checkbox to set state of "Select all" control
			$('#example tbody').on('change', 'input[type="checkbox"]', function(){
			  // If checkbox is not checked
			  if(!this.checked){
			     var el = $('#example-select-all').get(0);
			     // If "Select all" control is checked and has 'indeterminate' property
			     if(el && el.checked && ('indeterminate' in el)){
			        // Set visual state of "Select all" control 
			        // as 'indeterminate'
			        el.indeterminate = true;
			     }
			  }
			});

			$('#frm-example').on('submit', function(e){
			  var form = this;

			  // Iterate over all checkboxes in the table
			  table.$('input[type="checkbox"]').each(function(){
			     // If checkbox doesn't exist in DOM
			     if(!$.contains(document, this)){
			        // If checkbox is checked
			        if(this.checked){
			           // Create a hidden element 
			           $(form).append(
			              $('<input>')
			                 .attr('type', 'hidden')
			                 .attr('name', this.name)
			                 .val(this.value)
			           );
			        }
			     } 
			  });

			  // FOR TESTING ONLY
			  
			  // Output form data to a console
			  $('#example-console').text($(form).serialize()); 
			  console.log("Form submission", $(form).serialize()); 
			   
			  // Prevent actual form submission
			  e.preventDefault();
			});


		}); // end document ready

    </script>

</body>
</html>
