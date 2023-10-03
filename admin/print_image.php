<?

function print_image($printer, $filename) {

	$filename = "e:/xampp/tmp/barcode.jpg";

	$phPrinter = printer_open($printer);
	printer_set_option($phPrinter, "PRINTER_FORMAT_CUSTOM");
	printer_set_option($phPrinter, "PRINTER_PAPER_LENGTH", 25);
	printer_set_option($phPrinter, "PRINTER_PAPER_WIDTH", 110);
	
	$mgk = NewMagickWand();

	checkWandError(MagickReadImage($mgk, $filename), $mgk, __LINE__);
	
	$img_width = MagickGetImageWidth($mgk);
	$img_height = MagickGetImageHeight($mgk);
	$img_resolution = 96; // MagickGetImageResolution($mgk);
	$ptr_resolution_x = printer_get_option($phPrinter, PRINTER_RESOLUTION_X);
	$ptr_resolution_y = printer_get_option($phPrinter, PRINTER_RESOLUTION_Y);
	$pgWidth = floor((printer_get_option($phPrinter, PRINTER_PAPER_WIDTH) / 25.4) * $ptr_resolution_x);  //width in pixels
	$pgHeight = floor((printer_get_option($phPrinter, PRINTER_PAPER_LENGTH) / 25.4) * $ptr_resolution_y); //height in pixels

	MagickSetLastIterator($mgk);
	MagickNewImage($mgk, 5, $img_height, 'black');
	MagickSetLastIterator($mgk);
	checkWandError(MagickReadImage($mgk, $filename), $mgk, __LINE__);
	MagickSetLastIterator($mgk);
	MagickNewImage($mgk, 5, $img_height, 'black');
	MagickSetLastIterator($mgk);
	checkWandError(MagickReadImage($mgk, $filename), $mgk, __LINE__);

	//===================================================================
	// three copies of the labels, plus the 10 pixel gap in between twice
        //-------------------------------------------------------------------
	$img_width = ($img_width * 3) + 20;
	
        MagickSetFirstIterator($mgk);
        $mgk2 = MagickAppendImages($mgk);

	$img_scale_x = $ptr_resolution_x / $img_resolution;
	$img_scale_y = $ptr_resolution_y / $img_resolution;
	$ptr_width = $img_width * $img_scale_x; 
	$ptr_height = $img_height * $img_scale_y;

	printer_start_doc($phPrinter, "Barcode Print");

	$file_name = "e:/xampp/tmp/".time().".bmp";

	printer_start_page($phPrinter);
	checkWandError(MagickSetImageDepth($mgk2, 8), $mgk2, __LINE__);
	checkWandError(MagickSetImageFormat($mgk2, 'BMP'), $mgk2, __LINE__);
	checkWandError(MagickWriteImage($mgk2, $file_name), $mgk2, __LINE__);

	printer_draw_bmp($phPrinter, $file_name, 1, 1, $ptr_width, $ptr_height);
	printer_end_page($phPrinter);

	if (is_file($file_name))
		unlink($file_name);

	printer_end_doc($phPrinter);
	printer_close($phPrinter);

	DestroyMagickWand($mgk);
	DestroyMagickWand($mgk2);
}


function &checkWandError(&$result, $wand, $line) {
	if ($result === FALSE && WandHasException($wand)) {
		die("An error occurred on line ".$line.": ".WandGetExceptionString($wand));
		exit();
    	}
	return $result;
}

?>