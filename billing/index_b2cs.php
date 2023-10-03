<html>
<head>
	<style>

		table#b2cs {
		    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
		    border-collapse: collapse;
		    width: 100%;
		}

		table#b2cs caption {
			margin-top:20px;
			margin-bottom:20px;
			font-size:1em;
			font-weight: bold;
		}

		table#b2cs td, table#b2cs th {
		    border: 1px solid #ddd;
		    padding: 8px;
		}

		table#b2cs tr:nth-child(even){background-color: #f2f2f2;}

		table#b2cs tr:hover {background-color: #ddd;}

		table#b2cs th {
		    padding-top: 12px;
		    padding-bottom: 12px;
		    text-align: left;
		    background-color: grey;
		    color: white;
		}

		h1 {
		    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
			font-size:1em;
			font-weight: bold;
		}

		.footnote {
			padding-top:50px;
			font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
			font-size:.75em;
		}

		ol.a {
		    list-style-type: lower-alpha;
		}
		ol.b {
		    list-style-type: decimal;
		}

		div.loader {
		    max-width: 500px;
		    margin: auto;
		}
		

	</style>
</head>

<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("bill_funcs.inc.php");
	
	$_SESSION["int_bills_menu_selected"] = 3;

	/*
	b2cs	B2C Small	"Supplies made to consumers and unregistered persons of the following nature
	a) Intra-State: any value
	b) Inter-State: Invoice value Rs 2.5 lakh or less"	

			1. Type	In the Type column, enter E if the supply is done through E-Commerce or else enter OE (other than E-commerce).

			2. Place of Supply(POS)	Select the code of the state from drop down list for the applicable place of supply.

			3. Rate	Enter the combined  (State tax + Central tax) or the integrated tax rate. 

			4. Taxable Value	Enter the taxable value of the supplied  goods or services for each rate line item -2 decimal Digits, The taxable value has to be computed as per GST valuation provisions. 

			5. Cess Amount	Enter the total  Cess amount collected/payable. 

			6. E-Commerce GSTIN	Enter the GSTIN of the e-commerce company if the supplies are made through an e-Commerce operator.

	*/

?>


<body>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

	<script>
		$(document).ready(function(){

			function formatCurrency(total) {
			    var neg = false;
			    if(total < 0) {
			        neg = true;
			        total = Math.abs(total);
			    }
			    return (neg ? "-Rs " : "Rs ") + parseFloat(total, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
			}

			$.ajax({
			  method: "POST",
			  url: "get_data_b2cs.php",
			  contentType: "text/xml",
			  //data: { name: "John", location: "Boston" }
			})
			.done(function( msg ) {
				var obj = jQuery.parseJSON( msg );
				var row = '';

				$(".loader").css("display", "none");

				$.each( obj, function( key, value ) {
				  //alert(value.cess);

				  row = 
				  	"<tr><td>"+value.type+"</td>"+
				  	"<td>"+value.pos+"</td>"+
				  	"<td>"+value.rate+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.value)+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.cess)+"</td>"+
				  	"<td>"+value.gstin+"</td></tr>";

				  $("#b2cs > tbody").last().append(row);
				});
			})
			.fail(function() {
				alert( "Error fectching data." );
			})
			.always(function() {
				//
			});


			$("#export").click(function(){
				var row = '';

				$.ajax({
				  method: "POST",
				  url: "get_data_b2cs.php",
				  contentType: "text/xml",
				  
				})
				.done(function( msg ) {
					var obj = jQuery.parseJSON( msg );
					var data = '';

					$.each( obj, function( key, value ) {
					  data += 
					  	"\""+value.type+"\"," +
					  	"\""+value.pos+"\"," +
					  	"\""+value.rate+"\"," +
					  	"\""+value.value+"\","+
					  	"\""+value.cess+"\","+
					  	"\""+ value.gstin+"\","+" \r\n ";
					});

					var filename = 'b2cs.csv';
					var blobby = new Blob([data], {type: 'application/vnd.ms-excel;charset=charset=utf-8'});

					$(exportLink).attr({
			            'download' : filename,
			            'href': window.URL.createObjectURL(blobby),
			            'target': '_blank'
		            });

					exportLink.click();

					//window.open('export_b2cs.php?data='+encodeURIComponent(row));
				})
			}); 

		});
	</script>

	<button id="export" type="button">Export to CSV</button> 
	<a id="exportLink"></a>

	<table id='b2cs' style="width:100%">

		<caption>B2C Small - Supplies made to consumers and unregistered persons</caption>
		<tr>
			<th>Type</th>
			<th>Place of Supply</th>
			<th>Rate</th>
			<th>Taxable Value</th>
			<th>Cess Amount</th>
			<th>E-Commerce GSTIN</th>
		</tr>

		<tbody>
		</tbody>

	</table> 

	<div class="loader"><img src="../images/loading.gif"></div>

	<div class="footnote">
		<h1>B2C Small</h1>
		</br>
		Supplies made to consumers and unregistered persons of the following nature:
		<ol class="a">
			<li>Intra-State: any value</li>
			<li>Inter-State: Invoice value Rs 2.5 lakh or less</li>
		</ol>
		
		<ol class="b">
			<li><b>Type:</b> In the Type column, enter E if the supply is done through E-Commerce or else enter OE (other than E-commerce).</li>
			<li><b>Place of Supply(POS):</b> Select the code of the state from drop down list for the applicable place of supply.</li>
			<li><b>Rate:</b> Enter the combined  (State tax + Central tax) or the integrated tax rate. </li>
			<li><b>Taxable Value:</b> Enter the taxable value of the supplied  goods or services for each rate line item -2 decimal Digits, The taxable value has to 	be computed as per GST valuation provisions. </li>
			<li><b>Cess Amount:</b> Enter the total  Cess amount collected/payable. </li>
			<li><b>E-Commerce GSTIN:</b> Enter the GSTIN of the e-commerce company if the supplies are made through an e-Commerce operator.	</li>
		</ol>
	</div>

</body>
</html>