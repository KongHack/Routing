CREATE TABLE IF NOT EXISTS `_RouteRawList` (
  `route_id` int(11) NOT NULL AUTO_INCREMENT,
  `route_path` varchar(250) COLLATE utf8_bin NOT NULL,
  `route_name` varchar(250) COLLATE utf8_bin NOT NULL,
  `route_session` tinyint(1) NOT NULL DEFAULT '0',
  `route_autoWrapper` tinyint(1) NOT NULL DEFAULT '0',
  `route_class` text COLLATE utf8_bin NOT NULL,
  `route_pre_args` text COLLATE utf8_bin NOT NULL,
  `route_post_args` text COLLATE utf8_bin NOT NULL,
  `route_pexCheck` text COLLATE utf8_bin NOT NULL,
  `route_pexCheckAny` text COLLATE utf8_bin NOT NULL,
  `route_pexCheckExact` text COLLATE utf8_bin NOT NULL,
  `route_meta` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`route_id`),
  UNIQUE KEY `route_name` (`route_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
