		CREATE TABLE tmp_data SELECT * FROM `stock_balance_2017`;

		TRUNCATE TABLE `stock_balance_2017`;

		ALTER TABLE `stock_balance_2017` DROP INDEX `productid_storeroomid_monthyear`, ADD UNIQUE `productid_storeroomid_monthyear` (`product_id`, `storeroom_id`, `balance_month`, `balance_year`) USING BTREE;

		INSERT IGNORE INTO `stock_balance_2017` SELECT * from tmp_data;
		
		ALTER TABLE stock_balance_2017 DROP INDEX productid_storeroomid_month;

		DROP TABLE tmp_data;
