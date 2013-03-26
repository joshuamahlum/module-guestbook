CREATE TABLE IF NOT EXISTS `guestbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `author` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `website` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `text` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `status` enum('published','moderation','spam') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'moderation',
  `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'Serialized array',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;