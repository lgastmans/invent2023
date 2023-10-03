CREATE TABLE `invent`.`salespersons` (
`id` INT NOT NULL ,
`address` VARCHAR( 128 ) NOT NULL ,
`first` VARCHAR( 32 ) NOT NULL ,
`last` VARCHAR( 32 ) NOT NULL
) ENGINE = MYISAM ;

ALTER TABLE `salespersons` ADD PRIMARY KEY ( `id` );

ALTER TABLE `salespersons` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT 

ALTER TABLE `bill_2011_5` ADD `salesperson_id` INT NOT NULL 