ALTER TABLE `bill_2018_8` ADD `table_ref` VARCHAR(16) NULL AFTER `customer_id`;
ALTER TABLE `bill_2018_8` ADD `is_draft` TINYINT NOT NULL DEFAULT '0' AFTER `table_ref`;


ALTER TABLE `user_settings` ADD `bill_global_bill_number` INT NOT NULL AFTER `bill_aurocard_bill_number`;
ALTER TABLE `user_settings` ADD `bill_fs_low_balance` SMALLINT NOT NULL DEFAULT '0' AFTER `bill_fs_discount`;


ALTER TABLE `stock_storeroom` ADD `enabled_table_billing` CHAR(1) NOT NULL DEFAULT 'N' AFTER `default_unit_id`;


ALTER TABLE `bill_2018_9` ADD `gstin` VARCHAR(16) NOT NULL AFTER `is_draft`;