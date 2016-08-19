CREATE TABLE `catalog_cache` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catalog_page_id` int(10) unsigned NOT NULL,
  `filter_id` char(8) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalog_page_id_2` (`catalog_page_id`,`filter_id`,`item_id`),
  KEY `catalog_page_id` (`catalog_page_id`),
  KEY `item_id` (`item_id`),
  KEY `filter_id` (`filter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1859788 DEFAULT CHARSET=utf8;
