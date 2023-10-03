CREATE TABLE IF NOT EXISTS `help` (
  `id` int(11) NOT NULL auto_increment,
  `category_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `level` smallint(6) NOT NULL default '0',
  `section_name` varchar(128) NOT NULL,
  `html_text` text NOT NULL,
  `title` varchar(256) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=30 ;

