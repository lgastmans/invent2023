<?
	require_once('../include/const.inc.php');
	require_once('../include/db.inc.php');
	require_once('../include/session.inc.php');

	function update_active_status() {

		$qry_storeroom_batches = new Query("SELECT * FROM ".Monthalize('stock_storeroom_batch')." LIMIT 1");
		$qry_update = new Query("SELECT * FROM ".Monthalize('stock_storeroom_batch')." LIMIT 1");
	
		$str_batches = "
			SELECT *
			FROM ".Yearalize('stock_batch');
		$qry_batches = new Query($str_batches);
	
		$int_counter = 0;
	
		for ($i=0; $i<$qry_batches->RowCount(); $i++) {
	
			$qry_storeroom_batches->Query("
				SELECT *
				FROM ".Monthalize('stock_storeroom_batch')."
				WHERE batch_id = ".$qry_batches->FieldByName('batch_id')
			);
	
			$is_active = 'N';
	
			for ($j=0; $j<$qry_storeroom_batches->RowCount(); $j++) {
				if ($qry_storeroom_batches->FieldByName('is_active') == 'Y') {
					$is_active = 'Y';
					break;
				}
				$qry_storeroom_batches->Next();
			}
	
			if ($is_active == 'N') {
				$int_counter++;
	
				$qry_update->Query("
					UPDATE ".Yearalize('stock_batch')."
					SET is_active = 'N'
					WHERE batch_id = ".$qry_batches->FieldByName('batch_id')
				);
			}
	
			$qry_batches->Next();
		}
	}

	update_active_status();
?>