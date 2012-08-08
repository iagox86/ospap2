<?php

	require_once('cls/clsAlbum.php');

	print "<span class='recentheader'>Recently Updated Albums</span>";

	$arrAlbums = clsAlbum::getRecentAlbums($objUser, 5);
	foreach($arrAlbums as $objAlbum)
	{
		$objAlbum = new clsAlbum($objAlbum->get('id'));
		print "<p><a href='index.php?action=albums&".$objAlbum->getIDPair()."' class='recentlink'>".$objAlbum->get('name')."</a> <span class='recentdate'>(" . $objAlbum->getUsername() . ")</span> ".$objAlbum->getNewIcon($objUser)."<br>";
		print "<span class='recentdate'>(" . $objAlbum->getLastUpdated() . ")</span></p>";
	}

?>
