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
		"Details of invoices of Taxable supplies made to other registered taxpayers"	

		1. GSTIN/UIN of Recipient	Enter the GSTIN or UIN of the receiver. E.g. 05AEJPP8087R1ZF. Check that the registration is active on the date of the invoice from GST portal
		2. Invoice  number 	Enter the Invoice number of invoices issued to  registered recipients. Ensure that the format is alpha-numeric with  allowed special characters of slash(/) and dash(-) .The total number of characters should not be more than 16.
		3. Invoice Date 	Enter date of invoice in DD-MMM-YYYY. E.g. 24-May-2017.
		4. Invoice value	Enter the total value indicated in the invoice  of the supplied  goods or services- with 2 decimal Digits.
		5. Place of Supply(POS)	Select the code of the state from drop down list for the place of supply.
		6. Reverse Charge	Please select Y or N , if the supplies/services are subject to tax as per reverse charge mechanism.
		7. Invoice Type	Select from the dropdown whether the supply is regular, or to a SEZ unit/developer with or without payment of tax or deemed export.
		8. E-Commerce GSTIN	Enter the GSTIN of the e-commerce company if the supplies are made through an e-Commerce operator.
		9. Rate	Enter the combined  (State tax + Central tax) or the integrated tax, as applicable.
		10. Taxable Value	Enter the taxable value of the supplied  goods or services for each rate line item - with 2 decimal Digits, The taxable value has to be computed as per GST valuation provisions. 
		11. Cess Amount	Enter the total Cess amount collected/payable.
	*/

?>


<body>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

	<script>
		$(document).ready(function(){

			function formatCurrency(total) {

				return total;

			    var neg = false;
			    if(total < 0) {
			        neg = true;
			        total = Math.abs(total);
			    }
			    return (neg ? "-Rs " : "Rs ") + parseFloat(total, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
			}

			$.ajax({
			  method: "POST",
			  url: "get_data_b2b.php",
			  contentType: "text/xml",
			  //data: { name: "John", location: "Boston" }
			})
			.done(function( msg ) {
				var obj = jQuery.parseJSON(msg);
				var row = '';
				var total_cess = 0;

				$(".loader").css("display", "none");

				if (!!obj['ERROR']) {
					$("#b2cs > tbody").last().append(obj['ERROR']);
				}
				else {

					$.each( obj, function( key, value ) {

					  row = 
					  	"<tr><td>"+value.gstin+"</td>"+
					  	"<td>"+value.invoice_number+"</td>"+
					  	"<td>"+value.invoice_date+"</td>"+
					  	"<td align='right'>"+formatCurrency(value.invoice_value)+"</td>"+
					  	"<td>"+value.place_of_supply+"</td>"+
					  	"<td>"+value.reverse_charge+"</td>"+
					  	"<td>"+value.invoice_type+"</td>"+
					  	"<td>"+value.ecom_gstin+"</td>"+
					  	"<td align='right'>"+value.rate+"</td>"+
					  	"<td align='right'>"+formatCurrency(value.taxable_value)+"</td>"+
						"<td align='right'>"+formatCurrency(value.cess)+"</td>"+
					  	"</tr>";

					  $("#b2cs > tbody").last().append(row);

					  total_cess = total_cess + value.cess;

					});
				}

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
				  url: "get_data_b2b.php",
				  contentType: "text/xml",
				  
				})
				.done(function( msg ) {
					var obj = jQuery.parseJSON( msg );
					var data = '';
					var total_cess = 0;

					$.each( obj, function( key, value ) {
					  data += 
					  	"\""+value.gstin+"\"," +
					  	"\""+value.invoice_number+"\"," +
					  	"\""+value.invoice_date+"\"," +
					  	"\""+value.invoice_value+"\","+
					  	"\""+value.place_of_supply+"\","+
					  	"\""+value.reverse_charge+"\","+
					  	"\""+value.invoice_type+"\","+
					  	"\""+value.ecom_gstin+"\","+
						"\""+value.rate+"\","+
						"\""+value.taxable_value+"\","+
					  	"\""+ value.cess+"\","+" \r\n ";

					  	total_cess = total_cess + value.cess;
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

		<caption>B2B Supplies - Details of invoices of Taxable supplies made to other registered taxpayers</caption>
		<tr>
			<th>GSTIN/UIN of Recipient</th>
			<th>Invoice Number</th>
			<th>Invoice Date</th>
			<th>Invoice Value</th>
			<th>Place of Supply</th>
			<th>Reverse Charge</th>
			<th>Invoice Type</th>
			<th>E-Commerce GSTIN</th>
			<th>Rate</th>
			<th>Taxable Value</th>
			<th>Cess Amount</th>
		</tr>

		<tbody>
		</tbody>

	</table> 

	<div class="loader"><img src="../images/loading.gif"></div>

	<div class="footnote">
		<h1>B2B Supplies</h1>
		</br>
		<ol class="b">
			<li><b>GSTIN/UIN of Recipient</b>	Enter the GSTIN or UIN of the receiver. E.g. 05AEJPP8087R1ZF. Check that the registration is active on the date of the invoice from GST portal</li>
			<li><b>Invoice  number</b> 	Enter the Invoice number of invoices issued to  registered recipients. Ensure that the format is alpha-numeric with  allowed special characters of slash(/) and dash(-) .The total number of characters should not be more than 16.</li>
			<li><b>Invoice Date</b> 	Enter date of invoice in DD-MMM-YYYY. E.g. 24-May-2017.</li>
			<li><b>Invoice value</b>	Enter the total value indicated in the invoice  of the supplied  goods or services- with 2 decimal Digits.</li>
			<li><b>Place of Supply(POS)</b>	Select the code of the state from drop down list for the place of supply.</li>
			<li><b>Reverse Charge</b>	Please select Y or N , if the supplies/services are subject to tax as per reverse charge mechanism.</li>
			<li><b>Invoice Type</b>	Select from the dropdown whether the supply is regular, or to a SEZ unit/developer with or without payment of tax or deemed export.</li>
			<li><b>E-Commerce GSTIN</b>	Enter the GSTIN of the e-commerce company if the supplies are made through an e-Commerce operator.</li>
			<li><b>Rate</b>	Enter the combined  (State tax + Central tax) or the integrated tax, as applicable.</li>
			<li><b>Taxable Value</b>	Enter the taxable value of the supplied  goods or services for each rate line item - with 2 decimal Digits, The taxable value has to be computed as per GST valuation provisions. </li>
			<li><b>Cess Amount</b>	Enter the total Cess amount collected/payable.</li>
		</ol>
	</div>

</body>
</html>