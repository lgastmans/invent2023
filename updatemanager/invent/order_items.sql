CREATE TABLE IF NOT EXISTS `order_items_2011_2` (
  `order_item_id` int(11) NOT NULL auto_increment,
  `order_id` int(11) NOT NULL default '0',
  `quantity_ordered` float NOT NULL default '0',
  `quantity_delivered` float NOT NULL default '0',
  `price` float NOT NULL default '0',
  `is_temporary` char(1) NOT NULL default '',
  `adjusted` float NOT NULL default '0',
  `product_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`order_item_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39843 ;