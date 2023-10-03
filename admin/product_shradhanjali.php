<?
	header("Cache-control:private,no-cache");
	header("Expires: Mon, 26 Jun 1997 05:00:00 GMT");
	header("Pragma:no-cache");
	header("Cache:no-cache");
	
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("../common/functions.inc.php");
	
	$str_message = '';
	$str_error_msg = '';
	
	$img2 = '';
	$img3 = '';
	$img4 = '';
	$img5 = '';
	$img6 = '';
	
	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	

	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'save') {

			if (IsSet($_POST['id'])) {
				//***
				// edit existing product
				//***
				$int_id = $_POST['id'];
				
				$str_image = $_POST['str_image'];
				$int_reseller_client_id = $_POST['select_client'];
				$str_is_reseller_visible = 'N';
				$str_is_image_modified = 'N';
				$is_reseller_public = 'N';
				
				$img2 = $_POST['img2'];
				$img3 = $_POST['img3'];
				$img4 = $_POST['img4'];
				$img5 = $_POST['img5'];
				$img6 = $_POST['img6'];

				$web_description = addslashes($_POST['web_description']);
				$web_dimensions = addslashes($_POST['web_dimensions']);
				$web_weight = $_POST['web_weight'];
				$web_title = $_POST['web_title'];
				
				if (IsSet($_POST['is_reseller_visible']))
					$str_is_reseller_visible = 'Y';
				
				if (IsSet($_POST['is_image_modified']))
					$str_is_image_modified = 'Y';
					
				if (IsSet($_POST['is_reseller_public']))
					$is_reseller_public = 'Y';
				
				if (!empty($_POST['image_delete'])) {
					unlink($str_application_path."images/products/".$str_image);
					$str_image = '';
					
					$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
					$qry_updated = new Query($str_query);
				}
				else if (!empty($_FILES['product_image']['tmp_name'])) {
					if (is_uploaded_file($_FILES['product_image']['tmp_name'])) {
						$pos = check_jpg($_FILES['product_image']['name']);
						
						if ($pos > 0) {
							$str_image = "product_".$int_id.".jpg";
							$bool_success = move_uploaded_file($_FILES['product_image']['tmp_name'], $str_application_path."images/products/".$str_image);
							list($img_width, $img_height, $img_type, $img_attr) = getimagesize($str_application_path."images/products/".$str_image);
							
							if (($img_width > 300) || ($img_height > 300)) {
								$str_result = resize($str_application_path."images/products/".$str_image, $_FILES['product_image']["type"], 300, $str_application_path."images/products/".$str_image);
							}
							
							$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
							$qry_updated = new Query($str_query);
						}
						else
							$str_error_msg = "<font color='red'>Incorrect file type</font>";
					}
				}

				/*
				 * img 2
				 */
				if (!empty($_POST['img2_del'])) {
					unlink($str_application_path."images/products/".$img2);
					$img2 = '';
				}
				else if (!empty($_FILES['img2']['tmp_name'])) {
					if (is_uploaded_file($_FILES['img2']['tmp_name'])) {
						$pos = check_jpg($_FILES['img2']['name']);
						
						if ($pos > 0) {
							$img2 = "product_".$int_id."_img2.jpg";
							$bool_success = move_uploaded_file($_FILES['img2']['tmp_name'], $str_application_path."images/products/".$img2);
							list($img_width, $img_height, $img_type, $img_attr) = getimagesize($str_application_path."images/products/".$img2);
							
							if (($img_width > 300) || ($img_height > 300)) {
								$str_result = resize($str_application_path."images/products/".$img2, $_FILES['img2']["type"], 300, $str_application_path."images/products/".$img2);
							}
							
							$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
							$qry_updated = new Query($str_query);
							
						}
						else
							$str_error_msg = "<font color='red'>Incorrect file type image 2</font>";
					}
				}

				/*
				 * img 3
				 */
				if (!empty($_POST['img3_del'])) {
					unlink($str_application_path."images/products/".$img3);
					$img3 = '';
				}
				else if (!empty($_FILES['img3']['tmp_name'])) {
					if (is_uploaded_file($_FILES['img3']['tmp_name'])) {
						$pos = check_jpg($_FILES['img3']['name']);
						
						if ($pos > 0) {
							$img3 = "product_".$int_id."_img3.jpg";
							$bool_success = move_uploaded_file($_FILES['img3']['tmp_name'], $str_application_path."images/products/".$img3);
							list($img_width, $img_height, $img_type, $img_attr) = getimagesize($str_application_path."images/products/".$img3);
							
							if (($img_width > 300) || ($img_height > 300)) {
								$str_result = resize($str_application_path."images/products/".$img3, $_FILES['img3']["type"], 300, $str_application_path."images/products/".$img3);
							}
							
							$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
							$qry_updated = new Query($str_query);
							
						}
						else
							$str_error_msg = "<font color='red'>Incorrect file type image 3</font>";
					}
				}


				/*
				 * img 4
				 */
				if (!empty($_POST['img4_del'])) {
					unlink($str_application_path."images/products/".$img4);
					$img4 = '';
				}
				else if (!empty($_FILES['img4']['tmp_name'])) {
					if (is_uploaded_file($_FILES['img4']['tmp_name'])) {
						$pos = check_jpg($_FILES['img4']['name']);
						
						if ($pos > 0) {
							$img4 = "product_".$int_id."_img4.jpg";
							$bool_success = move_uploaded_file($_FILES['img4']['tmp_name'], $str_application_path."images/products/".$img4);
							list($img_width, $img_height, $img_type, $img_attr) = getimagesize($str_application_path."images/products/".$img4);
							
							if (($img_width > 300) || ($img_height > 300)) {
								$str_result = resize($str_application_path."images/products/".$img4, $_FILES['img4']["type"], 300, $str_application_path."images/products/".$img4);
							}
							
							$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
							$qry_updated = new Query($str_query);
							
						}
						else
							$str_error_msg = "<font color='red'>Incorrect file type image 4</font>";
					}
				}

				/*
				 * img 5
				 */
				if (!empty($_POST['img5_del'])) {
					unlink($str_application_path."images/products/".$img5);
					$img5 = '';
				}
				else if (!empty($_FILES['img5']['tmp_name'])) {
					if (is_uploaded_file($_FILES['img5']['tmp_name'])) {
						$pos = check_jpg($_FILES['img5']['name']);
						
						if ($pos > 0) {
							$img5 = "product_".$int_id."_img5.jpg";
							$bool_success = move_uploaded_file($_FILES['img5']['tmp_name'], $str_application_path."images/products/".$img5);
							list($img_width, $img_height, $img_type, $img_attr) = getimagesize($str_application_path."images/products/".$img5);
							
							if (($img_width > 300) || ($img_height > 300)) {
								$str_result = resize($str_application_path."images/products/".$img5, $_FILES['img5']["type"], 300, $str_application_path."images/products/".$img5);
							}
							
							$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
							$qry_updated = new Query($str_query);
							
						}
						else
							$str_error_msg = "<font color='red'>Incorrect file type image 4</font>";
					}
				}

				/*
				 * img 6
				 */
				if (!empty($_POST['img6_del'])) {
					unlink($str_application_path."images/products/".$img6);
					$img6 = '';
				}
				else if (!empty($_FILES['img6']['tmp_name'])) {
					if (is_uploaded_file($_FILES['img6']['tmp_name'])) {
						$pos = check_jpg($_FILES['img6']['name']);
						
						if ($pos > 0) {
							$img6 = "product_".$int_id."_img6.jpg";
							$bool_success = move_uploaded_file($_FILES['img6']['tmp_name'], $str_application_path."images/products/".$img6);
							list($img_width, $img_height, $img_type, $img_attr) = getimagesize($str_application_path."images/products/".$img6);
							
							if (($img_width > 300) || ($img_height > 300)) {
								$str_result = resize($str_application_path."images/products/".$img6, $_FILES['img6']["type"], 300, $str_application_path."images/products/".$img6);
							}
							
							$str_query = "UPDATE stock_product SET is_image_modified = 'Y' WHERE product_id = $int_id";
							$qry_updated = new Query($str_query);
							
						}
						else
							$str_error_msg = "<font color='red'>Incorrect file type image 4</font>";
					}
				}


				
				$str_query = "
					UPDATE stock_product
					SET
						image_filename = '$str_image',
						img2 = '$img2',
						img3 = '$img3',
						img4 = '$img4',
						img5 = '$img5',
						img6 = '$img6',
						is_reseller_visible = '$str_is_reseller_visible',
						is_image_modified = '$str_is_image_modified',
						is_reseller_public = '$is_reseller_public',
						reseller_client_id = $int_reseller_client_id,
						web_description = '$web_description',
						web_dimensions = '$web_dimensions',
						web_weight = '$web_weight',
						web_title = '$web_title'
					WHERE product_id = $int_id
				";
				$qry = new Query($str_query);
			}
		}
	}

	$str_image = '';
	$str_is_reseller_visible = 'Y';
	$str_is_image_modified = 'N';
	$is_reseller_public = 'N';
	$int_reseller_client_id = 0;
	$img2 = '';
	$img3 = '';
	$img4 = '';
	$img5 = '';
	$img6 = '';
	$web_description = '';
	$web_dimensions = '';
	$web_weight = '';
	$web_title = '';

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_product sp
			WHERE sp.product_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_image = trim($qry->FieldByName('image_filename'));
			$str_is_reseller_visible = $qry->FieldByName('is_reseller_visible');
			$is_reseller_public = $qry->FieldByName('is_reseller_public');
			$int_reseller_client_id = $qry->FieldByName('reseller_client_id');
			
			$img2 = trim($qry->FieldByName('img2'));
			$img3 = trim($qry->FieldByName('img3'));
			$img4 = trim($qry->FieldByName('img4'));
			$img5 = trim($qry->FieldByName('img5'));
			$img6 = trim($qry->FieldByName('img6'));

			$web_description = $qry->FieldByName('web_description');
			$web_dimensions = $qry->FieldByName('web_dimensions');
			$web_weight = $qry->FieldByName('web_weight');
			$web_title = $qry->FieldByName('web_title');
		}
	}

	$qry_clients = new Query("
		SELECT id, company
		FROM customer
		ORDER BY company
	");

?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function isNumeric(str){
			var re = /[\D]/g
			if (re.test(str)) return false;
			return true;
		}
		
		function saveData() {
			var can_save = true;
			
			if (can_save) {
				document.product_web.submit();
			}

			return can_save;
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
	<style>
		.img-caption {
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size:10px;
			font-weight:bold;
		}
	</style>
</head>
<body bgcolor="#e9ecf1" marginwidth=5 marginheight=5>
<form name='product_web' method='POST' enctype='multipart/form-data'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

?>
<input type='hidden' name='str_image' value='<?echo $str_image?>'>
<input type='hidden' name='img2' value='<?echo $img2?>'>
<input type='hidden' name='img3' value='<?echo $img3?>'>
<input type='hidden' name='img4' value='<?echo $img4?>'>
<input type='hidden' name='img5' value='<?echo $img5?>'>
<input type='hidden' name='img6' value='<?echo $img6?>'>
<input type='hidden' name='action' value='save'>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>

	<tr>
		<td colspan="2">
			<textarea name="web_description" rows="2" cols="50" placeholder="Description for Website"><?php echo $web_description; ?></textarea>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<textarea name="web_dimensions" rows="2" cols="50" placeholder="Product Dimenions for Website"><?php echo $web_dimensions; ?></textarea>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="text" name="web_weight" value="<?php echo $web_weight; ?>" placeholder="weight">
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="text" name="web_title" value="<?php echo $web_title; ?>" placeholder="title for website">
		</td>
	</tr>

	
	<tr>
		<td></td>
		<td>
			<label class="normaltext_bold" id="is_reseller_public">
				<input type='checkbox' id="is_reseller_public" name='is_reseller_public' <?if ($is_reseller_public=='Y') echo "checked";?>>
				This product is visible to the public
			</label>
		</td>
	</tr>
	<tr>
		<?
			if (!empty($str_image)) {
				echo "<td>&nbsp;</td><td><img height='100px' src='../images/products/".$str_image."'><br>";
					echo "<label class='normaltext_bold'><input type='checkbox' name='image_delete'>Delete this image</label></td>";
			} else { ?>
			<td class='img-caption'>
				Image 1:
			</td>
			<td>
				<input type='file' name='product_image'>
				<?if (!empty($str_error_msg)) echo "<br>".$str_error_msg;?>
			</td>
		<? } ?>
	</tr>
	<tr>
		<td class='login_text_bold'></td>
		<td>
			<label class="normaltext_bold" id="is_reseller_visible">
				<input type='checkbox' id="is_reseller_visible" name='is_reseller_visible' <?if ($str_is_reseller_visible=='Y') echo "checked";?>>
				Is visible to 
			</label>
			<select name="select_client" class="select_200">
				<option value=0>All</option>
				<?
					for ($i=0;$i<$qry_clients->RowCount();$i++) {
						if ($qry_clients->FieldByName('id') == $int_reseller_client_id)
							echo "<option value=".$qry_clients->FieldByName('id')." selected>".$qry_clients->FieldByName('company')."</option>\n";
						else
							echo "<option value=".$qry_clients->FieldByName('id').">".$qry_clients->FieldByName('company')."</option>\n";
						$qry_clients->Next();
					}
				?>
			</select>
			<font class="normaltext_bold">reseller(s)</font>

			<br>
			<label class="normaltext_bold" id="is_image_modified">
				<input type='checkbox' id="is_image_modified" name='is_image_modified' <?if ($str_is_image_modified=='Y') echo "checked";?>>
				Images have been modified
			</label>
		</td>
	</tr>
	<tr>
		<?
			if (!empty($img2)) {
				echo "<td>&nbsp;</td><td><img height='100px' src='../images/products/".$img2."'><br>";
					echo "<label class='normaltext_bold'><input type='checkbox' name='img2_del'>Delete this image</label></td>";
			} else { ?>
			<td class='img-caption'>
				Image 2:
			</td>
			<td>
				<input type='file' name='img2'>
				<?if (!empty($str_error_msg)) echo "<br>".$str_error_msg;?>
			</td>
		<? } ?>
	</tr>
	<tr>
		<?
			if (!empty($img3)) {
				echo "<td>&nbsp;</td><td><img height='100px' src='../images/products/".$img3."'><br>";
					echo "<label class='normaltext_bold'><input type='checkbox' name='img3_del'>Delete this image</label></td>";
			} else { ?>
			<td class='img-caption'>
				Image 3:
			</td>
			<td>
				<input type='file' name='img3'>
				<?if (!empty($str_error_msg)) echo "<br>".$str_error_msg;?>
			</td>
		<? } ?>
	</tr>
	<tr>
		<?
			if (!empty($img4)) {
				echo "<td>&nbsp;</td><td><img height='100px' src='../images/products/".$img4."'><br>";
					echo "<label class='normaltext_bold'><input type='checkbox' name='img4_del'>Delete this image</label></td>";
			} else { ?>
			<td class='img-caption'>
				Image 4:
			</td>
			<td>
				<input type='file' name='img4'>
				<?if (!empty($str_error_msg)) echo "<br>".$str_error_msg;?>
			</td>
		<? } ?>
	</tr>
	<tr>
		<?
			if (!empty($img5)) {
				echo "<td>&nbsp;</td><td><img height='100px' src='../images/products/".$img5."'><br>";
					echo "<label class='normaltext_bold'><input type='checkbox' name='img5_del'>Delete this image</label></td>";
			} else { ?>
			<td class='img-caption'>
				Image 5:
			</td>
			<td>
				<input type='file' name='img5'>
				<?if (!empty($str_error_msg)) echo "<br>".$str_error_msg;?>
			</td>
		<? } ?>
	</tr>
	<tr>
		<?
			if (!empty($img6)) {
				echo "<td>&nbsp;</td><td><img height='100px' src='../images/products/".$img6."'><br>";
					echo "<label class='normaltext_bold'><input type='checkbox' name='img6_del'>Delete this image</label></td>";
			} else { ?>
			<td class='img-caption'>
				Image 6:
			</td>
			<td>
				<input type='file' name='img6'>
				<?if (!empty($str_error_msg)) echo "<br>".$str_error_msg;?>
			</td>
		<? } ?>
	</tr>
	
</table>

</form>

	<script src="../include/js/jquery-3.2.1.min.js"></script>


	<script>

	    $( document ).ready(function() {

	    	window.saveData = function() {

				var can_save = true;
				
				if (can_save) {
					document.product_web.submit();
				}

				return can_save;

	    	}

	    });

	</script>


</body>
</html>