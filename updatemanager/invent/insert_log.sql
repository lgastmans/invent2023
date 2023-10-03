CREATE TABLE IF NOT EXISTS `update_log` (
  `id` int(11) NOT NULL auto_increment,
  `type_id` int(11) NOT NULL,
  `updated_on` datetime NOT NULL,
  `filename` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;
