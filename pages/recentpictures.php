<?php

	require_once('cls/clsAlbum.php');
	require_once('cls/clsPicture.php');

	print "<span class='recentheader'>New Pictures</span>";

	$arrPictures = clsPicture::getRecentPictures($objUser, 5);

	foreach($arrPictures as $objPicture)
	{
		$objPicture = new clsPicture($objPicture->get('id'));
		$objAlbum   = new clsAlbum($objPicture->get('album_id'));

		print "<p>";
		print $objPicture->getHtmlThumbnail(100, 100) . "<br>";
		print "<a href='index.php?action=picture&".$objPicture->getIDPair()."' class='recentlink'>".$objPicture->get('title') . "</a> <span class='recentdate'>in</span> <a href='index.php?action=albums&".$objAlbum->getIDPair()."' class='recentlink'>".$objAlbum->get('name')."</a><br>";
		print "<span class='recentdate'>" . $objPicture->getUsername() . "<br>";
		print time_to_text(strtotime($objPicture->get('date'))) . "</span>";
		print "</p>";
	}

?>
