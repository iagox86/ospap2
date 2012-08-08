-- MySQL dump 10.11
--
-- Host: localhost    Database: ospap2
-- ------------------------------------------------------
-- Server version	5.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tbl_album`
--

DROP TABLE IF EXISTS `tbl_album`;
CREATE TABLE `tbl_album` (
  `album_id` int(11) NOT NULL auto_increment,
  `album_album_id` int(11) NOT NULL default '0',
  `album_user_id` int(11) NOT NULL default '0',
  `album_group_id` int(11) NOT NULL default '0',
  `album_albumpolicy_guest_id` int(11) NOT NULL default '0',
  `album_albumpolicy_member_id` int(11) NOT NULL default '0',
  `album_albumpolicy_group_id` int(11) NOT NULL default '0',
  `album_name` varchar(256) NOT NULL default '',
  `album_caption` text NOT NULL,
  `album_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `album_mime` varchar(32) NOT NULL default '',
  `album_max_width` int(11) NOT NULL default '0',
  `album_max_height` int(11) NOT NULL default '0',
  `album_hide_from_main_page` tinyint(4) NOT NULL default '0',
  `album_export_tag` varchar(256) NOT NULL default '',
  `album_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`album_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_albumpolicy`
--

DROP TABLE IF EXISTS `tbl_albumpolicy`;
CREATE TABLE `tbl_albumpolicy` (
  `albumpolicy_id` int(11) NOT NULL auto_increment,
  `albumpolicy_user_id` int(11) NOT NULL default '0',
  `albumpolicy_allow_post_picture` tinyint(4) NOT NULL default '0',
  `albumpolicy_allow_post_comment` tinyint(4) NOT NULL default '0',
  `albumpolicy_allow_rate` tinyint(4) NOT NULL default '0',
  `albumpolicy_allow_view` tinyint(4) NOT NULL default '0',
  `albumpolicy_allow_delete_picture` tinyint(4) NOT NULL default '0',
  `albumpolicy_allow_create_subalbum` tinyint(4) NOT NULL default '0',
  `albumpolicy_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`albumpolicy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_comment`
--

DROP TABLE IF EXISTS `tbl_comment`;
CREATE TABLE `tbl_comment` (
  `comment_id` int(11) NOT NULL auto_increment,
  `comment_user_id` int(11) NOT NULL default '0',
  `comment_picture_id` int(11) NOT NULL default '0',
  `comment_username` varchar(256) NOT NULL default '',
  `comment_title` varchar(256) NOT NULL default '',
  `comment_text` text NOT NULL,
  `comment_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `comment_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_group`
--

DROP TABLE IF EXISTS `tbl_group`;
CREATE TABLE `tbl_group` (
  `group_id` int(11) NOT NULL auto_increment,
  `group_user_id` int(11) NOT NULL default '0',
  `group_name` varchar(256) NOT NULL default '0',
  `group_is_private` tinyint(4) NOT NULL default '0',
  `group_is_hidden` tinyint(4) NOT NULL default '0',
  `group_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_imagedata`
--

DROP TABLE IF EXISTS `tbl_imagedata`;
CREATE TABLE `tbl_imagedata` (
  `imagedata_id` int(11) NOT NULL auto_increment,
  `imagedata_data` longtext NOT NULL,
  `imagedata_mime` varchar(32) NOT NULL default '',
  `imagedata_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`imagedata_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_news`
--

DROP TABLE IF EXISTS `tbl_news`;
CREATE TABLE `tbl_news` (
  `news_id` int(11) NOT NULL auto_increment,
  `news_user_id` int(11) NOT NULL default '0',
  `news_album_id` int(11) NOT NULL default '0',
  `news_title` varchar(256) NOT NULL default '',
  `news_text` text NOT NULL,
  `news_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `news_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`news_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_notification`
--

DROP TABLE IF EXISTS `tbl_notification`;
CREATE TABLE `tbl_notification` (
  `notification_id` int(11) NOT NULL auto_increment,
  `notification_on_all_pictures` tinyint(4) NOT NULL default '0',
  `notification_on_group_pictures` tinyint(4) NOT NULL default '0',
  `notification_on_comments` tinyint(4) NOT NULL default '0',
  `notification_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`notification_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_picture`
--

DROP TABLE IF EXISTS `tbl_picture`;
CREATE TABLE `tbl_picture` (
  `picture_id` int(11) NOT NULL auto_increment,
  `picture_user_id` int(11) NOT NULL default '0',
  `picture_album_id` int(11) NOT NULL default '0',
  `picture_imagedata_id` int(11) NOT NULL default '0',
  `picture_title` varchar(256) NOT NULL default '',
  `picture_username` varchar(256) NOT NULL default '',
  `picture_width` int(11) NOT NULL default '0',
  `picture_height` int(11) NOT NULL default '0',
  `picture_caption` text NOT NULL,
  `picture_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `picture_original_name` varchar(256) NOT NULL default '',
  `picture_original_mime` varchar(32) NOT NULL default '',
  `picture_original_size` int(11) NOT NULL default '0',
  `picture_confirmed` tinyint(4) NOT NULL default '0',
  `picture_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`picture_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_setting`
--

DROP TABLE IF EXISTS `tbl_setting`;
CREATE TABLE `tbl_setting` (
  `setting_id` int(11) NOT NULL auto_increment,
  `setting_name` varchar(256) NOT NULL default '',
  `setting_value` text NOT NULL,
  `setting_comment` text NOT NULL,
  `setting_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`setting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_thumbnail`
--

DROP TABLE IF EXISTS `tbl_thumbnail`;
CREATE TABLE `tbl_thumbnail` (
  `thumbnail_id` int(11) NOT NULL auto_increment,
  `thumbnail_picture_id` int(11) NOT NULL default '0',
  `thumbnail_imagedata_id` int(11) NOT NULL default '0',
  `thumbnail_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `thumbnail_width` int(11) NOT NULL default '0',
  `thumbnail_height` int(11) NOT NULL default '0',
  `thumbnail_actual_width` int(11) NOT NULL default '0',
  `thumbnail_actual_height` int(11) NOT NULL default '0',
  `thumbnail_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`thumbnail_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_user`
--

DROP TABLE IF EXISTS `tbl_user`;
CREATE TABLE `tbl_user` (
  `user_id` int(11) NOT NULL auto_increment,
  `user_notification_id` int(11) NOT NULL default '0',
  `user_username` varchar(256) NOT NULL default '',
  `user_password` varchar(256) NOT NULL default '',
  `user_email` varchar(256) NOT NULL default '',
  `user_realname` text NOT NULL,
  `user_location` text NOT NULL,
  `user_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_is_approved` tinyint(4) NOT NULL default '0',
  `user_is_advanced` tinyint(4) NOT NULL default '0',
  `user_is_admin` tinyint(4) NOT NULL default '0',
  `user_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_usercommentview`
--

DROP TABLE IF EXISTS `tbl_usercommentview`;
CREATE TABLE `tbl_usercommentview` (
  `usercommentview_id` int(11) NOT NULL auto_increment,
  `usercommentview_user_id` int(11) NOT NULL default '0',
  `usercommentview_comment_id` int(11) NOT NULL default '0',
  `usercommentview_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `usercommentview_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`usercommentview_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_usergroup`
--

DROP TABLE IF EXISTS `tbl_usergroup`;
CREATE TABLE `tbl_usergroup` (
  `usergroup_id` int(11) NOT NULL auto_increment,
  `usergroup_user_id` int(11) NOT NULL default '0',
  `usergroup_user_inviter_id` int(11) NOT NULL default '0',
  `usergroup_user_approver_id` int(11) NOT NULL default '0',
  `usergroup_group_id` int(11) NOT NULL default '0',
  `usergroup_is_administrator` tinyint(4) NOT NULL default '0',
  `usergroup_is_approved` int(11) NOT NULL default '0',
  `usergroup_has_accepted` tinyint(4) NOT NULL default '0',
  `usergroup_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`usergroup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_userpictureview`
--

DROP TABLE IF EXISTS `tbl_userpictureview`;
CREATE TABLE `tbl_userpictureview` (
  `userpictureview_id` int(11) NOT NULL auto_increment,
  `userpictureview_user_id` int(11) NOT NULL default '0',
  `userpictureview_picture_id` int(11) NOT NULL default '0',
  `userpictureview_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `userpictureview_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`userpictureview_id`),
  KEY `user_id` (`userpictureview_user_id`),
  KEY `picture_id` (`userpictureview_picture_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `tbl_vote`
--

DROP TABLE IF EXISTS `tbl_vote`;
CREATE TABLE `tbl_vote` (
  `vote_id` int(11) NOT NULL auto_increment,
  `vote_picture_id` int(11) NOT NULL default '0',
  `vote_user_id` int(11) NOT NULL default '0',
  `vote_ip` varchar(32) NOT NULL default '',
  `vote_vote` int(11) NOT NULL default '0',
  `vote_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`vote_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-09-30 22:32:52
-- MySQL dump 10.11
--
-- Host: localhost    Database: ospap2
-- ------------------------------------------------------
-- Server version	5.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tbl_setting`
--

DROP TABLE IF EXISTS `tbl_setting`;
CREATE TABLE `tbl_setting` (
  `setting_id` int(11) NOT NULL auto_increment,
  `setting_name` varchar(256) NOT NULL default '',
  `setting_value` text NOT NULL,
  `setting_comment` text NOT NULL,
  `setting_is_deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`setting_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_setting`
--

LOCK TABLES `tbl_setting` WRITE;
/*!40000 ALTER TABLE `tbl_setting` DISABLE KEYS */;
INSERT INTO `tbl_setting` VALUES (1,'MAX_X','1024','This is the absolute maximum width of images. It should be reasonably large, keeping in mind that users may upload huge images and waste room.',0),(2,'MAX_Y','768','This is the absolute maximum height of images. It should be reasonably large, keeping in mind that users may upload huge images and waste room.',0),(3,'DEBUG','1','',0),(4,'DEFAULT_MIME','image/jpeg','Valid values are image/jpeg, image/png, and image.gif.',0),(5,'MAX_CAPTION','250','This is the maxiumu number of characters in a caption before it\'s cut off. ',0),(6,'DEFAULT_X','640','This is the default maximum width, used when creating an album and always used in non-advanced mode.',0),(7,'DEFAULT_Y','480','This is the default maximum height, used when creating an album and always used in non-advanced mode.',0),(8,'ALBUM_PREVIEWSIZE','64','The size of the preview pictures on the album page.',0),(9,'ALBUM_NUMPERROW','5','The number of album thumbnail pictures on a row.',0),(10,'ALBUM_NUMPREVIEW','5','The number of pictures to show in the album preview.',0),(11,'MAX_VOTE','10','The maximum vote somebody can leave for an image.',0),(12,'SETTING_WEIGHTED_AVERAGE','1','If set to 1, a weighted average will be used that makes the highest and lowest votes count for less.',0),(13,'SITE_NAME','OSPAP2','',0),(14,'SITE_DESCRIPTION','Ron\'s photoalbum / test page.','',0),(15,'VERSION','1.00','This field is used when upgrading to a new version. You shouldn\'t modify it.',0);
/*!40000 ALTER TABLE `tbl_setting` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-09-30 22:45:55
