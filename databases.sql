CREATE TABLE IF NOT EXISTS `lp_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL,
  `flag` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;

INSERT INTO `lp_users` (`id`, `user_name`,`flag`) VALUES
  (1, 'ChapatiyaqPTSM', 'fr');