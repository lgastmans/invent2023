<?
/**
*
* @version 	$Id: viewstock.php,v 1.2 2006/02/20 03:58:37 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product Edit
* @name  	viewstock.php
*
* This file uses the pear QuickForm components to display a dialog
* which lets the user modify product information
*
* Get Parameters:
* $_GET[id]		The product_id to load if you want to edit a product.  Otherwise
*			the insert product page will be shown
* Variables:
* $form 			The HTML QuickForm instance
* $bool_is_cur_month		True when active month is the current calendar month
* $bool_can_modify_record	True if user showing this page has modify rights
*/

//  error_reporting(E_ERROR|E_WARNING);
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once("../common/functions.inc.php");
  
  require_once 'HTML/QuickForm.php';
  $form =& new HTML_QuickForm('frmTest', 'get');
//
//  check permissions
//
  $bool_is_cur_month = $_SESSION["int_month_loaded"]==Date("m",time());
  $bool_can_modify_record = false;

  $bool_can_modify_record = (getModuleAccessLevel('Stock')>1);

  if ($_SESSION["int_user_type"]>1) {
	$bool_can_modify_record = true;
  }

//
// if a get parameter is passed, then edit, otherwise insert
//
  if (empty($_GET["id"])) {
	$form->addElement('header', '', 'Add Product To Storeroom');
  	$form->addElement('hidden', 'product_id', '0');

 	$cat_list = buildCategoryList();
	$qry_product= new Query("select * from stock_product where category_id=0 and storeroom_id=".$_SESSION['int_current_storeroom']);
	for ($i=0;$i<count($cat_list);$i++) {
    		$cat_list2[$cat_list[$i]["category_id"]] =  $cat_list[$i]["category_description"];
		$qry_product->Query("select * from stock_product where category_id=".$cat_list[$i]["category_id"]);
		for ($j=0;$j<$qry_product->RowCount();$j++) {
			$arr_products[$cat_list[$i]["category_id"]][$qry_product->FieldByName('product_id')]=$qry_product->FieldByName('product_description');
			$qry_product->Next();
		}
  	}
//  	$cat_select =& $form->addElement('select', 'category_id', 'Category:', $cat_list2);

$opts[] = $cat_list2;
$opts[] = $arr_products;

$hs =& $form->addElement('hierselect', 'product_sel', 'Select Product:', array('style' => 'width: 20em;'), '<br />');
$hs->setOptions($opts);

}
  else {
	$qry_product= new Query("select product_description from stock_product where product_id=".$_GET["id"]." and storeroom_id = ".$_SESSION['int_current_storeroom']);

	$form->addElement('header', '', 'View/Modify '.$qry_product->FieldByName('product_description'));
	$form->addElement('hidden', 'product_id', $_GET['id']);
}


  $form->addElement('text', 'sale_price', 'Sale Price:');
  $form->addElement('text', 'point_price', 'Point Value:');
  $b_element=$form->addElement('checkbox', 'use_batch_price', 'Use Batch Price', '');
  $form->addElement('text', 'minimum_qty', 'Minimum Quantity:');



//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules



  $form->addRule('minimum_qty', 'Minimum quantity is not valid', 'numeric', null, 'client');
  $form->addRule('sale_price', 'Sale Price not valid', 'numeric', null, 'client');
  $form->addRule('point_price', 'Point value is not valid', 'numeric', null, 'client');




/**
 * Check for duplicate product code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_product($element_name, $element_value) {

	if (count($element_value)<2) {
	      return false;
    	}
    	$qry = new Query("select * from ".Monthalize("stock_storeroom_product")." where product_id=".$element_value[1].' and storeroom_id='.$_SESSION['int_current_storeroom']);
    	if ($qry->RowCount()>0) {
		return false;
	}


   	return true;

 }



  $form->registerRule('check_product','function','fn_check_product'); $form->addRule('product_sel','Product exists already or is not selected properly!','check_product');

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
  if (!empty($_GET['id'])) {
  $id = $_GET['id'];
	 $sql = new Query("
			SELECT
				*
			FROM
				".Monthalize("stock_storeroom_product")."
			WHERE
				product_id=$id AND storeroom_id = ".$_SESSION['int_current_storeroom']);

	$form->setDefaults(array(
		'minimum_qty'  => ($sql->FieldByName("stock_minimum")+0),
		'sale_price'  => ($sql->FieldByName("sale_price")+0),
		'point_price'  => ($sql->FieldByName("point_price")+0),
		'use_batch_price'  => ($sql->FieldByName("use_batch_price"))
	));
	$b_element->setValue(($sql->FieldByName("use_batch_price")=='Y'?'1':'0'));

  }  else {
	$form->setDefaults(array(
		'minimum_qty'=>'0',
		'sale_price'=>'0',
		'point_price'=>'0',
		'use_batch_price'=>'Y'
	));
	$b_element->setValue('Y');

  }



if (empty($_GET["id"]))  {
	if ($form->validate()) {
    // Form is validated, then processes the data
		$form->freeze();
	    	$form->process('saveForm', false);
	    	echo "\n<HR>\n";
 	}

 }
// Process callback

$form->display();

/**
 * Save form after all processing is done
 *
 * @param   array    array of all form variables and their values
 *
 */
function saveForm($values) {
    $msg = "";
    if ($msg=="") {
    	$confirm = "";
    	// for a new record
	$parentRefresh = false;

    	if ($values['product_id']<=0) {
	    	$stUpdate = "
		INSERT INTO ".Monthalize('stock_storeroom_product')." (
			product_id,
			storeroom_id,
			stock_minimum,
			sale_price,
			point_price,
			use_batch_price
			)
			VALUES (
			".$values['product_sel'][1].",
			".$_SESSION['int_current_storeroom'].",
			".$values['minimum_qty'].",
			".$values['sale_price'].",
			".$values['point_price'].",
			'".($values['use_batch_price']==1?'Y':'N')."'
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}

			$existQuery->Free();
			$parentRefresh=true;


	} else {

		$stUpdate="
                  UPDATE ".Monthalize('stock_storeroom_product')."
                  SET
			stock_minimum=".$values['minimum_qty'].",
			sale_price=".$values['sale_price'].",
			point_price=".$values['point_price'].",
			use_batch_price='".($values['use_batch_price']==1?'Y':'N')."'
		  WHERE product_id=".$values['product_id']."
                        AND storeroom_id=".$_SESSION['int_current_storeroom'];
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
				$msg = "There was an error while trying to save your information! ".$existQuery->GetErrorMessage();
			}
			$existQuery->Free();
			$parentRefresh=true;

	}

    	if (($msg=="") && ($confirm=="")) {
    		?><html><body><script language="JavaScript"><?
		if ($parentRefresh) {
      			echo ("window.opener.document.location=window.opener.document.location.href;");
    		}
		?>
    		window.close(); </script></body></html>
    		<?
    		exit;
    	}
    }

}
?>
