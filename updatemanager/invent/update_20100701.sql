ALTER TABLE `customer` ADD `is_other_state` CHAR( 1 ) NOT NULL DEFAULT 'N';
ALTER TABLE `orders_2010_7` ADD `has_c_form` CHAR( 1 ) NOT NULL DEFAULT 'N';
ALTER TABLE `orders_2010_7` ADD  `has_h_form` CHAR( 1 ) NOT NULL DEFAULT  'N';
ALTER TABLE `user_settings` ADD `stock_dc_number` INT NOT NULL DEFAULT '0';
INSERT INTO `stock_transfer_type` (
`transfer_type` ,
`transfer_type_description`
)
VALUES (
'9', 'Delivery Chalan'
);
