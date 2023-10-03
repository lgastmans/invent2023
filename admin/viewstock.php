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

//  ini_set("include_path", '/usr/share/pear/' . PATH_SEPARATOR . ini_get("include_path"));

  require_once 'HTML/QuickForm.php';
  $form =& new HTML_QuickForm('frmTest', 'get');
//
//  check permissions
//
  $bool_is_cur_month = $_SESSION["int_month_loaded"] == Date("m",time());
  $bool_can_modify_record = false;

  $bool_can_modify_record = (getModuleAccessLevel('Stock') > 1);

  if ($_SESSION["int_user_type"]>1) {	
	$bool_can_modify_record = true;
  } 
	
//
// if a get parameter is passed, then edit, otherwise insert
//
	if (empty($_GET["id"])) 
		$form->addElement('header', '', 'New Product'); 
	else 
		$form->addElement('header', '', 'View/Modify Product Master');
	
	$form->addElement('hidden', 'product_id', '');
	$form->addElement('text', 'product_code', 'Product Code:');
	$form->addElement('text', 'product_bar_code', 'Bar Code:');
        $form->addElement('text', 'product_abbreviation', 'Abbreviation:');
	$form->addElement('text', 'product_description', 'Product Name:', 'style=width:400px');
	$form->addElement('text', 'mrp', 'M.R.P.:');
	
	$radio[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
	$radio[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
	$form->addGroup($radio, 'is_available', 'Is Available:');
	
	$radio2[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
	$radio2[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
	$form->addGroup($radio2, 'is_perishable', 'Is Perishable:');
	
	$radio4[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
	$radio4[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
	$form->addGroup($radio4, 'is_av_product', 'Is AV Product:');
	
	$arr_purchase[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
	$arr_purchase[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
	$form->addGroup($arr_purchase, 'list_in_purchase', 'Include in Purchase List:');
	
	$arr_order[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
	$arr_order[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
	$form->addGroup($arr_order, 'list_in_order_sheet', 'Include in Order Sheet:');

	$arr_price_list[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
	$arr_price_list[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
	$form->addGroup($arr_price_list, 'list_in_price_list', 'Include in Price List:');
	
	$form->addElement('text', 'purchase_round', 'Purchase Round Up:');
	$form->addElement('text', 'minimum_qty', 'Minimum Quantity:');
	$form->addElement('text', 'margin_percentage', 'Margin %:');
	$form->addElement('text', 'product_weight', 'Weight (Kg):');

	//=======================================
	// measurement unit list for bulk_unit_id
	//---------------------------------------
	$qry = new Query("SELECT * FROM stock_measurement_unit ORDER BY measurement_unit");
	
	for ($i=0;$i<$qry->RowCount();$i++) {
		$arr_bulk_unit_list[$qry->FieldByName("measurement_unit_id")] =  $qry->FieldByName("measurement_unit");
		$qry->Next();
	}
	$bulk_unit_select =& $form->addElement('select', 'bulk_unit_id', 'Bulk Unit:', $arr_bulk_unit_list);

	//==============
	// category list
	//--------------
	$cat_list = buildCategoryList();
	for ($i=0;$i<count($cat_list);$i++) {
		$cat_list2[$cat_list[$i]["category_id"]] =  $cat_list[$i]["category_description"];
	}
	$cat_select =& $form->addElement('select', 'category_id', 'Category:', $cat_list2);

	//=========
	// tax list
	//---------
	$tax_list = buildTaxList();
	for ($i=0;$i<count($tax_list);$i++) {
		$tax_list2[$tax_list[$i]["tax_id"]] =  $tax_list[$i]["tax_description"];
	}
	$tax_select =& $form->addElement('select', 'tax_id', 'Tax:', $tax_list2);

	//======================
	// measurement unit list
	//----------------------
	$qry->First();
	for ($i=0;$i<$qry->RowCount();$i++) {
		$arr_mu_list[$qry->FieldByName("measurement_unit_id")] =  $qry->FieldByName("measurement_unit");
		$qry->Next();
	}
	$mu_select =& $form->addElement('select', 'measurement_unit_id', 'Unit:', $arr_mu_list);

	//====================
	// first supplier list
	//--------------------
	$qry = new Query("select * from stock_supplier order by supplier_name");
	$arr_sup_list[0]='(none)';
	$arr_sup_list2[0]='(none)';
	$arr_sup_list3[0]='(none)';
	for ($i=0;$i<$qry->RowCount();$i++) {
		$arr_sup_list[$qry->FieldByName("supplier_id")] =  $qry->FieldByName("supplier_name");
		$arr_sup_list2[$qry->FieldByName("supplier_id")] =  $qry->FieldByName("supplier_name");
		$arr_sup_list3[$qry->FieldByName("supplier_id")] =  $qry->FieldByName("supplier_name");
		$qry->Next();
	}

	$sup_select =& $form->addElement('select', 'supplier_id', 'Main Supplier:', $arr_sup_list);
	$sup_select2 =& $form->addElement('select', 'supplier2_id', '2nd Supplier:', $arr_sup_list2);
	$sup_select3 =& $form->addElement('select', 'supplier3_id', '3rd Supplier:', $arr_sup_list3);


//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules
  $form->addRule('product_code', 'Product code is a required field', 'required', null, 'client');
  $form->addRule('product_description', 'You must enter a name for the product', 'required',null,'client');
  $form->addRule('minimum_qty', 'Minimum quantity is not valid', 'numeric', null, 'client');
  $form->addRule('product_weight', 'Weight is not valid', 'numeric', null, 'client');



/**
 * Check for duplicate product code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_duplicates($element_name, $element_value) {
      //================================================================
      // if editting the product, check for changed, and duplicate, code
      //----------------------------------------------------------------
      if (!empty($_GET["product_id"])) {
	    $existQuery = new Query("
		  SELECT product_description
		  FROM stock_product
		  WHERE product_code = '$element_value'
			AND product_id <> ".$_GET["product_id"]."
			AND deleted = 'N'");
	    if ($existQuery->RowCount() > 0) {
		  return false;
	    }
      //================================================================
      // if new product, check for duplicate code
      //----------------------------------------------------------------
      } else {
    	    $existQuery = new Query("
		  SELECT product_description
		  FROM stock_product
		  WHERE product_code='$element_value'
			AND (deleted = 'N')");
	    if ($existQuery->RowCount() > 0) {
		  return false;
	    }
      }
      return true;
}



  $form->registerRule('check_duplicates','function','fn_check_duplicates');$form->addRule('product_code','This product code is already used!','check_duplicates'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
  if (IsSet($_GET['id'])) {
  	$id = $_GET['id'];
	 $sql = new Query("
			SELECT
				*
			FROM
				stock_product
			WHERE
				product_id=$id.");
	$form->setDefaults(array(
		'product_id'  => $sql->FieldByName("product_id"),
		'product_code'  => $sql->FieldByName("product_code"),
		'product_bar_code'  => $sql->FieldByName("product_bar_code"),
		'product_description'  => stripslashes( $sql->FieldByName("product_description") ),
		'mrp' => $sql->FieldByName('mrp'),
		'product_abbreviation' => stripslashes($sql->FieldByName('product_abbreviation')),
		'is_available'  => $sql->FieldByName("is_available"),
		'is_av_product'  => $sql->FieldByName("is_av_product"),
		'is_perishable'  => $sql->FieldByName("is_perishable"),
		'purchase_round' => $sql->FieldByName('purchase_round'),
		'list_in_purchase' => $sql->FieldByName("list_in_purchase"),
		'list_in_order_sheet' => $sql->FieldByName('list_in_order_sheet'),
		'list_in_price_list' => $sql->FieldByName('list_in_price_list'),
		'is_consolidated'  => $sql->FieldByName("is_minimum_consolidated"),
		'category_id' => $sql->FieldByName("category_id"),
		'product_weight' => $sql->FieldByName("product_weight"),
		'minimum_qty'  => ($sql->FieldByName("minimum_qty")+0),
		'measurement_unit_id'  => $sql->FieldByName("measurement_unit_id"),
		'bulk_unit_id' => $sql->FieldByName('bulk_unit_id')
	));

  	$tax_select->setValue($sql->FieldByName('tax_id'));
    $cat_select->setValue($sql->FieldByName("category_id")); 
    $mu_select->setValue($sql->FieldByName("measurement_unit_id"));
	$bulk_unit_select->setValue($sql->FieldByName('bulk_unit_id'));
    $sup_select->setValue($sql->FieldByName("supplier_id")); 
    $sup_select2->setValue($sql->FieldByName("supplier2_id")); 
    $sup_select3->setValue($sql->FieldByName("supplier3_id")); 

  }  else {
		$sql_defs = new Query("
      SELECT *
      FROM stock_storeroom
      WHERE storeroom_id=".$_SESSION['int_current_storeroom']);

	$form->setDefaults(array(
		'mrp' => '0',
		'is_available'  => 'Y',
		'is_perishable'  => 'N',
		'margin_percentage'  => '0',
		'is_av_product'  => 'N',
		'list_in_purchase' => 'Y',
		'list_in_order_sheet' => 'N',
		'list_in_price_list' => 'Y',
		'purchase_round' => '0',
		'minimum_qty'=>'0',
		'product_weight'=>'0'
	));

	$tax_select->setValue($sql_defs->FieldByName('default_tax_id'));
	$sup_select->setValue($sql_defs->FieldByName('default_supplier_id'));
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
//	$values["product_description"]=strtoupper($values["product_description"]);
	$parentRefresh = false;

    	if ($values['product_id']<=0) {
	    	$stUpdate = "
		INSERT INTO stock_product (
			product_code,
			product_bar_code,
			product_description,
			mrp,
			product_abbreviation,
			category_id,
			is_available, 
			is_av_product,
			is_perishable,
			list_in_purchase,
			list_in_order_sheet,
			list_in_price_list,
			purchase_round,
			tax_id,
			minimum_qty,
			measurement_unit_id,
			supplier_id,
			supplier2_id,
			supplier3_id,
			product_weight,
			bulk_unit_id,
			margin_percent) 
			VALUES (
			'".$values['product_code']."',
			'".$values['product_bar_code']."',
			'".addquotes($values['product_description'])."',
			".$values['mrp'].",
			'".addslashes($values['product_abbreviation'])."',
			".$values['category_id'].",
			'". ($values['is_available'])."',
			'". ($values['is_av_product'])."',
			'". ($values['is_perishable'])."',
			'". ($values['list_in_purchase'])."',
			'". ($values['list_in_order_sheet'])."',
			'". ($values['list_in_price_list'])."',
			".intval($values['purchase_round']).",
			".$values['tax_id'].",
			".$values['minimum_qty'].",
			".$values['measurement_unit_id'].",
			".$values['supplier_id'].",
			".$values['supplier2_id'].",
			".$values['supplier3_id'].",
			".$values['product_weight'].",
			".$values['bulk_unit_id'].",
			".$values['margin_percentage']."
			)
			";
//			die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;
//                        echo $stUpdate;
			header("Location: viewstock.php");
			die();
	} else {
		
		$stUpdate="UPDATE stock_product SET
			product_code='".$values['product_code']."',
			product_bar_code='".$values['product_bar_code']."',
			product_description='".addquotes($values['product_description'])."',
			mrp=".$values['mrp'].",
			product_abbreviation='".addslashes($values['product_abbreviation'])."',
			category_id=".$values['category_id'].",
			is_available='".$values['is_available']."',
			is_av_product='".$values['is_av_product']."',
			is_perishable='".$values['is_perishable']."',
			list_in_purchase='".$values['list_in_purchase']."',
			list_in_order_sheet='".$values['list_in_order_sheet']."',
			list_in_price_list='".$values['list_in_price_list']."',
			purchase_round=".intval($values['purchase_round']).",
			tax_id=".$values['tax_id'].",
			minimum_qty=".$values['minimum_qty'].",
			measurement_unit_id=".$values['measurement_unit_id'].",
			supplier_id=".$values['supplier_id'].",
			supplier2_id=".$values['supplier2_id'].",
			supplier3_id=".$values['supplier3_id'].",
			margin_percent=".intval($values['margin_percentage']).",
			product_weight=".$values['product_weight'].",
			bulk_unit_id=".$values['bulk_unit_id']."
			WHERE product_id=".$values['product_id'];
//			die($stUpdate);
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