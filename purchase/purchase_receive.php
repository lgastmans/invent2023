<?
	require_once("../include/config.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once 'HTML/QuickForm.php';

	require_once("../include/common_funcs.js.php");
?>
<script language="javaScript">
	function setBatchSplit(intID) {
		alert('batch_id '+intID);
		document.frmReceive.split_action.value = 'batch_split';
		document.frmReceive.batch_id.value = intID;
		document.frmReceive.submit();
	}

	function removeBatchSplit(aBatchID, atIndex) {
		alert('batch_id '+aBatchID+', index '+atIndex);
		document.frmReceive.split_action.value = 'batch_remove';
		document.frmReceive.batch_id.value = aBatchID;
		document.frmReceive.del_index.value = atIndex;
		document.frmReceive.submit();
	}

	function getSellingPrice(intMargin, intRow) {
		var fnameSellingPrice 	= 'items' + intRow + '[selling_price_' + intRow + ']';
		var fnameBuyingPrice 	= 'items' + intRow + '[buying_price_' + intRow + ']';
		var fnameBonus			= 'items' + intRow + '[bonus_' + intRow + ']';
		var fnameReceived		= 'items' + intRow + '[received_' + intRow + ']';

		var fltSellingPrice	= document.getElementsByName(fnameSellingPrice);
		var fltBuyingPrice	= document.getElementsByName(fnameBuyingPrice);
		var fltBonus		= document.getElementsByName(fnameBonus);
		var fltReceived		= document.getElementsByName(fnameReceived);

		if (parseFloat(fltBonus[0]) == 0)
			fltPrice = RoundUp((1 + intMargin/100) * parseFloat(fltBuyingPrice[0].value));
		else
			fltPrice = RoundUp((parseFloat(fltReceived[0].value) / (parseFloat(fltReceived[0].value) + parseFloat(fltBonus[0].value))) * (1 + intMargin/100) * parseFloat(fltBuyingPrice[0].value));

		fltSellingPrice[0].value = fltPrice;
	}

</script>

<?
	error_reporting(E_ERROR);

	function fn_check_expiry($element_name, $element_value) {
		$arr_curdate = explode("/", date('d/m/Y',time()));
		$str_date = $element_value['Y'].$element_value['M'].$element_value['d'];
		$str_curdate = $arr_curdate[2].$arr_curdate[1].$arr_curdate[0];
		if ($str_date < $str_curdate)
			return false;

		return true;
	}

	// returns the date contained in the returned array "$arr_date" from the quickForm
	function get_date($arr_date) {
		$str_date = $arr_date['Y']."-".$arr_date['M']."-".$arr_date['d']." 00:00:00";
		return $str_date;
	}

	// returns the expiry date based on the date of manufacture (here $date_start) and
	// the shelf life of the product (here $int_increment)
	function get_expiry_date($date_start, $int_increment) {
		$str_date = mktime(0,0,0,$date_start['M'],$date_start['d'],$date_start['Y']);

		$str_increment = "+".$int_increment." days";

		return date("Y-m-d H:i:s", strtotime($str_increment, $str_date));
	}

	function loadArray() {
		// result set of items to receive, listing only those where the is_received
		// flag is still false
		$qry_receive = "SELECT *, pi.supplier_id AS cur_supplier
			FROM ".Yearalize('purchase_items')." pi,
				stock_product sp
			WHERE (purchase_order_id = ".$_GET["id"].") AND
				(pi.product_id = sp.product_id) AND
				(pi.is_received = 'N')";
				
		$result_receive = new Query($qry_receive);

		// load the result set in an array
		for ($i = 0; $i < $result_receive->RowCount(); $i++) {
			$_SESSION["arr_split_batches"][$i] = array(
				'code'			=> $result_receive->FieldByName('product_code'),
				'description'		=> $result_receive->FieldByName('product_description'),
				'batch_id'		=> $i,
				'product_id'		=> $result_receive->FieldByName('product_id'),
				'batch'			=> "",
				'ordered'		=> $result_receive->FieldByName('quantity_ordered'),
				'received'		=> $result_receive->FieldByName('quantity_ordered'),
				'bonus'			=> 0,
				'buying_price'		=> $result_receive->FieldByName('buying_price'),
				'selling_price'		=> $result_receive->FieldByName('selling_price'),
				'tax_category'		=> $result_receive->FieldByName('tax_id'),
				'date_manufacture'	=> time(),
				'shelf_life'		=> $result_receive->FieldByName('shelf_life'),
				'active'		=> 'Y',
				'supplier_id'		=> $result_receive->FieldByName('cur_supplier'),
				'is_received'		=> 'Y',
				'split_batch'		=> $i,
				'num_split_batches'	=> 0,
				'is_split_batch'	=> 'N',
				'margin_percent'	=> $result_receive->FieldByName('margin_percent')
			);
			$result_receive->Next();
		}
	}

	function save_to_array() {
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			if (!empty($_GET["items".$i])) {
				$group_values = $_GET["items".$i];
				$_SESSION["arr_split_batches"][$i]['batch'] = $group_values["product_id_".$i];
				$_SESSION["arr_split_batches"][$i]['received'] = $group_values["received_".$i];
				$_SESSION["arr_split_batches"][$i]['bonus'] = $group_values["bonus_".$i];
				$_SESSION["arr_split_batches"][$i]['buying_price'] = $group_values["buying_price_".$i];
				$_SESSION["arr_split_batches"][$i]['selling_price'] = $group_values["selling_price_".$i];
				$_SESSION["arr_split_batches"][$i]['tax_category'] = $group_values["tax_category_".$i];
				$_SESSION["arr_split_batches"][$i]['date_manufacture'] = $group_values["date_manufacture_".$i];
				if ($group_values["active_".$i] == 1)
					$_SESSION["arr_split_batches"][$i]['active'] = 'Y';
				else
					$_SESSION["arr_split_batches"][$i]['active'] = 'N';
				$_SESSION["arr_split_batches"][$i]['supplier_id'] = $group_values["supplier_".$i];
				if ($group_values["is_received_".$i] == 1)
					$_SESSION["arr_split_batches"][$i]['is_received'] = 'Y';
				else
					$_SESSION["arr_split_batches"][$i]['is_received'] = 'N';
			}
		}
	}

	function removeBatch($aBatchID, $anIndexID) {
		// locate the array row for the given intID
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			if ($_SESSION["arr_split_batches"][$i]['batch_id'] == $aBatchID) {
				// get the number of split batches for the given batch
				$tmp_num = $_SESSION["arr_split_batches"][$i]['num_split_batches'];

				// remove the row from the array
				$_SESSION["arr_split_batches"] = array_delete($_SESSION["arr_split_batches"], $anIndexID);

				// decrement the 'num_split_batches' in the original entry
				$tmp_num = $tmp_num - 1;
				$_SESSION["arr_split_batches"][$i]['num_split_batches'] = $tmp_num;

				break;
			}
		}
	}

	function splitBatch($intID) {
		// locate the array row for the given intID
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			if ($_SESSION["arr_split_batches"][$i]['batch_id'] == $intID) {

				// get the index of the last split batch to copy the data from
				$int_last_pos = $i + $_SESSION["arr_split_batches"][$i]['num_split_batches'];
				// get the number of split batches for the given batch
				$tmp_num = $_SESSION["arr_split_batches"][$i]['num_split_batches'];

				// fill new elements with the original entry
				$arr_new = array(
					'code'			=> $_SESSION["arr_split_batches"][$int_last_pos]['code'],
					'description'		=> $_SESSION["arr_split_batches"][$int_last_pos]['description'],
					'batch_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['batch_id'],
					'product_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['product_id'],
					'batch'			=> $_SESSION["arr_split_batches"][$int_last_pos]['batch'],
					'ordered'		=> $_SESSION["arr_split_batches"][$int_last_pos]['ordered'],
					'received'		=> $_SESSION["arr_split_batches"][$int_last_pos]['ordered'] - $_SESSION["arr_split_batches"][$int_last_pos]['received'],
					'bonus'			=> $_SESSION["arr_split_batches"][$int_last_pos]['bonus'],
					'buying_price'		=> $_SESSION["arr_split_batches"][$int_last_pos]['buying_price'],
					'selling_price'		=> $_SESSION["arr_split_batches"][$int_last_pos]['selling_price'],
					'tax_category'		=> $_SESSION["arr_split_batches"][$int_last_pos]['tax_category'],
					'date_manufacture'	=> $_SESSION["arr_split_batches"][$int_last_pos]['date_manufacture'],
					'shelf_life'		=> $_SESSION["arr_split_batches"][$int_last_pos]['shelf_life'],
					'active'		=> $_SESSION["arr_split_batches"][$int_last_pos]['active'],
					'supplier_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['supplier_id'],
					'is_received'		=> $_SESSION["arr_split_batches"][$int_last_pos]['is_received'],
					'split_batch'		=> $_SESSION["arr_split_batches"][$int_last_pos]['split_batch'],
					'num_split_batches'	=> $_SESSION["arr_split_batches"][$int_last_pos]['num_split_batches'],
					'is_split_batch'	=> 'Y',
					'margin_percent'	=> $_SESSION["arr_split_batches"][$int_last_pos]['margin_percent']
				);

				// insert a new entry at the end of the current list of split batches
				$_SESSION["arr_split_batches"] = array_insert($_SESSION["arr_split_batches"], $i+$tmp_num+1, array($arr_new));

				// increment the 'num_split_batches' in the original entry
				$tmp_num = $tmp_num + 1;
				$_SESSION["arr_split_batches"][$i]['num_split_batches'] = $tmp_num;

				break;
			}
		}
	}

?>
<html>
<body bgcolor="#DADADA">
<?

$form =& new HTML_QuickForm('frmReceive', 'GET');


$groupLabel = <<<_HTML
<table border="0" cellpadding=5 cellspacing="0">
	<tr valign="top">
		<td align="right" width="250">
			<!-- BEGIN required --><span>*</span><!-- END required -->{label}
		</td>
		<td align="left">
			<!-- BEGIN error --><span>{error}</span><br/><!-- END error -->{element}
		</td>
	</tr>
</table>
_HTML;


// column header to display at the top of the form
$html_header = "<table border=\"0\" cellpadding=\"5\" cellspacing=\"0\" bgcolor=\"#D0D0D0\">
<tr style=\"font-family:Arial,Verdana,sans-serif;font-size:12px;font-weight:bold;\">
	<td width=\"250\" align=\"right\">Code - Description - Ordered</td>
	<td width=\"70\">Batch</td>
	<td width=\"75\">Received</td>
	<td width=\"50\">Bonus</td>
	<td width=\"50\">B.Price</td>
	<td width=\"53\">S.Price</td>
	<td width=\"97\">Tax</td>
	<td width=\"50\">S.P./Tax</td>
	<td width=\"180\">Date of Manufacture</td>
	<td width=\"50\">Supplier</td>
	<td width=\"40\">Active</td>
	<td width=\"40\">Receive</td>
	<td width=\"40\"></td>
</tr></table>";

	$renderer =& $form->defaultRenderer();

	// get "purchase order number" for the current purchase order
	$qry_supplierid = "SELECT purchase_order_ref, supplier_id, assigned_to_user_id 
		FROM ".Yearalize('purchase_order')." 
		WHERE (purchase_order_id=".$_GET["id"].")";
	$result_supplierid = new Query($qry_supplierid);
	$str_purchase_order_number = $result_supplierid->FieldByName('purchase_order_ref');

	$form->addElement('hidden', 'id', $_GET["id"]);
	$form->addElement('hidden', 'int_assigned_to_id', $result_supplierid->FieldByName('assigned_to_user_id'));
	$form->addElement('hidden', 'str_po_number', $str_purchase_order_number);
	if (empty($_GET["action_split"])) {
		$form->addElement('hidden', 'split_action', 'none');
		$form->addElement('hidden', 'batch_id', 0);
		$form->addElement('hidden', 'del_index',-1);
	}
	$form->addElement('static', 'form_header', $html_header);

	// not used now as the date of manufacture is entered instead of the date of expiry
	// the date of expiry is calculated based on the date of manufacture
	$form->registerRule('check_expiry','function','fn_check_expiry');

	// initialize the array that holds the rows and the row counter
	if (empty($_GET["exec_action"]) && empty($_GET["split_action"])) {
		echo "here";
		$_SESSION["arr_split_batches"] = array();
		loadArray();
	}

	// check whether a batch has been selected for splitting
	if (IsSet($_GET["split_action"])) {
		if ($_GET["split_action"] == "batch_split") {
			// save all data entered so far
			$form->process('save_to_array', false);

			//add to array
			splitBatch($_GET["batch_id"]);
		}
		// or for removing
		else
		if ($_GET["split_action"] == "batch_remove") {
			// save all data entered so far
			$form->process('save_to_array', false);

			// remove from array
			removeBatch($_GET["batch_id"], $_GET["del_index"]);
		}
	}

	// list of tax categories
	$result_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);
	for ($i=0; $i<$result_tax->RowCount(); $i++) {
		$arr_tax[$result_tax->FieldByName('tax_id')] = $result_tax->FieldByName('tax_description');
		$result_tax->Next();
	}

  $str_js = "";
	// create a quickForm group for each purchase order item to receive
	for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {

		// list of suppliers for the given item
		$result_supplier = new Query("
			SELECT sp.supplier2_id, sp.supplier3_id, ss.supplier_id AS id_1, ss.supplier_name AS supplier_name1,
				ss2.supplier_id AS id_2, ss2.supplier_name AS supplier_name2,
				ss3.supplier_id AS id_3, ss3.supplier_name AS supplier_name3
			FROM
				stock_product sp
			LEFT JOIN stock_supplier ss ON (sp.supplier_id = ss.supplier_id)
			LEFT JOIN stock_supplier ss2 ON (sp.supplier2_id = ss2.supplier_id) AND (sp.product_id=".$_SESSION["arr_split_batches"][$i]['product_id'].")
			LEFT JOIN stock_supplier ss3 ON (sp.supplier3_id = ss3.supplier_id) AND (sp.product_id=".$_SESSION["arr_split_batches"][$i]['product_id'].")
			WHERE (sp.product_id=".$_SESSION["arr_split_batches"][$i]['product_id'].")
		");
		if ($result_supplier->error == true) {
			$result_supplier->Query("SELECT * FROM stock_supplier");
			if ($result_supplier->RowCount() > 0) {
				unset($arr_supplier);
				$arr_supplier[$result_supplier->FieldByName('supplier_id')] = $result_supplier->FieldByName('supplier_name');
			}
		}
		else {		
			// load the list of suppliers in an array
			unset($arr_supplier);
			$arr_supplier[$result_supplier->FieldByName('id_1')] = $result_supplier->FieldByName('supplier_name1');
			if (!is_null($result_supplier->FieldByName('supplier_name2')))
				$arr_supplier[$result_supplier->FieldByName('id_2')] = $result_supplier->FieldByName('supplier_name2');
			if (!is_null($result_supplier->FieldByName('supplier_name3')))
				$arr_supplier[$result_supplier->FieldByName('id_3')] = $result_supplier->FieldByName('supplier_name3');
		}

		// per item to receive, create a set of grouped elements
		// where each fieldname is unique
		$str_product_id		= "product_id_".$i;
		$str_batch 		= "batch_".$i;
		$str_ordered		= "ordered_".$i;
		$str_received		= "received_".$i;
		$str_bonus		= "bonus_".$i;
		$str_buying_price 	= "buying_price_".$i;
		$str_selling_price	= "selling_price_".$i;
//		$str_tax_id				= "tax_id_".$i;
		$str_tax_category	= "tax_category_".$i;
		$str_date_manufacture	= "date_manufacture_".$i;
		$str_shelf_life		= "shelf_life_".$i;
		$str_active		= "active_".$i;
		$str_supplier		= "supplier_".$i;
		$str_is_received	= "is_received_".$i;
		$str_split_batch	= "split_batch_".$i;
		$product_id		= &HTML_QuickForm::createElement('hidden', $str_product_id);
		$batch			= &HTML_QuickForm::createElement('text', $str_batch, 'code', array('style' => 'width: 75px;'));
		$ordered		= &HTML_QuickForm::createElement('hidden', $str_ordered);
		$received 		= &HTML_QuickForm::createElement('text', $str_received, 'received', array('style' => 'width: 75px;', 'onblur' => "getSellingPrice(".$_SESSION["arr_split_batches"][$i]['margin_percent'].", ".$i.")"));
		$bonus			= &HTML_QuickForm::createElement('text', $str_bonus, null, array('style' => 'width: 50px;', 'onblur' => "getSellingPrice(".$_SESSION["arr_split_batches"][$i]['margin_percent'].", ".$i.")"));
		$buying_price		= &HTML_QuickForm::createElement('text', $str_buying_price, null, array('style' => 'width: 55px;', 'onblur' => "getSellingPrice(".$_SESSION["arr_split_batches"][$i]['margin_percent'].", ".$i.")"));
		$selling_price		= &HTML_QuickForm::createElement('text', $str_selling_price, null, array('style' => 'width: 55px;'));
//		$tax_id			= &HTML_QuickForm::createElement('hidden', $str_tax_id);
		$tax_category		= &HTML_QuickForm::createElement('select', $str_tax_category, null, $arr_tax, array('style' => 'width: 100px;'));
		$str_tax_total = $_SESSION["arr_split_batches"][$i]['selling_price'];
		if ($_SESSION["arr_split_batches"][$i]['bonus'] == 0) {
			$str_tax_total = floatval((1 + $_SESSION["arr_split_batches"][$i]['margin_percent']/100) * $_SESSION["arr_split_batches"][$i]['selling_price']);
		} else
			$str_tax_total = floatval($_SESSION["arr_split_batches"][$i]['received'] / ($_SESSION["arr_split_batches"][$i]['received'] + $_SESSION["arr_split_batches"][$i]['received']) * (1 + $_SESSION["arr_split_batches"][$i]['margin_percent']/100) * $_SESSION["arr_split_batches"][$i]['buying_price']);

    $str_tax_total = $str_tax_total+calculateTax($str_tax_total, $_SESSION["arr_split_batches"][$i]['tax_category']);
    
		$tax_total			= &HTML_QuickForm::createElement('text', $str_tax_total);
		$manufacture		= &HTML_QuickForm::createElement('date', $str_date_manufacture, null, array('format'=>'d / M / Y'));
		$shelf_life		= &HTML_QuickForm::createElement('hidden', $str_shelf_life);
		$supplier		= &HTML_QuickForm::createElement('select', $str_supplier, null, $arr_supplier, array('style' => 'width: 100px;'));
		$active			= &HTML_QuickForm::createElement('checkbox', $str_active, null, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', null, array('style' => 'width: 100px;'));
		$is_received		= &HTML_QuickForm::createElement('checkbox', $str_is_received, null, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
		if ($_SESSION["arr_split_batches"][$i]['is_split_batch'] == 'N')
			$split_batch	= &HTML_QuickForm::createElement('button', 'split_action', 'split', array('onclick' => "setBatchSplit(".$_SESSION["arr_split_batches"][$i]['batch_id'].")"));
		else
			$split_batch	= &HTML_QuickForm::createElement('button', 'split_action', 'remove', array('onclick' => "removeBatchSplit(".$_SESSION["arr_split_batches"][$i]['batch_id'].", ".$i.")"));

		// create the unique group
		$str_group_name = "items".$i;
		if ($_SESSION["arr_split_batches"][$i]['is_split_batch'] == 'N')
			$form->addGroup(array($product_id, $batch, $ordered, $received, $bonus, $buying_price, $selling_price, $tax_category, $tax_total,$manufacture, $shelf_life, $supplier, $active, $is_received, $split_batch),
					$str_group_name,
					$_SESSION["arr_split_batches"][$i]['code']."  -  ".$_SESSION["arr_split_batches"][$i]['description']."  -  <b>".$_SESSION["arr_split_batches"][$i]['ordered']."</b>", '&nbsp;&nbsp;');
		else
			$form->addGroup(array($product_id, $batch, $ordered, $received, $bonus, $buying_price, $selling_price, $tax_category,  $tax_total,$manufacture, $shelf_life, $supplier, $active, $is_received, $split_batch),
					$str_group_name,
					null, '&nbsp;&nbsp;');

		$renderer->setElementTemplate($groupLabel, $str_group_name);
		
		$str_js .= "getSellingPrice(".$_SESSION["arr_split_batches"][$i]['margin_percent'].", ".$i.");\n";
		// Rule for group's elements
		$form->addGroupRule($str_group_name, array(
			$str_received => array(
				array('invalid received value', 'required', null, 'client'),
				array('received is numbers only', 'numeric', null, 'client')
			),
			$str_buying_price => array(
				array('Buying price is numbers only', 'numeric', null, 'client'),
				array('Buying price is required', 'required', null, 'client'),
				array('price should be greater than zero', 'nonzero', null, 'client')
			),
			$str_selling_price => array(
				array('price is numbers only', 'numeric', null, 'client'),
				array('price is required', 'required', null, 'client'),
				array('price should be greater than zero', 'nonzero', null, 'client')
			)
		));

		if (IsSet($_GET["exec_action"])) {
			if ($_GET["exec_action"] == "save") {
				save_to_array();
			}
		}
		else {
			// set default values for the items
			$product_id	->setValue($_SESSION["arr_split_batches"][$i]['product_id']);
			$ordered	->setValue($_SESSION["arr_split_batches"][$i]['ordered']);
			$received	->setValue($_SESSION["arr_split_batches"][$i]['received']);
			$bonus		->setValue($_SESSION["arr_split_batches"][$i]['bonus']);
			$buying_price	->setValue($_SESSION["arr_split_batches"][$i]['buying_price']);
			$selling_price	->setValue($_SESSION["arr_split_batches"][$i]['selling_price']);
			$tax_category	->setValue($_SESSION["arr_split_batches"][$i]['tax_category']);
			$tax_total->setValue($str_tax_total);
			$manufacture	->setValue($_SESSION["arr_split_batches"][$i]['date_manufacture']);
			$shelf_life	->setValue($_SESSION["arr_split_batches"][$i]['shelf_life']);
			$supplier	->setValue($_SESSION["arr_split_batches"][$i]['supplier_id']);
			if ($_SESSION["arr_split_batches"][$i]['active'] == 'Y')
				$active	->setValue(true);
			else
				$active	->setValue(false);
			if ($_SESSION["arr_split_batches"][$i]['is_received'] == 'Y')
				$is_received->setValue(true);
			else
				$is_received->setValue(false);
		}
	}

	$form->addElement('static', '', "<hr align=\"left\" height=\"5\" width=\"1165\">");

	$buttons[] = &HTML_QuickForm::createElement('submit', 'exec_action', 'save'); //, array('onClick' => "javascript:submitForm()"));
	$buttons[] = &HTML_QuickForm::createElement('button', 'exec_action', 'cancel', array('onClick' => "window.close();"));
	$form->addGroup($buttons, null, null, '&nbsp;', false);

	if (IsSet($_GET["exec_action"])) {
		if ($_GET["exec_action"] == "save") {
			if ($form->validate()) {
			// Form is validated, then processes the data
				$form->freeze();
				$form->process('saveForm', false);
			}
		}
	}

	$form->display();

?>
	<script language="javascript">
		document.frmReceive.split_action.value = 'none';
		document.frmReceive.batch_id.value = 0;
		document.frmReceive.del_index.value = -1;
		<? echo $str_js; ?>
	</script>

<?
	// returns the number of items selected to process based on the "is_received" field
	function getSelectedRows() {
		$int_result=0;
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			$item_values =  $_GET["items".$i];
			if (!empty($item_values["is_received_".$i]))
				$int_result = $int_result + 1;
		}
		return $int_result;
	}


	// this function to test the result array
	function saveForm2($values) {
//			for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
//				$group_values =  $_GET["items".$i];
//				show_arr($group_values);
//			}
	}


	function saveForm($values) {
		// get the number of rows that have been selected
		$int_num_rows_selected = getSelectedRows();
		if ($int_num_rows_selected > 0) {
			// save all the selected rows...
			$qry_update = new Query("BEGIN");
			$bool_success = true;
			$str_message = "";

			for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {

				$group_values =  $_GET["items".$i];

				if (!empty($group_values["is_received_".$i])) {

				// add an entry in stock_batch
					if (!empty($group_values["batch_".$i])) {
						$qry_update->Query("INSERT INTO ".Yearalize('stock_batch')."
								(batch_code,
								buying_price,
								selling_price,
								date_created,
								opening_balance,
								date_manufacture,
								date_expiry,
								is_active,
								status,
								user_id,
								buyer_id,
								supplier_id,
								product_id,
								storeroom_id,
								tax_id)
							VALUES('".
								$group_values["batch_".$i]."', ".
								$group_values["buying_price_".$i].", ".
								$group_values["selling_price_".$i].", '".
								date("Y-m-d H:i:s")."', ".
								$group_values["received_".$i].", '".
								get_date($group_values["date_manufacture_".$i])."', '".
								get_expiry_date($group_values["date_manufacture_".$i], $group_values["shelf_life_".$i])."', ".
								"'Y', '".
								STATUS_COMPLETED."', ".
								$_SESSION["int_user_id"].", ".
								$_GET["int_assigned_to_id"].", ".
								$group_values["supplier_".$i].", ".
								$group_values["product_id_".$i].", ".
								$_SESSION["int_current_storeroom"].", ".
								$group_values["tax_category_".$i]."
								)");
					}
					else {
						// don't save the batch code, and set it later to the autoincremental value of batch_id
						$qry_update->Query("INSERT INTO ".Yearalize('stock_batch')."
								(buying_price,
								selling_price,
								date_created,
								opening_balance,
								date_manufacture,
								date_expiry,
								is_active,
								status,
								user_id,
								buyer_id,
								supplier_id,
								product_id,
								storeroom_id,
								tax_id)
							VALUES(".
								$group_values["buying_price_".$i].", ".
								$group_values["selling_price_".$i].", '".
								date("Y-m-d H:i:s")."', ".
								$group_values["received_".$i].", '".
								get_date($group_values["date_manufacture_".$i])."', '".
								get_expiry_date($group_values["date_manufacture_".$i], $group_values["shelf_life_".$i])."', ".
								"'Y', '".
								STATUS_COMPLETED."', ".
								$_SESSION["int_user_id"].", ".
								$_GET["int_assigned_to_id"].", ".
								$group_values["supplier_".$i].", ".
								$group_values["product_id_".$i].", ".
								$_SESSION["int_current_storeroom"].", ".
								$group_values["tax_category_".$i]."
								)");
					}
					$int_batch_id = $qry_update->getInsertedID();
					if (empty($group_values["batch_".$i])) {
						// set the batch code to the autoincremental value of batch_id if it is not assigned
						$qry_update->Query("UPDATE ".Yearalize('stock_batch')."
							SET batch_code = '".$int_batch_id."'
							WHERE (batch_id=".$int_batch_id.")
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
					}
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock batch";
					}

				// flag is_active to false where stock_available is zero or below
				$qry_update->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
					SET is_active = 'N',
						debug = 'receive'
					WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (product_id = ".$group_values["product_id_".$i].")
						AND (stock_available <= 0)
				");

				// update stock_storeroom_product_year_month, updating fields stock_ordered and stock_current
					$total_received = $group_values["received_".$i] + $group_values["bonus_".$i];
					$qry_update->Query("UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_ordered = stock_ordered - ".$group_values["ordered_".$i].",
							stock_current = stock_current + ".$total_received."
						WHERE (product_id=".$group_values["product_id_".$i].") AND
							(storeroom_id=".$_SESSION["int_current_storeroom"].")");

					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock storeroom product";
					}

				// add an entry in stock_storeroom_batch_year_month
					$qry_update->Query("INSERT INTO ".Monthalize('stock_storeroom_batch')."
							(stock_available,
							shelf_id,
							batch_id,
							storeroom_id,
							product_id)
						VALUES (".$total_received.",
							0, ".
							$int_batch_id.", ".
							$_SESSION["int_current_storeroom"].", ".
							$group_values["product_id_".$i].")");
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock storeroom batch";
					}

        // check whether an entry exists in stock_balance_year
          $qry_update->Query("SELECT * 
              FROM ".Yearalize('stock_balance')." 
							WHERE (product_id=".$group_values["product_id_".$i].") AND
								(storeroom_id=".$_SESSION["int_current_storeroom"].") AND
								(balance_month=".$_SESSION["int_month_loaded"].") AND
								(balance_year=".$_SESSION["int_year_loaded"].")");
								
				// update entry in stock_balance_year if found
          if ($qry_update->RowCount() > 0) {
  					$qry_update->Query("UPDATE ".Yearalize('stock_balance')."
  							SET stock_received = stock_received + ".$group_values["received_".$i].",
								stock_closing_balance = stock_closing_balance + ".$group_values["received_".$i]."
  							WHERE (product_id=".$group_values["product_id_".$i].") AND
  								(storeroom_id=".$_SESSION["int_current_storeroom"].") AND
  								(balance_month=".$_SESSION["int_month_loaded"].") AND
  								(balance_year=".$_SESSION["int_year_loaded"].")");
  					if ($qry_update->b_error == true) {
  						$bool_success = false;
  						$str_message = "error updating stock balances";
  					}
  			// else create an entry
  				} else {
  					$qry_update->Query("INSERT INTO ".Yearalize('stock_balance')."
  					    (stock_closing_balance,
						stock_received,
						product_id,
						storeroom_id,
						balance_month,
						balance_year)
					      VALUES (".$group_values["received_".$i].", ".
							$group_values["received_".$i].", ".
  							$group_values["product_id_".$i].", ".
  							$_SESSION["int_current_storeroom"].", ".
  							$_SESSION["int_month_loaded"].", ".
  							$_SESSION["int_year_loaded"].")");
  					if ($qry_update->b_error == true) {
  						$bool_success = false;
  						$str_message = "error updating stock balances";
  					}
  				}

				// add an entry in stock_transfer_year_month
					$str_update = "INSERT INTO ".Monthalize('stock_transfer')."
							(transfer_quantity,
							transfer_description,
							date_created,
							module_id,
							user_id,
							storeroom_id_from,
							storeroom_id_to,
							product_id,
							batch_id,
							module_record_id,
							transfer_type,
							transfer_status,
							user_id_dispatched,
							user_id_received,
							is_deleted)
						VALUES(".
							$group_values["received_".$i].", '".
							"PURCHASE ORDER ".$_GET["str_po_number"]."', '".
							date("Y-m-d H:i:s")."', ".
							"3, ".
							$_SESSION["int_user_id"].", ".
							"0, ".
							$_SESSION["int_current_storeroom"].", ".
							$group_values["product_id_".$i].", ".
							$int_batch_id.", ".
							$_GET["id"].", ".
							TYPE_RECEIVED.", ".
							STATUS_COMPLETED.", ".
							$_GET["int_assigned_to_id"].", ".
							$_SESSION["int_user_id"].", ".
							"'N')"; 
					$qry_update->Query($str_update);
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock transfer";
					}

				// update purchase_items_year, setting the quantity_received, quantity_bonus and batch_id field
					$qry_update->Query("UPDATE ".Yearalize('purchase_items')."
						SET quantity_received=".$group_values["received_".$i].",
							quantity_bonus=".$group_values["bonus_".$i].",
							is_received='Y',
							batch_id=".$int_batch_id."
						WHERE (purchase_order_id=".$_GET["id"].") AND
							(product_id=".$group_values["product_id_".$i].")");
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating purchase items";
					}

				} // end of "if selected"

			}	// end of loop that iterates through received items

			// set the purchase order to received only if selected rows = total rows
			if ($int_num_rows_selected == count($_SESSION["arr_split_batches"])) {
				// update purchase_order_year, setting purchase_status field
				$qry_update->Query("UPDATE ".Yearalize('purchase_order')."
					SET purchase_status=".PURCHASE_RECEIVED.",
						date_received='".date("Y-m-d H:i:s")."'
					WHERE (purchase_order_id=".$_GET["id"].")");
				if ($qry_update->b_error == true) {
					$bool_success = false;
					$str_message = "error updating purchase order";
				}
			}

			if ($bool_success) {
				$qry_update->Query("COMMIT");
				echo "<script language=\"javascript\">\n";
				echo "alert('Purchase Order saved successfully');\n";
				echo "window.opener.document.location=window.opener.document.location.href;\n";
				echo "window.close();\n";
				echo "</script>\n";
			}
			else {
				$qry_update->Query("ROLLBACK");
				echo "<script language=\"javascript\">\n";
				echo "alert('".$str_message."');\n";
				echo "</script>\n";
			}

			$qry_update->Free();

		} // end of "if selected > 0" statement

	}	// end function saveForm

?>

</body>
</html>
