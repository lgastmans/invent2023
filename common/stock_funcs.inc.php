<?

	function check_active_batches() {
		$str_batches = "
			SELECT stock_storeroom_batch_id, batch_id, @cur_id := product_id AS product_id,
				(SELECT 
					COUNT(batch_id) 
					FROM ".Monthalize('stock_storeroom_batch')."
					WHERE stock_available  > 0 
						AND is_active = 'Y' 
						AND product_id = @cur_id
				) AS counter
			FROM ".Monthalize('stock_storeroom_batch')."
			WHERE is_active = 'Y'
				AND stock_available = 0";
		$qry_batches = new Query($str_batches);
		
		$arr_batches = array();
		
		for ($i=0;$i<$qry_batches->RowCount();$i++) {
			if ($qry_batches->FieldByName('counter') > 0)
				$arr_batches[] = $qry_batches->FieldByName('stock_storeroom_batch_id');
			
			$qry_batches->Next();
		}
		
		return $arr_batches;
	}
?>