CREATE TABLE IF NOT EXISTS `yui_grid` (
  `id` int(11) NOT NULL auto_increment,
  `fieldname` varchar(64) NOT NULL,
  `yui_fieldname` varchar(64) NOT NULL,
  `columnname` varchar(64) NOT NULL,
  `formatter` varchar(32) NOT NULL,
  `is_custom_formatter` char(1) NOT NULL default 'N',
  `parser` varchar(16) NOT NULL,
  `columnwidth` int(11) NOT NULL default '100',
  `visible` char(1) NOT NULL default 'Y',
  `sortable` char(1) NOT NULL default 'Y',
  `filter` char(1) NOT NULL default 'N',
  `gridname` varchar(64) NOT NULL,
  `view_name` varchar(32) NOT NULL default 'default',
  `position` smallint(6) NOT NULL default '1',
  `is_primary_key` char(1) NOT NULL default 'N',
  `user_id` int(11) NOT NULL default '1',
  `alias` varchar(16) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=111 ;
