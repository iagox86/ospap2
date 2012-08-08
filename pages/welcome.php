<?php
	require_once('cls/clsComment.php');
	require_once('cls/clsGroup.php');
	require_once('cls/clsPicture.php');

	if($objUser)
	{
		print "Welcome back, <a href='index.php?action=members&subaction=view&" . $objUser->getIDPair() . "'>" . $objUser->get('username') . "</a>! <br>";
		print "You have <a href='index.php?action=comment&subaction=viewnew'><strong>" . sizeof(clsComment::getNewComments($objUser)) . "</strong> unread comments</a> on your pictures.<br>";
		print "There are <a href='index.php?action=albums&subaction=newpictures'><strong>" . sizeof(clsPicture::getNewPictures($objUser)) . "</strong> new pictures</a>.<br>";

		if($objUser)
		{
			$intNum = sizeof(clsGroup::getInvitations($objUser));

			if($intNum > 0)
				print "You have invitations to <a href='index.php?action=groups&subaction=invitations'><strong>$intNum groups</strong></a>.<br>";
		}
	}
	else
	{
		print "Welcome, guest! You can <a href='index.php?action=login'>log in</a> or <a href='index.php?action=members&subaction=view'>register</a>.<br><br>";
	}

?>
