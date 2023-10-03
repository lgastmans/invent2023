<?

function print_image($printer, $filename) {

	$phPrinter = printer_open($printer);

	$mgk = NewMagickWand();

	checkWandError(MagickReadImage($mgk, $filename), $mgk, __LINE__);

	$img_width = MagickGetImageWidth($mgk);
	$img_height = MagickGetImageHeight($mgk);
	$img_resolution = 96; // MagickGetImageResolution($mgk);
	$ptr_resolution_x = printer_get_option($phPrinter, PRINTER_RESOLUTION_X);
	$ptr_resolution_y = printer_get_option($phPrinter, PRINTER_RESOLUTION_Y);
	$pgWidth = floor((printer_get_option($phPrinter, PRINTER_PAPER_WIDTH) / 25.4) * $ptr_resolution_x);  //width in pixels
	$pgHeight = floor((printer_get_option($phPrinter, PRINTER_PAPER_LENGTH) / 25.4) * $ptr_resolution_y); //height in pixels

	$img_scale_x = $ptr_resolution_x / $img_resolution;
	$img_scale_y = $ptr_resolution_y / $img_resolution;
	$ptr_width = $img_width * $img_scale_x; 
	$ptr_height = $img_height * $img_scale_y;

	printer_start_doc($phPrinter, "Test Print");

	$file_name = "c:/xampp/tmp/".time().".bmp";

	printer_start_page($phPrinter);
	checkWandError(MagickSetImageIndex($mgk, $i), $mgk, __LINE__);
	checkWandError(MagickSetImageDepth($mgk, 8), $mgk, __LINE__);
	checkWandError(MagickSetImageFormat($mgk, 'BMP'), $mgk, __LINE__);

	checkWandError(MagickWriteImage($mgk, $file_name), $mgk, __LINE__);
	printer_draw_bmp($phPrinter, $file_name, 1, 1, $ptr_width, $ptr_height); //stretch to fit page.
	printer_end_page($phPrinter);

	if (is_file($file_name))
      	unlink($file_name);

	printer_end_doc($phPrinter);
	printer_close($phPrinter);
	DestroyMagickWand($mgk);
}

function &checkWandError(&$result, $wand, $line) {
    if ($result === FALSE && WandHasException($wand)) {
        echo '<pre>An error occurred on line ', $line, ': ', WandGetExceptionString($wand), '</pre>';
        exit();
    }
return $result;
}

?>            
