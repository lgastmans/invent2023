ALTER TABLE `stock_storeroom` ADD `can_bill_aurocard` CHAR( 1 ) NOT NULL DEFAULT 'N' AFTER `can_bill_creditcard`
ALTER TABLE `user_settings` ADD `bill_aurocard_bill_number` INT NOT NULL DEFAULT '0' AFTER `bill_transfer_bill_number`
ALTER TABLE `bill_2010_10` ADD `aurocard_number` INT NOT NULL DEFAULT '0',
ADD `aurocard_transaction_id` INT NOT NULL DEFAULT '0'