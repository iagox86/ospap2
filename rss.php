<?php
	require_once('include/constants.php');
	require_once('include/functions.php');
	require_once('include/messages.php');

	require_once('cls/clsAlbum.php');
	require_once('cls/clsDB.php');
	require_once('cls/clsPicture.php');
	require_once('cls/clsSetting.php');
	require_once('cls/clsUser.php');

	session_start();
	clsSetting::load_settings();

	try
	{
		if(!isset($_SESSION['objUser']))
			$objUser = clsUser::getCookie();
		else
			$objUser = $_SESSION['objUser'];


		header("Content-type: application/xhtml+xml");

		$arrPictures = clsPicture::getRecentPictures($objUser, 10);

		$url = "http://" . $_SERVER['HTTP_HOST'] . "/" . preg_replace("/\/[a-zA-Z0-9.]*$/", "/index.php", $_SERVER['PHP_SELF']);

		print "<?xml version='1.0' encoding='UTF-8'?>
<!-- generator='OSPAP2' -->
<rss version='2.0'
	xmlns:content='http://purl.org/rss/1.0/modules/content/'
	xmlns:wfw='http://wellformedweb.org/CommentAPI/'
	xmlns:dc='http://purl.org/dc/elements/1.1/'
	>

<channel>
	<title>" . SITE_NAME . "</title>
	<link>$url</link>
	<description>" . SITE_DESCRIPTION . "</description>
	<generator>http://www.javaop.com/~ron/ospap2</generator>
	<language>en</language>
";

		foreach($arrPictures as $objPicture)
		{
			$objPicture = new clsPicture($objPicture->get('id'));

			$link = "http://" . $_SERVER['HTTP_HOST'] . "/" . preg_replace("/\/[a-zA-Z0-9.]*$/", "/index.php?action=picture&amp;".$objPicture->getIDPair(), $_SERVER['PHP_SELF']);

			print "	<item>\n";
			print "		<title>" . $objPicture->get('title') . "</title>\n";
			print "		<link>$link</link>\n";
			print "		<comments>$link</comments>\n";
			print "		<pubDate>" . date("D M j Y G:i:s T", strtotime($objPicture->get('date'))) . "</pubDate>\n";
			print "		<dc:creator>" . $objPicture->getFrom('user', 'username') . "</dc:creator>\n";
		
			print "		<category><![CDATA[" . $objPicture->getFrom('album', 'name') . "]]></category>\n";

			print "		<guid isPermaLink=\"true\">$link</guid>\n";
			print "		<description><![CDATA[" . cut_text($objPicture->get('caption'), 200) . "<br><br>" . $objPicture->getHtmlThumbnail(150, 150) . "]]></description>\n";
			print "		<content:encoded><![CDATA[" . $objPicture->get('caption') . "<br><br>" . $objPicture->getHtml() . "]]></content:encoded>\n";
			print "		<wfw:commentRss>" . $_SERVER['PHP_SELF'] . "</wfw:commentRss>\n";
			print "	</item>\n";
		}
		print "</channel>\n";
		print "</rss>\n";
	}
	catch(Exception $e)
	{
		$_SESSION['e'] = $e;
		header("Location: index.php?action=exception&message=" . $e->getMessage());
	}
?>
