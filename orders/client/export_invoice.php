<?php

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once "../include/grid.inc.php";

	require_once("PEAR/MDB2.php");
	require_once("PEAR/PDF.php");
	require_once("PEAR/HTTP.php");
	require_once("PEAR/HTTP/Header.php");

	require_once("mdb2_params.php");
	
	$str_include_price = 'N';
	if (IsSet($_GET['include_price']))
		$str_include_price = $_GET['include_price'];
	
	$str_on_clause = " AND (c.category_id IN (".$_GET['ids'].")) ";
	
	$sql = "SELECT price_increase FROM clients c WHERE id = ".$_SESSION['user_id'];
	$qry =& $mdb2->query($sql);

	$price_increase = 1;
	if ($qry) {
		$obj =& $qry->fetchRow();
		$price_increase = (100 + $obj->price_increase) / 100;
	}

	$str_query = "
		SELECT *
		FROM products p
		INNER JOIN categories c ON (c.category_id = p.category_id) $str_on_clause
		WHERE is_visible = 'Y'
			AND (client_id IN (0, ".$_SESSION['user_id']."))
		ORDER BY product_code
	";
	$qry =& $mdb2->query($str_query);
	
	class MyPDF extends File_PDF {
	
		function header() {
			// Select Arial bold 15
			global $str_include_price;
			
			$this->setXY(7,7);
			$this->setFont('Arial', 'B', 8);
			$this->write(10, "Shradhanjali Price List");
			
			$this->setY(15);
			$this->setX(7);
			$this->write(6, 'CODE');
			
			$this->setX(30);
			$this->write(6, 'DESCRIPTION');
			
			$this->setX(140);
			$this->write(6, 'CATEGORY');
			
			if ($str_include_price == 'Y') {
				$this->setFont('Arial','B',8);
				$this->setX(180);
				$this->write(6, 'PRICE');
			}
			
			$this->setY(20);
		}
		
		function footer() {
			// Select Arial bold 15
			$intY = $this->getY();
			$this->setXY(7,$intY+10);
			$this->setFont('Arial', '', 8);
			$str = date('d-m-Y', time())." "."Page: ".$this->getPageNo();
			$this->write(10, $str);
			
			$this->setY(0);
		}
	}
	
	// Set up the pdf object.
	$pdf = &MyPDF::factory(array('orientation' => 'P','unit' => 'mm','format' => 'A4'), 'MyPDF');
	
	// Start the document.
	$pdf->open();
	
	// Activate compression.
	$pdf->setCompression(true);
	
	// Start a page.
	$pdf->addPage();
	
	$pdf->setY(20);
	
	while ($obj =& $qry->fetchRow()) {
		$intX = 7;
		$intY = $pdf->getY();
		
		/*
			product code
		*/
		$pdf->setFont('Arial','',8);
		$str = $obj->product_code;
		$pdf->setXY($intX,$intY);
		$pdf->write(6, $str);
		
		/*
			product description
		*/
		$intX += 23;
		$intY = $pdf->getY();
			
		$pdf->setFont('Times','',10);
		$str = $obj->product_description;
		$pdf->setXY($intX,$intY); // 30
		$pdf->write(6, $str);
		
		
		/*
			category
		*/
		$intY = $pdf->getY();
		$intX += 110;
		$str = $obj->category_description;
		$pdf->setXY($intX, $intY); // 140
		$pdf->write(6, $str);
		
		/*
			price
		*/
		if ($str_include_price == 'Y') {

			$price = round($obj->mrp * $price_increase);

			$intY = $pdf->getY();
			$intX += 40;
			$pdf->setFont('Arial','',8);
			$str = $_SESSION['currency_name']." ".number_format(($price/$_SESSION['currency_rate']),2,'.',',')."\r\n";
			$pdf->setXY($intX,$intY); // 180
			$pdf->write(6, $str);
		}
		else {
			$intY = $pdf->getY();
			$intX += 40;
			$pdf->setFont('Arial','',8);
			$str = "\r\n";
			$pdf->setXY($intX,$intY); // 180
			$pdf->write(6, $str);
		}
	}

	$pdf->output('price_list.pdf', true);
?>