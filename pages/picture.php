<?php
	require_once('cls/clsAlbum.php');
	require_once('cls/clsComment.php');
	require_once('cls/clsParameters.php');
	require_once('cls/clsPicture.php');
	require_once('cls/clsTemplate.php');
	require_once('cls/clsThumbnail.php');
	require_once('cls/clsUser.php');
	require_once('cls/clsVote.php');

	$objBreadcrumbs->add('Albums', 'index.php?action=albums');

	$objPicture = new clsPicture();
	$objPicture->getFromRequest();
	$objPicture->load();

	$objPrevPicture = $objPicture->getPrev();
	$objNextPicture = $objPicture->getNext();
	

	if($objPicture->isNew())
		throw new Exception('exception_invalidrequest');

	if(!$objPicture->get('confirmed'))
	{
		header("Location: index.php?action=upload&subaction=preview");
		die;
	}

	/* Check for access. */
	$objAlbum = new clsAlbum($objPicture->get('album_id'));
	if(!$objAlbum->canView($objUser))
		throw new Exception('exception_accessdenied');

	$objAlbum->addBreadcrumbs($objBreadcrumbs);

	
//		$strMiniMenu = "<li><a href='index.php?action=picture&subaction=edit&" . $objPicture->getIDPair() . "'>Create Album</a></li>";
//		$objTemplate->setText('MINIMENU', "<ul>$strMiniMenu</ul>");

	if($strSubAction == '')
	{
		$objTemplate->setText('PAGETITLE', "Viewing " . $objPicture->get('title'));

		$objBreadcrumbs->add($objPicture->get('title'), "index.php?action=picture&" . $objPicture->getIDPair());

		if($objPicture->canEdit($objUser))
			$objMiniMenu->add('Edit', 'index.php?action=picture&subaction=edit&' . $objPicture->getIDPair());
		if($objAlbum->canDeletePicture($objUser))
			$objMiniMenu->add('Delete', 'index.php?action=picture&subaction=delete&' . $objPicture->getIDPair());
		$objMiniMenu->add('Link to this', 'index.php?action=picture&subaction=link&' . $objPicture->getIDPair());

		/* Mark this picture as viewed. */
		$objPicture->setViewed($objUser);

		$objPictureTemplate = new clsTemplate('picture');

		if($objPrevPicture)
			$objPictureTemplate->setText('PREV', "<a href='index.php?action=picture&".$objPrevPicture->getIDPair()."'>Previous<br>".$objPrevPicture->getHtmlThumbnail(64, 64)."</a>");
		else
			$objPictureTemplate->setText('PREV', "<span class='disabled'>At the start</span>");

		if($objNextPicture)
			$objPictureTemplate->setText('NEXT', "<a href='index.php?action=picture&".$objNextPicture->getIDPair()."'>Next<br>".$objNextPicture->getHtmlThumbnail(64, 64)."</a>");
		else
			$objPictureTemplate->setText('NEXT', "<span class='disabled'>At the end</span>");

		$objPictureTemplate->setText('TITLE',    $objPicture->get('title'));
		$objPictureTemplate->setText('USERNAME', $objPicture->getUsername());
		$objPictureTemplate->setText('PICTURE',  $objPicture->getHtml());

		$objPictureTemplate->setText('CAPTION', "<span id='more' style='display: none;'>" . 
													bbcode_format($objPicture->get('caption')) . 
													"<br><a href='#' onClick='$(\"more\").style.display=\"none\"; $(\"less\").style.display=\"block\"'>Less</a></span>");

		$objPictureTemplate->setText('CAPTION', "<span id='less' style='display: inline;'>" . 
													cut_text(bbcode_format($objPicture->get('caption')), MAX_CAPTION, 
													"<br><a href='#' onClick='$(\"less\").style.display=\"none\"; $(\"more\").style.display=\"block\"'>More</a></span>"));

		$strCaption = cut_text(bbcode_format($objPicture->get('caption')), MAX_CAPTION, " (<a href='index.php?action=picture&all=1&".$objPicture->getIDPair()."'>more</a>)");

		/* Voting code. */
		if(clsVote::canVote($objPicture, $objUser, $_SERVER['REMOTE_ADDR']))
		{
			$objPictureTemplate->setText('RATING', clsVote::getVoteField($objPicture));
		}
		else
		{
			if(clsVote::getVoteCount($objPicture))
			{
				$objPictureTemplate->setText('RATING', "Ranked <span class='rating'>" . clsVote::getRating($objPicture) . "</span> / " . clsVote::getMaxRating() . " (" . clsVote::getVoteCount($objPicture) . " votes)");
			}
		}

		/* Commenting code. */
		$arrComments = $objPicture->getComments();
		foreach($arrComments as $objComment)
		{
			$objComment = new clsComment($objComment->get('id'));
			$objCommentTemplate = new clsTemplate('comment');

			$objCommentTemplate->setText('TITLE',    $objComment->get('title') . ' ' . $objComment->getNewIcon($objUser));
			$objCommentTemplate->setText('USERNAME', $objComment->getUsername());
			$objCommentTemplate->setText('DATE',     time_to_text(strtotime($objComment->get('date'))));
			$objCommentTemplate->setText('TEXT',     bbcode_format($objComment->get('text')));

			if($objComment->canEdit($objUser))
				$objCommentTemplate->setText('TITLE', "<span class='editdelete'> [<a href='index.php?action=comment&subaction=edit&".$objPicture->getIDPair()."&".$objComment->getIDPair()."'>edit</a>]</span>");

			if($objComment->canDelete($objUser))
				$objCommentTemplate->setText('TITLE', "<span class='editdelete'> [<a href='index.php?action=comment&subaction=delete&".$objPicture->getIDPair()."&".$objComment->getIDPair()."'>delete</a>]</span>");

			$objPictureTemplate->setText('COMMENTS', $objCommentTemplate->get());

			/* Mark the comment as viewed */
			$objComment->setViewed($objUser);
		}

		if($objAlbum->canPostComment($objUser))
		{
			$objPictureTemplate->setText('POSTCOMMENT1', "<a href='index.php?action=comment&subaction=edit&".$objPicture->getIDPair()."' class='smalllink'>Post Comment</a>");

			if(sizeof($arrComments) != 0)
				$objPictureTemplate->setText('POSTCOMMENT2', "<a href='index.php?action=comment&subaction=edit&".$objPicture->getIDPair()."' class='smalllink'>Post Comment</a>");
		}

		if(sizeof($arrComments) == 0)
			$objPictureTemplate->setText('COMMENTS', "No comments on this picture!");

		print $objPictureTemplate->get();
	}
	else if($strSubAction == 'delete')
	{
		if(!$objAlbum->canDeletePicture($objUser))
			throw new Exception('exception_accessdenied');

		$objPicture->delete();
		$objPicture->save();

		if($objPrevPicture)
			header("Location: index.php?action=picture&" . $objPrevPicture->getIDPair());
		else if($objNextPicture)
			header("Location: index.php?action=picture&" . $objNextPicture->getIDPair());
		else
			header("Location: index.php?action=albums&" . $objAlbum->getIDPair());
	}

	if($strSubAction == 'edit')
	{
		if(!$objPicture->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		$objTemplate->setText('PAGETITLE', "Editing " . $objPicture->get('title'));

		$objEditTemplate = new clsTemplate('editpicture');

		$objEditTemplate->setText('HIDDEN', "<input type='hidden' name='action'    value='picture'>");
		$objEditTemplate->setText('HIDDEN', "<input type='hidden' name='subaction' value='save'>");
		$objEditTemplate->setText('HIDDEN', $objPicture->getHiddenField('id'));

		$objEditTemplate->setText('ID', $objPicture->get('id'));
		$objEditTemplate->setText('WIDTH', $objPicture->get('width'));
		$objEditTemplate->setText('HEIGHT', $objPicture->get('height'));
		$objEditTemplate->setText('IMAGE', $objPicture->getHtmlThumbnail(250, 250));
		$objEditTemplate->setText('ALBUM', $objAlbum->get('name'));
		$objEditTemplate->setText('TITLE', $objPicture->getTextField('title'));
		$objEditTemplate->setText('CAPTION', $objPicture->getTextArea('caption', 4, 45));
		$objEditTemplate->setText('CONFIRMED', $objPicture->getCheckNoJavascript('confirmed'));
		$objEditTemplate->setText('SUBMIT',$objPicture->getSubmit('Save'));

		print $objEditTemplate->get();
	}

	if($strSubAction == 'save')
	{
		if(!$objPicture->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		$objPicture->getFromRequest(array('id', 'title', 'caption', 'confirmed'));
		$objPicture->save();

		header("Location: index.php?action=picture&" . $objPicture->getIDPair());
	}

	if($strSubAction == 'vote')
	{
		if(!$objAlbum->canRate($objUser))
			throw new Exception('exception_accessdenied');
		if(!clsVote::canVote($objPicture, $objUser, $_SERVER['REMOTE_ADDR']))
			throw new Exception('exception_accessdenied');

		clsVote::recordVote($objPicture, $objUser, $_SERVER['REMOTE_ADDR'], $_REQUEST['vote']);

		header("Location: index.php?action=picture&" . $objPicture->getIDPair());
	}

	if($strSubAction == 'link')
	{
		$objTemplate->setText('PAGETITLE', "Linking to " . $objPicture->get('title'));

		/* Get the script path */
		$strBasePath = htmlentities("http://".$_SERVER['HTTP_HOST'].preg_replace("/\/[a-zA-Z0-9._]*$/", "", $_SERVER['PHP_SELF']) . "/");

		$strPicturePath = $strBasePath . "picture.php?".$objPicture->getIDPair();
		$strPicturePath2 = $strBasePath . "pictures/".$objPicture->get('id') . ".jpg";
		$strThumbnailPath = $strBasePath . "thumbnails/";
		$strPagePath =  $strBasePath . "index.php?action=picture&" . $objPicture->getIDPair();

		print "<h5>HTML to embed this image</h5>";
		print "<pre>";
		print "&lt;img src='$strPicturePath2'&gt;";
		print "</pre>";
		print "or";
		print "<pre>";
		print "&lt;img src='$strPicturePath'&gt;";
		print "</pre>";
		print "<hr>";

		print "<h5>HTML to link this image</h5>";
		print "<pre>";
		print "&lt;a href='$strPagePath'&gt;Your text here&lt;/a&gt;";
		print "</pre>";
		print "<hr>";

		print "<h5>HTML to link with a thumbnail</h5>";
		print "<ul><li>Change thumbnail size: ";
		print "<input type='text' value='64' size='3' onKeyUp='$(\"w1\").innerHTML=(isNumeric(this.value) ? this.value : \"64\"); $(\"w2\").innerHTML = $(\"w1\").innerHTML;'>";
		print "x";
		print "<input type='text' value='64' size='3' onKeyUp='$(\"h1\").innerHTML=(isNumeric(this.value) ? this.value : \"64\"); $(\"h2\").innerHTML = $(\"h1\").innerHTML;'>";
		print "</li></ul>";

		print "<tt>&lt;a href='$strPagePath'&gt;&lt;img src='$strThumbnailPath<span id='w2'>64</span>x<span id='h2'>64</span>/" . $objPicture->get('id') . ".jpg'&gt;&lt;/a&gt;</tt>";
		print "<p>or<p>";
		print "<tt>&lt;a href='$strPagePath'&gt;&lt;img src='$strPicturePath&action=tn&w=<span id='w1'>64</span>&amp;h=<span id='h1'>64</span>'&gt;&lt;/a&gt;</tt>";
		print "<p><hr>";

		print "<h5>Forum code to embed this image</h5>";
		print "<pre>";
		print "[img]{$strPicturePath2}[/img]";
		print "</pre>";
		print "or";
		print "<pre>";
		print "[img]{$strPicturePath}[/img]";
		print "</pre>";
		print "<hr>";

		print "<h5>Forum code to link this image</h5>";
		print "<pre>";
		print "[url=$strPagePath]Your text here[/url]";
		print "</pre>";
		print "<hr>";

		print "<h5>Forum code to link with a thumbnail</h5>";
		print "<ul><li>Change thumbnail size: ";
		print "<input type='text' value='64' size='3' onKeyUp='$(\"w3\").innerHTML=(isNumeric(this.value) ? this.value : \"64\"); $(\"w4\").innerHTML = $(\"w3\").innerHTML;'>";
		print "x";
		print "<input type='text' value='64' size='3' onKeyUp='$(\"h3\").innerHTML=(isNumeric(this.value) ? this.value : \"64\"); $(\"h4\").innerHTML = $(\"h3\").innerHTML;'>";
		print "</li></ul>";

		print "<tt>[url=$strPagePath][img]$strThumbnailPath<span id='w4'>64</span>x<span id='h4'>64</span>/" . $objPicture->get('id') . ".jpg[/img][/url]</tt>";
		print "<p>or<p>";
		print "<tt>[url=$strPagePath][img]$strPicturePath&tn=1&w=<span id='w3'>64</span>&amp;h=<span id='h3'>64</span>[/img][/url]</tt>";
		print "<hr>";

		print "<a href='index.php?action=picture&".$objPicture->getIDPair()."'>Back</a>";

	}
?>
