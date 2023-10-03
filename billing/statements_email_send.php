<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");
//	require_once("../include/PHPMailer-5.2.26/PHPMailerAutoload.php");
	require_once("../include/sendgrid-php/sendgrid-php.php");


	$calc_price = "BP";
	if (IsSet($_GET['price']))
		$calc_price = $_GET['price'];
	
	$where_filter_day = "";
	if (IsSet($_GET['filter_day']) && ($_GET['filter_day']!='ALL'))
		$where_filter_day = "AND (DAYOFMONTH(b.date_created)=".$_GET['filter_day'].") ";

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
	");
	/*
	if ($sql_settings->RowCount() > 0) {
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}
	*/
	$str_calc_tax_first = "N";

	$str_format = "DATE_BILL";
	if (IsSet($_GET['format']))
		$str_format = $_GET['format'];
	
	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;
	
	$str_order_by = 'b.date_created';
	if (IsSet($_GET['order_by'])) {
		if ($_GET['order_by'] == 'date')
			$str_order_by = 'b.date_created, sp.product_code';
		else if ($_GET['order_by'] == 'code')
			$str_order_by = 'sp.product_code';
	}
	
	$_SESSION['global_current_supplier_id'] = $int_supplier_id;
	
	$str_include_tax = 'Y';
	if (IsSet($_GET['include_tax']))
		$str_include_tax = $_GET['include_tax'];

	/*
		for previous month/year requests get the commissions
		from the table stock_supplier_commissions
	*/
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		$sql = "
			SELECT *
			FROM stock_supplier
			WHERE (supplier_id = ".$int_supplier_id.")
		";
	}
	else {
		$sql = "
			SELECT *
			FROM stock_supplier_commissions ssc
			INNER JOIN stock_supplier ss ON (ss.supplier_id = ssc.supplier_id)
			WHERE (ssc.`supplier_id` = ".$int_supplier_id.")
				AND (ssc.`month` = ".$_SESSION["int_month_loaded"].")
				AND (ssc.`year` = ".$_SESSION["int_year_loaded"].")
		";
		
	}
	$qry_supplier = new Query($sql);

	$consignment = ($qry_supplier->FieldByName('is_supplier_delivering') == 'Y' ? true : false);

	$flt_percent = 0;
	$flt_percent_2 = 0;
	$flt_percent_3 = 0;
	if ($qry_supplier->RowCount() > 0) {
		$flt_percent = $qry_supplier->FieldByName('commission_percent');
		$flt_percent_2 = $qry_supplier->FieldByName('commission_percent_2');
		$flt_percent_3 = $qry_supplier->FieldByName('commission_percent_3');
	}
	
	if ($str_format == 'DATE_BILL')
		$str_query = "
			SELECT DAYOFMONTH(b.date_created) AS date_created, b.bill_number, b.is_debit_bill,
				sp.product_code, sp.product_id,
				bi.product_description,
				bi.price,
				bi.tax_id,
				st.tax_description,
				IF(b.is_debit_bill='Y',
					(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
					ROUND(bi.quantity + bi.adjusted_quantity, 3)
				) AS quantity,
				bi.discount,
				IF(b.is_debit_bill='Y',
					IF(bi.discount > 0,
							(ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2)*-1),
							(ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2)*-1)
						),
					IF(bi.discount > 0,
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2)
						)
				)
				AS amount,
				smu.is_decimal
			FROM ".Monthalize('bill')." b, 
				".Monthalize('bill_items')." bi, 
				stock_product sp, 
				".Yearalize('stock_batch')." sb,
				stock_measurement_unit smu,
				".Monthalize('stock_tax')." st,
				stock_category sc
			WHERE (bi.bill_id = b.bill_id)
				AND (
						(b.bill_status = ".BILL_STATUS_RESOLVED.")
						OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
					)
				AND (sp.product_id = bi.product_id)
				AND (sb.product_id = bi.product_id)
				AND (sb.supplier_id = ".$int_supplier_id.")
				AND (sb.batch_id = bi.batch_id)
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
				AND (sp.category_id = sc.category_id)
				AND (bi.tax_id = st.tax_id)
				$where_filter_day
			ORDER BY $str_order_by";
	else
		$str_query = "
			SELECT sp.product_code, sp.product_id,
				bi.product_description,
				bi.price,
				bi.tax_id,
				b.is_debit_bill,
				SUM(
					IF(b.is_debit_bill='Y',
						(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
						ROUND(bi.quantity + bi.adjusted_quantity, 3)
					)
				) AS quantity,
				bi.discount,
				SUM(
					IF(b.is_debit_bill='Y',
						IF(bi.discount > 0,
								(ROUND((bi.price * (1 - (bi.discount/100)) * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2)*-1),
								(ROUND((bi.price * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2))
							),
						IF(bi.discount > 0,
								ROUND((bi.price * (1 - (bi.discount/100)) * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2),
								ROUND((bi.price * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2)
							)
					)
				)
				AS amount,
				smu.is_decimal,
				sc.category_description
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp,
				".Yearalize('stock_batch')." sb,
				stock_measurement_unit smu,
				".Monthalize('stock_tax')." st,
				stock_category sc
			WHERE (bi.bill_id = b.bill_id)
				AND (
					(b.bill_status = ".BILL_STATUS_RESOLVED.")
					OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
				)
				AND (sp.product_id = bi.product_id)
				AND (sb.product_id = bi.product_id)
				AND (sb.supplier_id = ".$int_supplier_id.")
				AND (sb.batch_id = bi.batch_id)
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
				AND (sp.category_id = sc.category_id)
				AND (bi.tax_id = st.tax_id)
				$where_filter_day
			GROUP BY bi.product_id, bi.price, b.is_debit_bill, bi.discount
			ORDER BY sc.category_description, sp.product_code
		";

	//echo $str_query;
	$qry = new Query($str_query);


	if ($calc_price == "SP")
		$str = "on selling price";
	else
		$str = "on buying price";

	if (IsSet($_GET['filter_day']) && ($_GET['filter_day']!='ALL')) {
		$str_title = "Supplier Statement $str for ".$qry_supplier->FieldByName('supplier_name')." for ".$_GET['filter_day'].", ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];
		$filename = $qry_supplier->FieldByName('supplier_name')."_sales_".$_GET['filter_day']."_".getMonthName($_SESSION["int_month_loaded"])."_".$_SESSION["int_year_loaded"].".csv";
	}
	else {
		$str_title = "Supplier Statement $str for ".$qry_supplier->FieldByName('supplier_name')." for ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];
		$filename = $qry_supplier->FieldByName('supplier_name')."_sales_".getMonthName($_SESSION["int_month_loaded"])."_".$_SESSION["int_year_loaded"].".csv";
	}


    /*
    	get Company details
    */
    $company = new Query("SELECT * FROM company WHERE 1");


	/*

		Generate the CSV file

	*/		

    $fp = fopen($str_root.'temp/'.$filename, 'w+');
//print_r($fp);

    fputcsv($fp, array($str_title));



	if ($str_format == "DATE_BILL") {

		if ($str_include_tax == 'Y')
			 fputcsv($fp, array("Date","Bill","Code","Description","Qty","Price","Discount","Taxable Value","Tax Rate","Tax Amt","Amount"));
		else
			 fputcsv($fp, array("Date","Bill","Code","Description","Qty","Price","Discount","Taxable Value","Amount"));

		$date_current = 0;
		$total = 0;
		$total_qty = 0;
		$total_taxable_value = 0;
		$total_tax_amount = 0;
		$total_amount = 0;
		
		for ($i=0;$i<$qry->RowCount();$i++) {
			
			$row = array();

			if ($calc_price == "BP")
				$flt_price = number_format(getBuyingPrice($qry->FieldByName('product_id')), 2,'.','');
			else
				$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');


			$discount = $qry->FieldByName('discount');

			if ((!$consignment) && ($calc_price == "BP")) {
				$discount =  0;
			}


			$is_debit_bill = $qry->FieldByName('is_debit_bill');

			/*
				quantity includes adjusted (see query)
			*/
			$quantity = $qry->FieldByName('quantity');
			
			if ($str_include_tax == 'Y') {
				$tax_id = $qry->FieldByName('tax_id');
				
				if ($discount > 0) {
					if ($str_calc_tax_first == 'Y') {
						$tax_price = round($flt_price + calculateTax($flt_price, $tax_id),3);
						$tax_amount = calculateTax(($flt_price * $quantity), $tax_id);
						$flt_discount = round(($quantity * $tax_price) * ($discount/100),3);
						
						$flt_amount = round(($quantity * $tax_price - $discount), 3);
					}
					else {
						$discount_price = round(($flt_price * (1 - ($discount/100))), 3);
						$tax_amount = calculateTax($quantity * $discount_price, $tax_id);
						$flt_amount = round(($quantity * $discount_price + $tax_amount), 3);
					}
				}
				else {
					$discount_price = $flt_price;
					$tax_amount = calculateTax($flt_price * $quantity, $tax_id);
					$flt_amount = round(($quantity * $flt_price + $tax_amount), 3);
				}
				$flt_amount = number_format($flt_amount, 2, '.', '');
			}
			else {
				if ($discount > 0) {
					$flt_amount = number_format(($flt_price * (1 - ($discount/100)) * $quantity), 2, '.','');
				}
				else {
					$flt_amount = number_format(($flt_price * $quantity), 2, '.','');
				}
			}
			
			if ($is_debit_bill == 'Y')
				$flt_amount = $flt_amount * -1;

			
			if ($str_order_by == 'b.date_created, sp.product_code') {

				if ($date_current < $qry->FieldByName('date_created')) {

					$row[$i][] = $qry->FieldByName('date_created');
					$date_current = $qry->FieldByName('date_created');

				}
				else
					$row[$i][] = " ";
			}
			else
				$row[$i][] = $qry->FieldByName('date_created');

			
			$row[$i][] = $qry->FieldByName('bill_number');

			$row[$i][] = $qry->FieldByName('product_code');

			$row[$i][] = $qry->FieldByName('product_description');

			$tmp_qty = $qry->FieldByName('quantity');
			if ($qry->FieldByName('is_decimal') == 'Y')
				$row[$i][] = number_format($tmp_qty, 2, '.', '');
			else
				$row[$i][] = number_format($tmp_qty, 0, '.', '');

			$row[$i][] = $flt_price;

			$row[$i][] = $discount;

			$row[$i][] = number_format(($discount_price * $tmp_qty),2,'.',',');

			if ($str_include_tax == 'Y') {
				$row[$i][] = $qry->FieldByName('tax_description');
				$row[$i][] = $tax_amount;
			}
			
			
			$row[$i][] = $flt_amount;
			
			$total += $flt_amount;
			$total_qty += $qry->FieldByName('quantity');
			$total_taxable_value += ($discount_price * $tmp_qty);
			$total_tax_amount += $tax_amount;
			$total_amount += $flt_amount;

			fputcsv($fp, $row[$i]);

			$qry->Next();
		}

		$commission = $total * ($flt_percent/100);
		
		$commission_2 = 0;
		if ($flt_percent_2 > 0)
			$commission_2 = $total * ($flt_percent_2/100);
			
		$commission_3 = 0;
		if ($flt_percent_3 > 0)
			$commission_3 = $total * ($flt_percent_3/100);
			
		$total = number_format($total,2,'.','');
		$commission = number_format($commission,2,'.','');
		$commission_2 = number_format($commission_2,2,'.','');
		$commission_3 = number_format($commission_3,2,'.','');
		$flt_percent = number_format($flt_percent,2,'.','');
		$flt_percent_2 = number_format($flt_percent_2,2,'.','');
		$flt_percent_3 = number_format($flt_percent_3,2,'.','');
	}
	else {
		$category_current = '';
		$total = 0;
		$total_qty = 0;
		$total_taxable_value = 0;
		
		for ($i=0;$i<$qry->RowCount();$i++) {
			
			$row = array();

			if ($calc_price == "BP")
				$flt_price = number_format(getBuyingPrice($qry->FieldByName('product_id')), 2,'.','');
			else
				$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');

			$discount = $qry->FieldByName('discount');

			if ((!$consignment) && ($calc_price == "BP")) {
				$discount =  0;
			}

			$is_debit_bill = $qry->FieldByName('is_debit_bill');

			/*
				quantity includes adjusted (see query)
			*/
			$quantity = $qry->FieldByName('quantity');
			
			if ($str_include_tax == 'Y') {
				$tax_id = $qry->FieldByName('tax_id');
				
				if ($discount > 0) {
					if ($str_calc_tax_first == 'Y') {
						$tax_price = round($flt_price + calculateTax($flt_price, $tax_id),3);
						$tax_amount = calculateTax(($flt_price * $quantity), $tax_id);
						$flt_discount = round(($quantity * $tax_price) * ($discount/100),3);
						
						$flt_amount = round(($quantity * $tax_price - $discount), 3);
					}
					else {
						$discount_price = round(($flt_price * (1 - ($discount/100))), 3);
						$tax_amount = calculateTax($quantity * $discount_price, $tax_id);
						$flt_amount = round(($quantity * $discount_price + $tax_amount), 3);
					}
				}
				else {
					$discount_price = $flt_price;
					$tax_amount = calculateTax($flt_price * $quantity, $tax_id);
					$flt_amount = round(($quantity * $flt_price + $tax_amount), 3);
				}
				$flt_amount = number_format($flt_amount, 2, '.', '');
			}
			else {
				if ($discount > 0) {
					$flt_amount = number_format(($flt_price * (1 - ($discount/100)) * $quantity), 2, '.','');
				}
				else {
					$flt_amount = number_format(($flt_price * $quantity), 2, '.','');
				}
			}
			
			if ($is_debit_bill == 'Y')
				$flt_amount = $flt_amount * -1;
			
			if ($category_current <> $qry->FieldByName('category_description')) {
				$row[$i][] = $qry->FieldByName('category_description');
				$category_current = $qry->FieldByName('category_description');
			}
			
			$row[$i][] = $qry->FieldByName('product_code');
			$row[$i][] = $qry->FieldByName('product_description');

			$tmp_qty = $qry->FieldByName('quantity');
			if ($qry->FieldByName('is_decimal') == 'Y')
				$row[$i][] = number_format($tmp_qty, 2, '.', '');
			else
				$row[$i][] = number_format($tmp_qty, 0, '.', '');

			$row[$i][] = $flt_price;
			$row[$i][] = $discount;
			$row[$i][] = ($discount_price * $tmp_qty);

			$row[$i][] = $flt_amount;
			
			$total += $flt_amount;
			$total_qty += $qry->FieldByName('quantity');
			$total_taxable_value = $discount_price * $tmp_qty;
			
			fputcsv($fp, $row[$i]);

			$qry->Next();
		}
		
		$commission = $total * ($flt_percent/100);
		
		$commission_2 = 0;
		if ($flt_percent_2 > 0)
			$commission_2 = $total * ($flt_percent_2/100);
			
		$commission_3 = 0;
		if ($flt_percent_3 > 0)
			$commission_3 = $total * ($flt_percent_3/100);
			
		$total = number_format($total,2,'.','');
		$commission = number_format($commission,2,'.','');
		$commission_2 = number_format($commission_2,2,'.','');
		$commission_3 = number_format($commission_3,2,'.','');
		$flt_percent = number_format($flt_percent,2,'.','');
		$flt_percent_2 = number_format($flt_percent_2,2,'.','');
		$flt_percent_3 = number_format($flt_percent_3,2,'.','');
	}

	$total_qty = number_format($total_qty,2,'.','');
	$total_taxable_value = number_format($total_taxable_value,2,'.','');
	$total_tax_amount = number_format($total_tax_amount,2,'.','');
	$total_amount = number_format($total_amount,2,'.','');

	
		$given = $total_taxable_value - $commission - $commission_2 - $commission_3;


	if ($calc_price == 'SP') {

		fputcsv($fp, array("Total ", number_format($total_taxable_value, 2, '.', ',')));

		fputcsv($fp, array("Commission ".$flt_percent."%", number_format($commission, 2, '.', ',')));

		if ($flt_percent_2 > 0) { 
			fputcsv($fp, array("Commission ".$flt_percent_2."%", number_format($commission_2, 2, '.', ',')));
		} 
		
		if ($flt_percent_3 > 0) {
			fputcsv($fp, array("Commission ".$flt_percent_3."%:", number_format($commission_3, 2, '.', ',')));
		}

		fputcsv($fp, array("Given", number_format($given, 2, '.', ',')));

	}

	if ($calc_price == 'BP') {
		$line = array(" "," "," "," ",$total_qty," "," ",number_format($total_taxable_value,2,'.',',')," ",number_format($total_tax_amount,2,'.',','),number_format($total,2,'.',','));
		fputcsv($fp, $line);
	}


	/*

		email details

	*/

	$to = $qry_supplier->FieldByName('supplier_email');
	if (empty($to)) {
		echo "Senders email address must be provided";
		return false;
	}

	$from = $company->FieldByName('email');
	if (empty($from)) {
		echo "Your email address must be provided";
		return false;
	}
	$subject = "Sales Statement ".$_SESSION['int_year_loaded']."-".$_SESSION['int_month_loaded'];
	$body = "Dear People,<br><p>Please find attached a CSV file of your sales statement for ".$_SESSION['int_year_loaded']."-".$_SESSION['int_month_loaded'].".</p><p> This file can be imported into Excel.</p><p>The fields are separated by a <strong>comma</strong> and the <strong>delimiter is a double quote</strong>.</p>";


	/*

		send email via SendGrid

	*/

	$email = new \SendGrid\Mail\Mail(); 

	$email->setFrom($from, $company->title);
	$email->setSubject($subject);
	$email->addTo($to, "");
	$email->addContent("text/html", $body);

	$file_encoded = base64_encode(file_get_contents($str_root.'temp/'.$filename));
	$email->addAttachment(
	    $file_encoded,
	    "application/text",
	    $filename,
	    "attachment"
	);

	$sendgrid = new SendGrid(SENDGRID_API_KEY);

	try {

	    $response = $sendgrid->send($email);

	    echo "Email sent successfully from <strong>$from</strong> <br>to <strong>$to</strong><br> with sales statement attached.";
	    //echo $response->statusCode() . "<br>". $response->headers(). "<br>". print_r($response->body());

	} catch (Exception $e) {

	    echo 'Caught exception: '. $e->getMessage() ."\n";

	}

?>