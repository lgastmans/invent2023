CREATE TABLE `invent`.`stock_supplier_commissions` (
`id` INT NOT NULL ,
`supplier_id` INT NOT NULL ,
`month` INT NOT NULL ,
`year` INT NOT NULL ,
`commission_percent` FLOAT NOT NULL ,
`commission_percent_2` FLOAT NOT NULL ,
`commission_percent_3` FLOAT NOT NULL
) ENGINE = MYISAM ;

ALTER TABLE `stock_supplier_commissions` ADD PRIMARY KEY ( `id` );

ALTER TABLE `stock_supplier_commissions` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ;