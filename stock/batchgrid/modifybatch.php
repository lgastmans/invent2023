<?
/**
* 
* @version 	$Id: modifybatch.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product Edit
* @name  	viewstock.php
* 
* This file uses the pear QuickForm components to display a dialog
* which lets the user create transfers
* 
* Get Parameters: 
* $_GET[id]		The batch_id to load if you want to edit a product.  Otherwise
*			the insert product page will be shown
* Variables:
* $form 			The HTML QuickForm instance
* $bool_is_cur_month		True when active month is the current calendar month
* $bool_can_modify_record	True if user showing this page has modify rights
*/

      error_reporting(E_ERROR|E_WARNING);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require('QuickForm.php');

	$form =& new HTML_QuickForm('frmTest', 'get');

//
//  check permissions
//
      $bool_is_cur_month = $_SESSION["int_month_loaded"]==Date("m",time());
      $bool_can_modify_record = false;

      $qry_user_settings = new Query("
	    SELECT can_edit_batch
	    FROM user
	    WHERE user_id = ".$_SESSION['int_user_id']
      );
      if ($qry_user_settings->RowCount() > 0)
	    if ($qry_user_settings->FieldByName('can_edit_batch') == 'N')
		  die('You do not have permission to edit batch details');

      // list of tax categories
      $result_tax = new Query("
	    SELECT tax_id, tax_description
	    FROM ".Monthalize('stock_tax')."
	    ORDER BY tax_description"
      );
      for ($i=0; $i<$result_tax->RowCount(); $i++) {
	    $arr_tax[$result_tax->FieldByName('tax_id')] = $result_tax->FieldByName('tax_description');
	    $result_tax->Next();
      }


      if (IsSet($_GET['id'])) $batch_id = $_GET['id']; 
      if (IsSet($_GET['batch_id'])) $batch_id = $_GET['batch_id'];   

      $qry_batch = new Query("
	    SELECT *, sb.is_active AS is_active, sb.supplier_id AS current_supplier_id
	    FROM ".Yearalize('stock_batch')." sb
	    INNER JOIN
		  stock_product sp
	    ON
		  sp.product_id=sb.product_id
	    LEFT JOIN
		  ".Monthalize('stock_tax')." st
	    ON
		  st.tax_id=sb.tax_id
	    WHERE
		  batch_id=".$batch_id."
                  and storeroom_id=".$_SESSION['int_current_storeroom']);

      // get the list of suppliers for the given product
      $result_supplier = new Query("
              SELECT *
              FROM stock_supplier
	      ORDER BY supplier_name
      ");
      $arr_suppliers = array();
      for ($i=0;$i<$result_supplier->RowCount();$i++) {
            $arr_suppliers[$result_supplier->FieldByName('supplier_id')] = $result_supplier->FieldByName('supplier_name');
	    $result_supplier->Next();
      }

      $radio[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
      $radio[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');

      $form->addElement('header', '', 'Batch Details for : '.$qry_batch->FieldByName('product_description')); 
      $form->addElement('hidden', 'batch_id', $batch_id);
//      $form->addElement('static', 'product_name', 'Product:'.); 
      $form->addElement('text', 'batch_code', 'Batch Code:'); 
      $form->addElement('text', 'buying_price', 'Buying Price:'); 
      $form->addElement('text', 'selling_price', 'Selling Price:');
      $tax_category = $form->addElement('select', 'tax_id', 'Tax:', $arr_tax, array('style' => 'width: 150px;'));
      $batch_supplier = $form->addElement('select', 'supplier_id', 'Supplier:', $arr_suppliers, array('style' => 'width: 200px;'));
      $form->addGroup($radio, 'is_active', 'Active:');
      

  if ($qry_batch->b_error) {
	die('Error getting batch details.');
  }

//====================
  $form->applyFilter('__ALL__', 'trim');

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
      if (!empty($_GET['id'])) {
	    $form->setDefaults(array(
		  'batch_id'  => $qry_batch->FieldByName("batch_id"),
		  'batch_code'  => $qry_batch->FieldByName("batch_code"),
		  'buying_price'  => $qry_batch->FieldByName("buying_price"),
		  'selling_price'  => $qry_batch->FieldByName("selling_price"),
		  'is_active' => $qry_batch->FieldByName("is_active")
	    ));
	    $tax_category->setValue($qry_batch->FieldByName('tax_id'));
            $batch_supplier->setValue($qry_batch->FieldByName('current_supplier_id'));
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

    	if ($values['batch_id']>0) {
		
                  $can_save = true;
                  
                  if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
                          $str_message = '';
                  }
                  else {
                          $msg = 'Cannot update batches in previous months. \\n Select the current month/year and continue.';
                          $can_save = false;
                  }
                  
                  if ($can_save) {
                        $stUpdate = "
                              UPDATE ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
                              SET
                                    sb.batch_code=\"".$values['batch_code']."\",
                                    sb.buying_price=".$values['buying_price'].",
                                    sb.selling_price=".$values['selling_price'].",
                                    sb.tax_id=".$values['tax_id'].",
                                    sb.supplier_id=".$values['supplier_id'].",
                                    sb.is_active = '".$values['is_active']."',
                                    ssb.is_active = '".$values['is_active']."'
                              WHERE sb.batch_id=".$values['batch_id']."
                                    AND ssb.batch_id = sb.batch_id
                                    AND sb.storeroom_id = ".$_SESSION['int_current_storeroom'];
                              $existQuery = new Query($stUpdate);
                              if ($existQuery->b_error == true) {
                                      $msg = "There was an error while trying to save your information! ".$existQuery->GetErrorMessage();
		  }
		  $existQuery->Free();
		  $parentRefresh=true;
                  
                  }
	    
	}
	
    	if (($msg=="") && ($confirm=="")) {
    		?><html><body><script language="JavaScript"><? 
		if ($parentRefresh) {
//      			echo ("window.opener.document.location=window.opener.document.location.href;");
      			echo ("window.opener.updateData();");
    		}
		?>
    		window.close(); </script></body></html>
    		<?
    		exit;
    	}
	else {
            echo "<script language='javascript'>\n";
            echo "alert('".$msg."');\n";
            echo "window.close();\n";
            echo "</script>\n";
        }
    }
   
}  
?>
