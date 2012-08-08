<?php

	require_once('cls/clsAlbum.php');
	require_once('cls/clsComment.php');
	require_once('cls/clsPicture.php');

	$objComment = new clsComment();
	$objComment->getFromRequest();
	$objComment->load();

	$objPicture = new clsPicture();
	$objPicture->getFromRequest();
	$objPicture->load();

	$objAlbum = new clsAlbum($objPicture->get('album_id'));

	if($strSubAction == 'edit')
	{
		if($objComment->isNew() && !$objAlbum->canPostComment($objUser))
			throw new Exception('exception_accessdenied');

		if(!$objComment->canEdit($objUser))
			throw new Exception('exception_accessdenied');


		$objAlbum->addBreadcrumbs($objBreadcrumbs);
		$objBreadcrumbs->add($objPicture->get('name'), 'index.php?action=picture&'.$objPicture->getIDPair());
		$objBreadcrumbs->add('Post comment', 'comment.php?action=edit&'.$objPicture->getIDPair().'&'.$objComment->getIDPair());

		$objCommentTemplate = new clsTemplate('editcomment');
		$objCommentTemplate->setText('HIDDEN', $objComment->getHiddenField('id'));
		$objCommentTemplate->setText('HIDDEN', $objPicture->getHiddenField('id'));
		$objCommentTemplate->setText('HIDDEN', "<input type='hidden' name='action' value='comment'>");
		$objCommentTemplate->setText('HIDDEN', "<input type='hidden' name='subaction' value='save'>");

		if($objUser)
			$objCommentTemplate->setText('NAME', '<strong>' . $objUser->get('username') . '</strong>');
		else
			$objCommentTemplate->setText('NAME', $objComment->getTextField('username'));

		/* Set a default title if it's not present. */
		if(!$objComment->exists('title'))
			$objComment->set('title', 'Re: ' . $objPicture->get('title'), false);

		$objCommentTemplate->setText('TITLE', $objComment->getTextField('title'));
		$objCommentTemplate->setText('COMMENT', $objComment->getTextArea('text', 5, 60));
		$objCommentTemplate->setText('SUBMIT', $objComment->getSubmit('Save'));

		print $objCommentTemplate->get();
	}
	else if($strSubAction == 'save')
	{
		if($objComment->isNew() && !$objAlbum->canPostComment($objUser))
			throw new Exception('exception_accessdenied');

		if(!$objComment->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		$objComment->getFromRequest(array('id', 'username', 'title', 'text'));
		$objComment->set('picture_id', $objPicture->get('id'));
		$objComment->set('date', date('Y-m-d H:i:s'));

		if($objUser)
		{
			$objComment->set('user_id', $objUser->get('id'));
			$objComment->set('username', $objUser->get('username'));
		}

		$objComment->save();

		header("Location: index.php?action=picture&" . $objPicture->getIDPair());
	}
	else if($strSubAction == 'delete')
	{
		if(!$objComment->canDelete($objUser))
			throw new Exception('exception_accessdenied');

		$objComment->delete();
		$objComment->save();

		header("Location: index.php?action=picture&" . $objPicture->getIDPair());
	}
	else if($strSubAction = 'viewnew')
	{
		if(!$objUser)
			throw new Exception('exception_accessdenied');

		$arrComments = clsComment::getNewComments($objUser);

		foreach($arrComments as $objComment)
		{
			$objComment = new clsComment($objComment->get('id'));
			$objPicture = new clsPicture($objComment->get('picture_id'));

			$objCommentTemplate = new clsTemplate('newcomment');
			$objCommentTemplate->setText('IMAGE', "<a href='index.php?action=picture&".$objPicture->getIDPair()."'>".$objPicture->getHtmlThumbnail(128, 128)."</a>");
			$objCommentTemplate->setText('TITLE',	$objComment->get('title') . ' ' . $objComment->getNewIcon($objUser));
			$objCommentTemplate->setText('USERNAME', $objComment->getUsername());
			$objCommentTemplate->setText('DATE',	 time_to_text(strtotime($objComment->get('date'))));
			$objCommentTemplate->setText('TEXT',	 bbcode_format($objComment->get('text')));

			print $objCommentTemplate->get();

			/* Mark the comment as viewed */
			$objComment->setViewed($objUser);
		}
	}


?>
