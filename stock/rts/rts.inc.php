<?php


class RTS {
 
	public $stock_rts_id;
	public $storeroom_id;
	public $bill_number;
	public $date_created;
	public $total_amount;
	public $discount;
	public $bill_status;
	public $description;
	public $user_id;
	public $supplier_id;
	public $module_id;
	public $nvoice_number;
	public $invoice_date;


	public $items = array();


	public $ret = array();

	
	function __construct()
	{
		unset($this->ret);

		$this->ret['error'] = false;
		
		/*
			default values
		*/
		$this->stock_rts_id = 0;
		$this->module_id = 2;
		$this->storeroom_id = $_SESSION['int_current_storeroom'];
		$this->date_created = date('Y-m-d');
		$this->total_amount = 0;
		$this->user_id = $_SESSION['int_user_id'];

		/*
			get the last receipt number
		*/
		$sql = "
			SELECT bill_number
			FROM ".Monthalize('stock_rts')."
			WHERE (module_id = ".$this->module_id.") AND 
				(storeroom_id = ".$this->storeroom_id.")
			ORDER BY bill_number+0 DESC";
		$qry = new Query($sql);

		if ($qry->RowCount() > 0)
			$this->bill_number = intval($qry->FieldByName('bill_number')) +1;
		else
			$this->bill_number = 0;

	} // construct



	public function insert()
	{
		$sql = "
			INSERT INTO ".Monthalize('stock_rts')."
				(storeroom_id,
				bill_number,
				date_created,
				total_amount,
				discount,
				bill_status,
				user_id,
				supplier_id,
				module_id,
				description,
				invoice_number,
				invoice_date)
			VALUES (".
				$this->storeroom_id.", ".
				"'".$this->bill_number."', ".
				"'".$this->date_created."', ".
				$this->total_amount.", ".
				$this->discount.", ".
				$this->bill_status.", ".
				$this->user_id.", ".
				$this->supplier_id.", ".
				$this->module_id.", ".
				"'".$this->description."',".
				"'".$this->invoice_number."',".
				"'".$this->invoice_date."')";

		$result_set = new Query($sql);


		if ($result_set->b_error == true) {

			$this->ret['error'] = true;
			$this->ret['msg'] = "Error: ".$result_set->err;

			return false;
		}
		else
			$this->stock_rts_id = $result_set->getInsertedID();

		return true;

	} // insert



	public function update()
	{
		$this->ret['error'] = false;
		$this->ret['msg'] = "Updated successfully";

		$sql = "
			UPDATE ".Monthalize('stock_rts')." rts
			SET
				invoice_number = '".$this->invoice_number."',
				invoice_date = '".$this->invoice_date."',
				bill_number = '".$this->bill_number."',
				description = '".$this->description."'
			WHERE stock_rts_id = ".$this->stock_rts_id;
		$result_set = new Query($sql);

		if ($result_set->b_error == true) {
			$this->ret['error'] = true;
			$this->ret['msg'] = "Error: ".$result_set->err;

			return false;
		}

		return true;

	} // update



	public function save()
	{

		/*
			insert row in rts table
		*/
		if ($this->insert()==false) {

			return $this->ret;

		}


		/*
			insert row in rts items table
		*/
		foreach ($this->items as $row) {

			$item = new RTS_item($this);

			$item->quantity = $row['quantity'];
			$item->price = $row['price'];
			$item->bprice = $row['bprice'];
			$item->product_id = $row['product_id'];
			$item->batch_id = $row['batch_id'];
			$item->tax_id = $row['tax_id'];

			$item->update_stock_on_insert = true;

			$item->insert();

		}

		if ($this->ret['error']==false) {

			$this->ret['msg'] = "RTS ".$this->bill_number." saved successfully";

		}

		return $this->ret;

	} // update

} // RTS Class





class RTS_item {

	public $rts_item_id;
	public $rts_id;
	public $storeroom_id;
	public $quantity;
	public $price;
	public $bprice;
	public $product_id;
	public $batch_id;
	public $tax_id;

	public $ret = array();

	public $update_stock_on_insert;

	private $rts;


	function __construct($rts)
	{

		unset($this->ret);

		$this->rts = $rts;

		$this->rts_id = $this->rts->stock_rts_id;
		$this->storeroom_id = $_SESSION['int_current_storeroom'];

		$this->update_stock_on_insert = false;

	} // construct


	public function insert()
	{
		$sql = "
			INSERT INTO ".Monthalize('stock_rts_items')."
				(rts_id,
				quantity,
				price,
				bprice,
				product_id,
				batch_id,
				tax_id)
			VALUES (".
				$this->rts_id.", ".
				$this->quantity.", ".
				$this->price.", ".
				$this->bprice.", ".
				$this->product_id.", ".
				$this->batch_id.", ".
				$this->tax_id.")";

		$result_set = new Query($sql);


		if ($result_set->b_error == true) {

			$this->ret['error'] = true;
			$this->ret['msg'] = "Error: ".$result_set->err;

			return false;
		}
		else {
			$this->rts_item_id = $result_set->getInsertedID();

			if ($this->update_stock_on_insert) {

				$this->update_stock();

			}
		}

		return true;

	} // insert


	private function update_stock()
	{

		$can_save = true;

		$result_set = new Query("BEGIN");
		

		$flt_quantity_to_bill = $this->quantity;


		/*
			TABLE stock_storeroom_product
		*/
		$result_set->Query("UPDATE ".Monthalize('stock_storeroom_product')."
			SET stock_current = ABS(ROUND((stock_current - ".$flt_quantity_to_bill."),3))
			WHERE (product_id=".$this->product_id.") 
				AND	(storeroom_id=".$this->storeroom_id.")");
		if ($result_set->b_error == true) {
			$can_save = false;
			$str_message = "Error updating stock_storeroom_product";
		}


		/*
			TABLE stock_storeroom_batch
			There was some strange behaviour subtracting here, hence the ROUND function call
			very small amounts were generated, as -7.12548e-9
		*/
		$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
			SET stock_available = ROUND((stock_available - ".$flt_quantity_to_bill."),3)
			WHERE (batch_id = ".$this->batch_id.") 
				AND	(storeroom_id = ".$this->storeroom_id.") 
				AND	(product_id = ".$this->product_id.")
		");
		if ($result_set->b_error == true) {
			$can_save = false;
			$str_message = "Error updating stock_storeroom_batch";
		}


		/*
			if the current stock becomes zero, then set the batch's is_active flag to false
			if there is more than one active batch available. There should always be one active
			batch regardless of the available stock
		*/
		$result_set->Query("SELECT stock_available 
			FROM ".Monthalize('stock_storeroom_batch')."
			WHERE (batch_id = ".$this->batch_id.")
				AND (storeroom_id = ".$this->storeroom_id.")
				AND	(product_id = ".$this->product_id.")
		");

		if ($result_set->FieldByName('stock_available') <= 0) {

			/*
				check number of available active batches, and if it is greater than one
				set the current batch's is_active flag to false
			*/
			$qry_check = new Query("SELECT * 
				FROM ".Monthalize('stock_storeroom_batch')." 
				WHERE (storeroom_id = ".$this->storeroom_id.") 
					AND (product_id = ".$this->product_id.")
					AND (is_active = 'Y')
			");

			if ($qry_check->RowCount() > 1) {

				$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
				SET is_active = 'N'
				WHERE (batch_id = ".$this->batch_id.")
					AND (storeroom_id = ".$this->storeroom_id.")
					AND (product_id = ".$this->product_id.")
				");
			}
		}


		/*
			TABLE stock_balance
		*/
		$result_set->Query("
			SELECT *
			FROM ".Yearalize('stock_balance')."
				WHERE (product_id = ".$this->product_id.")
					AND (storeroom_id = ".$this->storeroom_id.")
					AND (balance_month = ".$_SESSION["int_month_loaded"].")
					AND (balance_year = ".$_SESSION["int_year_loaded"].")
		");

		if ($result_set->RowCount() > 0) {

			$result_set->Query("UPDATE ".Yearalize('stock_balance')."
					SET stock_returned = stock_returned + ".$this->quantity.",
						stock_closing_balance = ROUND((stock_closing_balance - ".$this->quantity."),3)
					WHERE (product_id = ".$this->product_id.")
						AND (storeroom_id = ".$this->storeroom_id.")
						AND	(balance_month = ".$_SESSION["int_month_loaded"].") 
						AND	(balance_year = ".$_SESSION["int_year_loaded"].")");
			if ($result_set->b_error == true) {
				$can_save = false;
				$str_message = "Error updating stock_balance";
			}
		}
		else {

			$result_set->Query("
				INSERT INTO ".Yearalize('stock_balance')."
				(
					stock_returned,
					stock_closing_balance,
					product_id,
					storeroom_id,
					balance_month,
					balance_year
				)
				VALUES(
					".$this->quantity.",
					".$this->quantity.",
					".$this->product_id.",
					".$this->storeroom_id.",
					".$_SESSION["int_month_loaded"].",
					".$_SESSION["int_year_loaded"]."
				)
			");
		}

		/*
			TABLE stock_transfer
		*/
		$result_set->Query("INSERT INTO  ".Monthalize('stock_transfer')."
				(transfer_quantity,
				transfer_description,
				transfer_reference,
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
			VALUES (".
				$this->quantity.", ".
				"'RETURN TO SECTION', ".
				"'".$this->rts->bill_number."', ".
				"'".$this->rts->date_created."', ".
				"2, ".
				$this->rts->user_id.", ".
				$this->storeroom_id.", ".
				"0, ".
				$this->product_id.", ".
				$this->batch_id.", ".
				$this->rts->stock_rts_id.", ".
				TYPE_RETURNED.", ".
				STATUS_COMPLETED.", ".
				$this->rts->user_id.", ".
				"0, ".
				"'N')");
		if ($result_set->b_error == true) {
			$can_save = false;
			$str_message = "Error updating stock_transfer";
		}
		
		
		if ($can_save) {

			$result_set->Query("COMMIT");

			$this->ret['error'] = false;
			$this->ret['msg'] = "Stock updated successfully";

			return true;

		}
		else {

			$result_set->Query("ROLLBACK");

			$this->ret['error'] = true;
			$this->ret['msg'] = $str_message." ".$result_set->err;

			return false;
		}

	} // update_stock

} // RTS_item Class





?>