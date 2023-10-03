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

		table#b2cs td, table#b2cs1 th {
		    border: 1px solid #ddd;
		    padding: 8px;
		}

		table#b2cs tr:nth-child(even){background-color: #f2f2f2;}

		table#b2cs tr:hover {background-color: #ddd;}

		table#b2cs th {
		    padding: 8px;
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
	
	$_SESSION["int_bills_menu_selected"] = 4;

	/*

	HSN wise summary of goods /services supplied during the tax period	

		1. HSN	"Enter the HSN Code  for the supplied goods or Services. Minimum digit required to be mentioned in the tax invoice and consequently to be reported is as follows.

			1. Up to rupees one crore fifty lakhs annual turnover - Nil digit 
			2. more than rupees one crore fifty lakhs and up to rupees five crores annual turnover - 2 digit  
			3. more than rupees five crores annual turnover - 4  digit.
		"

		2. Description	Enter the description of the supplied goods or Services. Description becomes a mandatory field if HSN code is not provided above.
		3. UQC	Select the applicable Unit Quantity Code  from the drop down.
		4. Total Quantity	Enter the total quantity of the supplied goods or Services- up to 2 decimal Digits.
		5. Total Value	Enter the invoice  value of the goods or services-up to 2 decimal Digits.
		6. Taxable Value	Enter the total taxable value of the supplied goods or services- up to 2 decimal Digits.
		7. Integrated Tax Amount	Enter the total  Integrated tax amount collected/payable.
		8. Central Tax Amount	Enter the total  Central tax amount collected/payable.
		9. State/UT Tax Amount	Enter the total State/UT tax amount collected/payable.
		10. Cess Amount	Enter the total  Cess amount collected/payable.

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
			  url: "get_data_hsn.php",
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
				  	"<tr><td>"+value.hsn+"</td>"+
				  	"<td>"+value.description+"</td>"+
				  	"<td>"+value.unit+"</td>"+
				  	"<td>"+value.qty+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.total_value)+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.taxable_value)+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.igst)+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.cgst)+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.sgst)+"</td>"+
				  	"<td align='right'>"+formatCurrency(value.cess)+"</td></tr>";

				  $("#b2cs > tbody").last().append(row);
				});
			})
			.fail(function() {
				alert( "error" );
			})
			.always(function() {
				//
			});

			$("#export").click(function(){
				var row = '';

				$.ajax({
				  method: "POST",
				  url: "get_data_hsn.php",
				  contentType: "text/xml",
				})
				.done(function( msg ) {
					var obj = jQuery.parseJSON( msg );
					var data = '';

					$.each( obj, function( key, value ) {
					  data += 
					  	"\""+value.hsn+"\"," +
					  	"\""+value.description+"\"," +
					  	"\""+value.unit+"\"," +
					  	"\""+value.qty+"\"," +
					  	"\""+value.total_value+"\","+
					  	"\""+value.taxable_value+"\","+
					  	"\""+value.igst+"\","+
					  	"\""+value.cgst+"\","+
					  	"\""+value.sgst+"\","+
					  	"\""+value.cess+"\","+" \r\n ";
					});

					var filename = 'hsn.csv';
					var blobby = new Blob([data], {type: 'application/vnd.ms-excel;charset=charset=utf-8'});

					$(exportLink).attr({
			            'download' : filename,
			            'href': window.URL.createObjectURL(blobby),
			            'target': '_blank'
		            });

					exportLink.click();

//					window.open('export_b2cs.php?data='+encodeURIComponent(row));
				})
			}); 
		});
	</script>

	<button id="export" type="button">Export to CSV</button> 
	<a id="exportLink"></a>

	<table id='b2cs' style="width:100%">

		<caption>HSN wise summary of goods /services supplied during the tax period</caption>

		<tr>
			<th>HSN</th>
			<th>Description</th>
			<th>UoM</th>
			<th>Qty</th>
			<th>Total Value</th>
			<th>Taxable Value</th>
			<th>IGST</th>
			<th>SGST</th>
			<th>CGST</th>
			<th>Cess</th>
		</tr>

		<tbody>
		</tbody>

	</table> 

	<div class="loader"><img src="../images/loading.gif"></div>


<div class="footnote">
	<h1>HSN wise summary of goods /services supplied during the tax period</h1>

		<ol class="b">
			<li><b>HSN</b>	Enter the HSN Code  for the supplied goods or Services. Minimum digit required to be mentioned in the tax invoice and consequently to be reported is as follows.
				<ol class="a">
					<li>Up to rupees one crore fifty lakhs annual turnover - Nil digit </li>
					<li>more than rupees one crore fifty lakhs and up to rupees five crores annual turnover - 2 digit  </li>
					<li>more than rupees five crores annual turnover - 4  digit.</li>
				</ol>
			</li>
			<li><b>Description</b>	Enter the description of the supplied goods or Services. Description becomes a mandatory field if HSN code is not provided above.</li>
			<li><b>UQC</b>	Select the applicable Unit Quantity Code  from the drop down.</li>
			<li><b>Total Quantity</b>	Enter the total quantity of the supplied goods or Services- up to 2 decimal Digits.</li>
			<li><b>Total Value</b>	Enter the invoice  value of the goods or services-up to 2 decimal Digits.</li>
			<li><b>Taxable Value</b>	Enter the total taxable value of the supplied goods or services- up to 2 decimal Digits.</li>
			<li><b>Integrated Tax Amount</b>	Enter the total  Integrated tax amount collected/payable.</li>
			<li><b>Central Tax Amount</b>	Enter the total  Central tax amount collected/payable.</li>
			<li><b>State/UT Tax Amount</b>	Enter the total State/UT tax amount collected/payable.</li>
			<li><b>Cess Amount</b>	Enter the total  Cess amount collected/payable.</li>
		</ol>
</div>


</body>
</html>