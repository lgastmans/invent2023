
ALTER TABLE `module` ADD `active` CHAR(1) NOT NULL DEFAULT 'Y' AFTER `module_folder`;

CREATE TABLE `salespersons` (
`id` INT NOT NULL ,
`address` VARCHAR( 128 ) NOT NULL ,
`first` VARCHAR( 32 ) NOT NULL ,
`last` VARCHAR( 32 ) NOT NULL
) ENGINE = MYISAM ;

ALTER TABLE `salespersons` ADD PRIMARY KEY ( `id` );

ALTER TABLE `salespersons` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ;

ALTER TABLE `bill_2017_7` ADD `salesperson_id` INT NOT NULL ;

ALTER TABLE `bill_2017_7` CHANGE `bill_header` `bill_header` VARCHAR(80) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL;

ALTER TABLE `bill_2017_7` CHANGE `date_created` `date_created` DATETIME NULL DEFAULT NULL, CHANGE `resolved_on` `resolved_on` DATETIME NULL DEFAULT NULL;

INSERT INTO `stock_transfer_type` (
`transfer_type` ,
`transfer_type_description`
)
VALUES (
'9', 'Delivery Chalan'
);

ALTER TABLE `stock_transfer_2017_7` CHANGE `transfer_reference` `transfer_reference` VARCHAR(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `date_created` `date_created` DATETIME NULL DEFAULT NULL;