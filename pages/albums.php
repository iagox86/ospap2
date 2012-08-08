<?php
	require_once('cls/clsAlbum.php');
	require_once('cls/clsGroup.php');
	require_once('cls/clsParameters.php');
	require_once('cls/clsPicture.php');
	require_once('cls/clsTemplate.php');
	require_once('cls/clsThumbnail.php');


	$objAlbum = new clsAlbum();
	$objAlbum->getFromRequest();
	$objAlbum->load();

	$objMember = new clsUser();
	$objMember->getFromRequest();
	$objMember->load();

	$objGroup = new clsGroup();
	$objGroup->getFromRequest();
	$objGroup->load();

	$arrSimplePermissions = array (-1=>"Don't change",
									0=>"Use same permission as parent",
									1=>"Public (anybody can see the album and post comments)",
									2=>"Public with rating (same as Public, but users can rate pictures)",
									3=>"Public with group posting (same as Public, except group members can post pictures)",
									4=>"Private (only group members can see)",
									5=>"Private with rating (same as Private, but users can rate pictures)",
									6=>"Private with group posting (same as Private, except group members can post pictures)" 
								);

	if($strSubAction == '' && $objMember->isNew()) /* Display all the sub-albums and pictures in an album. */
	{
		if(!$objAlbum->canView($objUser))
			throw new Exception('exception_accessdenied');

		/* Do they have access? */
		if(!$objAlbum->canView($objUser))
			throw new Exception('exception_accessdenied');

		/* Are they filtering? */
		if($objUser && $objUser->get('remember_filter') && $objUser->get('filter_user'))
			header("Location: index.php?action=albums&subaction=useralbums&user_id=" . $objUser->get('filter_user'));

		$objBreadcrumbs->add('Albums', 'index.php?action=albums');
		$objAlbum->addBreadcrumbs($objBreadcrumbs, false);

		/* Editing the album. */
		if(!$objAlbum->isNew() && $objAlbum->canEdit($objUser))
			$objMiniMenu->add('Edit', 'index.php?action=albums&subaction=edit&' . $objAlbum->getIDPair());

		/* Deleting the album. */
		if(!$objAlbum->isNew() && $objAlbum->canEdit($objUser))
			$objMiniMenu->add('Delete', 'index.php?action=albums&subaction=delete&' . $objAlbum->getIDPair());

		/* Moving the album. */
		if(!$objAlbum->isNew() && $objAlbum->canEdit($objUser))
			$objMiniMenu->add('Move', 'index.php?action=albums&subaction=move&' . $objAlbum->getIDPair());

		/* Creating an album/sub album. */
		if($objUser && $objAlbum->isNew())
			$objMiniMenu->add('Create Album', 'index.php?action=albums&subaction=edit');
		else if($objUser && $objAlbum->canCreateSubalbum($objUser))
			$objMiniMenu->add('Create Sub-album', 'index.php?action=albums&subaction=edit&album_album_id=' . $objAlbum->get('id'));

		/* Posting a picture. */
		if($objAlbum->canPostPicture($objUser))
			$objMiniMenu->add('Post Picture', 'index.php?action=upload&' . $objAlbum->getIDPair());

		if($objUser)
			$objMiniMenu->add('Mark All as Seen', 'index.php?action=albums&subaction=seen&' . $objAlbum->getIDPair());

		$objTemplate->setText('PAGETITLE', 'Albums' . ($objAlbum->get('id') == 0 ? '' : " -- " . $objAlbum->get('name')));

		/* Get the array of sub albums. */
		$arrSubAlbums = $objAlbum->getSubAlbums($objUser);

		/* This script allows the albums to be expanded/hidden. */
		$objTemplate->setText('SCRIPT', clsAlbum::getSubAlbumScript());
		$objTemplate->setText('SCRIPT', clsAlbum::getAllAlbumScript($arrSubAlbums));

		/* Display the caption  */
		if(strlen($objAlbum->get('caption')))
			print "<span class='albumcaption'>" . bbcode_format($objAlbum->get('caption')) . "</span><hr>";

		/* If it's the base album, allow filtering by user. */
		if($objAlbum->isNew())
			print $objAlbum->getUserFilter("Filter by user") . "<hr>";

		/* Display the expand/contract all buttons. */
		print "<a href='#' onClick='expand_all()' class='nounderline'><img src='images/expand.png'> Expand All</a><br>";
		print "<a href='#' onClick='contract_all()' class='nounderline'><img src='images/contract.png'> Hide All</a><br>";
		print "<br>";

		/* Display any sub-albums. */
		clsAlbum::displayAlbums($arrSubAlbums, $objUser);

		/* Display any pictures. */
		clsPicture::displayPictures($objAlbum->getPictures(), $objAlbum->get('id') ? true : false);
	}

	if($strSubAction == 'useralbums') /* Display all the sub-albums for the particular user. */
	{
		if($objUser && $objUser->get('remember_filter'))
		{
			$objUser->set('filter_user', $objMember->get('id'));
			$objUser->save();
		}

		if(!$objMember || $objMember->isNew())
		{
			header("Location: index.php?action=albums");
			exit;
		}

		$objTemplate->setText('PAGETITLE', $objMember->get('username') . "'s Albums");

		$objBreadcrumbs->add('Members', 'index.php?action=members');
		$objBreadcrumbs->add($objMember->get('username'), 'index.php?action=members&subaction=view&' . $objGroup->getIDPair());
		$objBreadcrumbs->add('Albums', "index.php?action=albums&subaction=memberalbums" . $objMember->getIDPair());

		$arrAlbums = clsAlbum::getUserAlbums($objUser, $objMember);

		/* This script allows the albums to be expanded/hidden. */
		$objTemplate->setText('SCRIPT', clsAlbum::getSubAlbumScript());
		$objTemplate->setText('SCRIPT', clsAlbum::getAllAlbumScript($arrAlbums));

		/* Display the expand/contract all buttons. */
		print "<a href='#' onClick='expand_all()' class='nounderline'><img src='images/expand.png'> Expand All</a><br>";
		print "<a href='#' onClick='contract_all()' class='nounderline'><img src='images/contract.png'> Hide All</a><br>";
		print "<br>";

		/* Allow filtering by user. */
		print $objAlbum->getUserFilter("Show all albums") . "<hr>";

		/* Display any sub-albums. */
		clsAlbum::displayAlbums($arrAlbums, $objUser);
	}

	if($strSubAction == 'groupalbums') /* Display all sub-albums for the particular group. */
	{
		$objTemplate->setText('PAGETITLE', "Albums in " . $objGroup->get('name'));

		$objBreadcrumbs->add('Groups', 'index.php?action=groups');
		$objBreadcrumbs->add($objGroup->get('name'), 'index.php?action=groups&subaction=view&' . $objGroup->getIDPair());
		$objBreadcrumbs->add('Albums', "index.php?action=albums&subaction=groupalbums" . $objGroup->getIDPair());

		$arrAlbums = clsGroup::getAlbumsByGroup($objUser, $objGroup);

		/* This script allows the albums to be expanded/hidden. */
		$objTemplate->setText('SCRIPT', clsAlbum::getSubAlbumScript());
		$objTemplate->setText('SCRIPT', clsAlbum::getAllAlbumScript($arrAlbums));

		/* Display the expand/contract all buttons. */
		print "<a href='#' onClick='expand_all()' class='nounderline'><img src='images/expand.png'> Expand All</a><br>";
		print "<a href='#' onClick='contract_all()' class='nounderline'><img src='images/contract.png'> Hide All</a><br>";
		print "<br>";

		/* Display any sub-albums. */
		clsAlbum::displayAlbums($arrAlbums, $objUser);
	}

	if($strSubAction == 'userpictures') /* Display all the pictures for the particular user. */
	{
		if(!$objMember || $objMember->isNew())
			$objMember = $objUser;
		$objTemplate->setText('PAGETITLE', $objMember->get('username') . "'s Pictures");

		$objBreadcrumbs->add('Members', 'index.php?action=members');
		$objBreadcrumbs->add($objMember->get('username'), 'index.php?action=members&subaction=view&' . $objGroup->getIDPair());
		$objBreadcrumbs->add("Pictures", "index.php?action=albums&subaction=userpictures&" . $objMember->getIDPair());

		clsPicture::displayPictures(clsAlbum::getPicturesByUser($objUser, $objMember));
	}

	if($strSubAction == 'grouppictures') /* Display all the pictures for the particular group. */
	{
		$objTemplate->setText('PAGETITLE', "Pictures in " . $objGroup->get('name'));

		$objBreadcrumbs->add('Groups', 'index.php?action=groups');
		$objBreadcrumbs->add($objGroup->get('name'), 'index.php?action=groups&subaction=view&' . $objGroup->getIDPair());
		$objBreadcrumbs->add("Pictures", "index.php?action=albums&subaction=userpictures&" . $objMember->getIDPair());

		clsPicture::displayPictures(clsGroup::getPicturesByGroup($objUser, $objGroup));
	}



	if($strSubAction == 'newpictures') /* Display all the pictures that the user hasn't seen. */
	{
		if($objUser == null)
			throw new Exception('exception_accessdenied');

		$objTemplate->setText('PAGETITLE', "New Pictures");
		$objMiniMenu->add('Mark all as seen', "index.php?action=albums&subaction=seen");

		$objBreadcrumbs->add("New pictures", "index.php?action=albums&subaction=newpictures&" . $objMember->getIDPair());

		clsPicture::displayPictures(clsPicture::getNewPictures($objUser));
	}

	if($strSubAction == 'save') /* Save the edited settings. */
	{
		$blnGood = true;

		if(!$objUser)
			throw new Exception('exception_notloggedin');
		if(!$objAlbum->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		/* Get the user's submitted changes. */
		$objAlbum = new clsAlbum();
		$objAlbum->getFromRequest(array('id', 'album_id', 'group_id', 'name', 'caption', 'date', 'export_tag', 'max_height', 'max_width', 'mime', 'simple_permissions'));

		if($objAlbum->get('max_width') > MAX_X || $objAlbum->get('max_width') <= 0 || !is_numeric($objAlbum->get('max_width')))
		{
			$blnGood = false;
			$objTemplate->setText('ERROR', "Width must be a number between 1 and " . MAX_X . ".<br>");
		}

		if($objAlbum->get('max_height') > MAX_X || $objAlbum->get('max_height') <= 0 || !is_numeric($objAlbum->get('max_height')))
		{
			$blnGood = false;
			$objTemplate->setText('ERROR', "Height must be a number between 1 and " . MAX_X . ".<br>");
		}


		if(array_search($objAlbum->get('mime'), array('image/jpeg', 'image/png', 'image/gif')) === false)
		{
			$blnGood = false;
			$objTemplate->setText('ERROR', "Mime choices are image/jpeg, image/png, and image/gif.<br>");
		}

		/* Validate fields. */
		if($blnGood)
		{
			if($objAlbum->isNew())
				$objAlbum->set('user_id', $objUser->get('id'));

			if(!$objAlbum->exists('simple_permissions'))
			{
				$objAlbumGuest = clsAlbum::getPolicyFromRequest('albumpolicy_guest', $objUser);
				$objAlbumGuest->save();
				$objAlbum->set('albumpolicy_guest_id', $objAlbumGuest->get('id'));
		
				$objAlbumMember = clsAlbum::getPolicyFromRequest('albumpolicy_member', $objUser);
				$objAlbumMember->save();
				$objAlbum->set('albumpolicy_member_id', $objAlbumMember->get('id'));
		
				$objAlbumGroup = clsAlbum::getPolicyFromRequest('albumpolicy_group', $objUser);
				$objAlbumGroup->save();
				$objAlbum->set('albumpolicy_group_id', $objAlbumGroup->get('id'));
			}
			else
			{
				$objAlbumGuest = clsAlbum::getPolicyFromRequest('albumpolicy_guest', $objUser);
				$objAlbumMember = clsAlbum::getPolicyFromRequest('albumpolicy_member', $objUser);
				$objAlbumGroup = clsAlbum::getPolicyFromRequest('albumpolicy_group', $objUser);


				switch($objAlbum->get('simple_permissions'))
				{
					/* -1=>"Don't change" */
					case -1:
						/* Do nothing. */
					break;

					case 0:
						/* 0=>"Use same permission as parent",*/
						$objAlbumGroup->set('allow_post_picture',     INHERIT);
						$objAlbumGroup->set('allow_post_comment',     INHERIT);
						$objAlbumGroup->set('allow_rate',             INHERIT);
						$objAlbumGroup->set('allow_view',             INHERIT);
						$objAlbumGroup->set('allow_delete_picture',   INHERIT);
						$objAlbumGroup->set('allow_create_subalbum',  INHERIT);

						$objAlbumMember->set('allow_post_picture',    INHERIT);
						$objAlbumMember->set('allow_post_comment',    INHERIT);
						$objAlbumMember->set('allow_rate',            INHERIT);
						$objAlbumMember->set('allow_view',            INHERIT);
						$objAlbumMember->set('allow_delete_picture',  INHERIT);
						$objAlbumMember->set('allow_create_subalbum', INHERIT);

						$objAlbumGuest->set('allow_post_picture',     INHERIT);
						$objAlbumGuest->set('allow_post_comment',     INHERIT);
						$objAlbumGuest->set('allow_rate',             INHERIT);
						$objAlbumGuest->set('allow_view',             INHERIT);
						$objAlbumGuest->set('allow_delete_picture',   INHERIT);
						$objAlbumGuest->set('allow_create_subalbum',  INHERIT);
					break;

					case 1:
						/* 1=>"Public (anybody can see the album and post comments)",*/
						$objAlbumGroup->set('allow_view',             YES);
						$objAlbumGroup->set('allow_rate',             NO);
						$objAlbumGroup->set('allow_post_picture',     NO);
						$objAlbumGroup->set('allow_post_comment',     YES);
						$objAlbumGroup->set('allow_delete_picture',   NO);
						$objAlbumGroup->set('allow_create_subalbum',  NO);

						$objAlbumMember->set('allow_view',            YES);
						$objAlbumMember->set('allow_rate',            NO);
						$objAlbumMember->set('allow_post_picture',    NO);
						$objAlbumMember->set('allow_post_comment',    YES);
						$objAlbumMember->set('allow_delete_picture',  NO);
						$objAlbumMember->set('allow_create_subalbum', NO);

						$objAlbumGuest->set('allow_view',             YES);
						$objAlbumGuest->set('allow_rate',             NO);
						$objAlbumGuest->set('allow_post_picture',     NO);
						$objAlbumGuest->set('allow_post_comment',     YES);
						$objAlbumGuest->set('allow_delete_picture',   NO);
						$objAlbumGuest->set('allow_create_subalbum',  NO);
					break;

					case 2:
						/* 2=>"Public with rating (same as Public, but users can rate pictures)",*/
						$objAlbumGroup->set('allow_view',             YES);
						$objAlbumGroup->set('allow_rate',             YES);
						$objAlbumGroup->set('allow_post_picture',     NO);
						$objAlbumGroup->set('allow_post_comment',     YES);
						$objAlbumGroup->set('allow_delete_picture',   NO);
						$objAlbumGroup->set('allow_create_subalbum',  NO);

						$objAlbumMember->set('allow_view',            YES);
						$objAlbumMember->set('allow_rate',            YES);
						$objAlbumMember->set('allow_post_picture',    NO);
						$objAlbumMember->set('allow_post_comment',    YES);
						$objAlbumMember->set('allow_delete_picture',  NO);
						$objAlbumMember->set('allow_create_subalbum', NO);

						$objAlbumGuest->set('allow_view',             YES);
						$objAlbumGuest->set('allow_rate',             NO);
						$objAlbumGuest->set('allow_post_picture',     NO);
						$objAlbumGuest->set('allow_post_comment',     YES);
						$objAlbumGuest->set('allow_delete_picture',   NO);
						$objAlbumGuest->set('allow_create_subalbum',  NO);
					break;

					case 3:
						/* 3=>"Public with group posting (same as Public, except group members can post pictures)",*/
						$objAlbumGroup->set('allow_view',             YES);
						$objAlbumGroup->set('allow_rate',             NO);
						$objAlbumGroup->set('allow_post_picture',     YES);
						$objAlbumGroup->set('allow_post_comment',     YES);
						$objAlbumGroup->set('allow_delete_picture',   NO);
						$objAlbumGroup->set('allow_create_subalbum',  YES);

						$objAlbumMember->set('allow_view',            YES);
						$objAlbumMember->set('allow_rate',            NO);
						$objAlbumMember->set('allow_post_picture',    NO);
						$objAlbumMember->set('allow_post_comment',    YES);
						$objAlbumMember->set('allow_delete_picture',  NO);
						$objAlbumMember->set('allow_create_subalbum', NO);

						$objAlbumGuest->set('allow_view',             YES);
						$objAlbumGuest->set('allow_rate',             NO);
						$objAlbumGuest->set('allow_post_picture',     NO);
						$objAlbumGuest->set('allow_post_comment',     YES);
						$objAlbumGuest->set('allow_delete_picture',   NO);
						$objAlbumGuest->set('allow_create_subalbum',  NO);
					break;

					case 4:
						/* 4=>"Private (only group members can see)",*/
						$objAlbumGroup->set('allow_view',             YES);
						$objAlbumGroup->set('allow_rate',             NO);
						$objAlbumGroup->set('allow_post_picture',     NO);
						$objAlbumGroup->set('allow_post_comment',     YES);
						$objAlbumGroup->set('allow_delete_picture',   NO);
						$objAlbumGroup->set('allow_create_subalbum',  NO);

						$objAlbumMember->set('allow_view',            NO);
						$objAlbumMember->set('allow_rate',            NO);
						$objAlbumMember->set('allow_post_picture',    NO);
						$objAlbumMember->set('allow_post_comment',    NO);
						$objAlbumMember->set('allow_delete_picture',  NO);
						$objAlbumMember->set('allow_create_subalbum', NO);

						$objAlbumGuest->set('allow_view',             NO);
						$objAlbumGuest->set('allow_rate',             NO);
						$objAlbumGuest->set('allow_post_picture',     NO);
						$objAlbumGuest->set('allow_post_comment',     NO);
						$objAlbumGuest->set('allow_delete_picture',   NO);
						$objAlbumGuest->set('allow_create_subalbum',  NO);
					break;

					case 5:
						/* 5=>"Private with rating (same as Private, but users can rate pictures)", */
						$objAlbumGroup->set('allow_view',             YES);
						$objAlbumGroup->set('allow_rate',             YES);
						$objAlbumGroup->set('allow_post_picture',     YES);
						$objAlbumGroup->set('allow_post_comment',     YES);
						$objAlbumGroup->set('allow_delete_picture',   NO);
						$objAlbumGroup->set('allow_create_subalbum',  YES);

						$objAlbumMember->set('allow_view',            NO);
						$objAlbumMember->set('allow_rate',            NO);
						$objAlbumMember->set('allow_post_picture',    NO);
						$objAlbumMember->set('allow_post_comment',    NO);
						$objAlbumMember->set('allow_delete_picture',  NO);
						$objAlbumMember->set('allow_create_subalbum', NO);

						$objAlbumGuest->set('allow_view',             NO);
						$objAlbumGuest->set('allow_rate',             NO);
						$objAlbumGuest->set('allow_post_picture',     NO);
						$objAlbumGuest->set('allow_post_comment',     NO);
						$objAlbumGuest->set('allow_delete_picture',   NO);
						$objAlbumGuest->set('allow_create_subalbum',  NO);
					break;

					case 6:
						/* 6=>"Private with group posting (same as Private, except group members can post pictures)" */
						$objAlbumGroup->set('allow_view',             YES);
						$objAlbumGroup->set('allow_rate',             NO);
						$objAlbumGroup->set('allow_post_picture',     YES);
						$objAlbumGroup->set('allow_post_comment',     YES);
						$objAlbumGroup->set('allow_delete_picture',   NO);
						$objAlbumGroup->set('allow_create_subalbum',  YES);

						$objAlbumMember->set('allow_view',            NO);
						$objAlbumMember->set('allow_rate',            NO);
						$objAlbumMember->set('allow_post_picture',    NO);
						$objAlbumMember->set('allow_post_comment',    NO);
						$objAlbumMember->set('allow_delete_picture',  NO);
						$objAlbumMember->set('allow_create_subalbum', NO);

						$objAlbumGuest->set('allow_view',             NO);
						$objAlbumGuest->set('allow_rate',             NO);
						$objAlbumGuest->set('allow_post_picture',     NO);
						$objAlbumGuest->set('allow_post_comment',     NO);
						$objAlbumGuest->set('allow_delete_picture',   NO);
						$objAlbumGuest->set('allow_create_subalbum',  NO);
					break;
				}

				$objAlbumGuest->save();
				$objAlbumMember->save();
				$objAlbumGroup->save();

				$objAlbum->set('albumpolicy_guest_id', $objAlbumGuest->get('id'));
				$objAlbum->set('albumpolicy_member_id', $objAlbumMember->get('id'));
				$objAlbum->set('albumpolicy_group_id', $objAlbumGroup->get('id'));

				$objAlbum->remove('simple_permissions');
			}
	
			$objAlbum->save();
	
			header('Location: index.php?action=albums&' . $objAlbum->getIDPair());
		}
		else
		{
			$strSubAction = 'edit';
		}
	}

	if($strSubAction == 'delete')
	{
		$objAlbum->getFromRequest();
		$objAlbum->load();

		if(!$objAlbum->canEdit($objUser))
			throw new exception('exception_accessdenied');

		$objAlbum->delete();
		$objAlbum->save();

		header('Location: index.php?action=albums');
	}

	if($strSubAction == 'move')
	{
		if(!$objAlbum->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		/* Set the breadcrumbs and title. */
		$objBreadcrumbs->add('Albums', 'index.php?action=albums');
		$objAlbum->addBreadcrumbs($objBreadcrumbs, false);
		$objBreadcrumbs->add('Move', 'index.php?action=albums&subaction=move');
		$objTemplate->setText('PAGETITLE', "Moving an Album");

		print "<form action='index.php' method='get'>";
		print "<input type='hidden' name='action' value='albums'>";
		print "<input type='hidden' name='subaction' value='move2'>";
		print $objAlbum->getHiddenField('id');

		print "Move the album to: " . $objAlbum->getCombo('album_id', clsDB::getOptionsFromList($objAlbum->getPotentialParents($objUser), 'name', 'id', "[no parent]")) . "<br>";
		print $objAlbum->getSubmit('Move');


		print "</form>";
	}

	if($strSubAction == 'move2')
	{
		if(!$objAlbum->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		$objAlbum->getFromRequest();
		$objParent = new clsAlbum($objAlbum->get('album_id'));

		if(!$objParent->canCreateSubalbum($objUser))
			throw new Exception('exception_accessdenied');

		/* TODO: Make sure albums can't be moved to their own children. */
		$objAlbum->save();

		header("Location: index.php?action=albums&" . $objAlbum->getIDPair());
	}

	if($strSubAction == 'seen') /* Mark all pictures as seen. */
	{
		clsAlbum::markSeen($objUser, $objAlbum);

		if($objAlbum->isNew())
			header("Location: index.php");
		else
			header("Location: index.php?action=albums&" . $objAlbum->getIDPair());
	}

	if($strSubAction == 'edit') /* Edit the settings on an album. */
	{
		if(!$objAlbum->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		/* Get the parent album based on the album_id that the user specified. */
		$objAlbum->getFromRequest();
		$objParent = new clsAlbum($objAlbum->get('album_id'));
		if(!$objParent->canCreateSubalbum($objUser))
			throw new Exception('exception_accessdenied');

		/* Set the breadcrumbs and title. */
		$objBreadcrumbs->add('Albums', 'index.php?action=albums');
		$objAlbum->addBreadcrumbs($objBreadcrumbs, false);
		$objBreadcrumbs->add('Edit', 'index.php?action=albums&subaction=edit');
		$objTemplate->setText('PAGETITLE', "Editing an Album");

		/* Set up the option list. */
		if($objParent->isNew())
			$arrOptions = array(NO=>"No", YES=>"Yes");
		else
			$arrOptions = array(INHERIT=>"Inherit", NO=>"No", YES=>"Yes");

		/* On a new album, set the default policies. After this, the policy objects should never change. If the 
		 * policy is 0, which may mean that it was an import, also set the default policies. */
		if($objAlbum->isNew() || $objAlbum->get('albumpolicy_guest_id') == 0)
			$objAlbum->setDefaultPolicies($objUser);

		$objAlbumGuest  = $objAlbum->getForeignObject('albumpolicy', 'guest');
		$objAlbumMember = $objAlbum->getForeignObject('albumpolicy', 'member');
		$objAlbumGroup  = $objAlbum->getForeignObject('albumpolicy', 'group');

		/* A tiny kludge, but it could be worse. This allows us to use these objects without conflicting names. */
		$objAlbumGuest->setName('albumpolicy_guest');
		$objAlbumMember->setName('albumpolicy_member');
		$objAlbumGroup->setName('albumpolicy_group');

		/* Set the default width, height, and mime. */
		if($objAlbum->get('max_width',  DEFAULT_X,    true) == '')
			$objAlbum->set('max_width', DEFAULT_X);

		if($objAlbum->get('max_height', DEFAULT_Y,    true) == '')
			$objAlbum->set('max_height', DEFAULT_Y);

		if($objAlbum->get('mime',       DEFAULT_MIME, true) == '')
			$objAlbum->set('mime', DEFAULT_MIME);

		/* The template that looks after the edit page. */
		$objEditTemplate = new clsTemplate('editalbum');

		$objEditTemplate->setText('HIDDEN', $objAlbum->getHiddenField('id'));
		$objEditTemplate->setText('HIDDEN', $objAlbumGuest->getHiddenField('id'));
		$objEditTemplate->setText('HIDDEN', $objAlbumMember->getHiddenField('id'));
		$objEditTemplate->setText('HIDDEN', $objAlbumGroup->getHiddenField('id'));
		$objEditTemplate->setText('HIDDEN', $objAlbum->getHiddenField('album_id'));
		$objEditTemplate->setText('HIDDEN', "<input type='hidden' name='action' value='albums'>");
		$objEditTemplate->setText('HIDDEN', "<input type='hidden' name='subaction' value='save'>");
		$objEditTemplate->setText('MAXWIDTH',  MAX_X);
		$objEditTemplate->setText('MAXHEIGHT', MAX_Y);


		$objEditTemplate->setText('NAME', $objAlbum->getTextField('name', new clsParameters('SIZE', 40)));
		$objEditTemplate->setText('PARENT', $objParent->isNew() ? "n/a" : $objParent->get('name'));
		$objEditTemplate->setText('GROUP', $objAlbum->getCombo('group_id', clsDB::getOptionsFromList(clsGroup::getGroups($objUser), 'name', 'id', "No group.")));
		$objEditTemplate->setText('CAPTION', $objAlbum->getTextArea('caption', 4, 45));
		$objEditTemplate->setText('EXPORTKEY', $objAlbum->getTextField('export_tag', new clsParameters('SIZE', 4)));
		$objEditTemplate->setText('WIDTH', $objAlbum->getTextField('max_width', new clsParameters('SIZE', 3)));
		$objEditTemplate->setText('HEIGHT', $objAlbum->getTextField('max_height', new clsParameters('SIZE', 3)));
		$objEditTemplate->setText('MIME', $objAlbum->getTextField('mime'));

		$strGroup = '';
		$strGroup .= "View pictures? "    . $objAlbumGroup->getCombo('allow_view', $arrOptions)            . "<br>";
		$strGroup .= "Rate pictures? "    . $objAlbumGroup->getCombo('allow_rate', $arrOptions)            . "<br>";
		$strGroup .= "Post pictures? "    . $objAlbumGroup->getCombo('allow_post_picture', $arrOptions)    . "<br>";
		$strGroup .= "Post comments? "    . $objAlbumGroup->getCombo('allow_post_comment', $arrOptions)    . "<br>";
		$strGroup .= "Delete pictures? "  . $objAlbumGroup->getCombo('allow_delete_picture', $arrOptions)  . "<br>";
		$strGroup .= "Create sub-albums?" . $objAlbumGroup->getCombo('allow_create_subalbum', $arrOptions) . "<br>";
		$objEditTemplate->setText('GROUPPERMISSIONS', $strGroup);

		$strMember = '';
		$strMember .= "View pictures? "    . $objAlbumMember->getCombo('allow_view', $arrOptions)            . "<br>";
		$strMember .= "Rate pictures? "    . $objAlbumMember->getCombo('allow_rate', $arrOptions)            . "<br>";
		$strMember .= "Post pictures? "    . $objAlbumMember->getCombo('allow_post_picture', $arrOptions)    . "<br>";
		$strMember .= "Post comments? "    . $objAlbumMember->getCombo('allow_post_comment', $arrOptions)    . "<br>";
		$strMember .= "Delete pictures? "  . $objAlbumMember->getCombo('allow_delete_picture', $arrOptions)  . "<br>";
		$strMember .= "Create sub-albums?" . $objAlbumMember->getCombo('allow_create_subalbum', $arrOptions) . "<br>";
		$objEditTemplate->setText('MEMBERPERMISSIONS', $strMember);

		$strGuest = '';
		$strGuest .= "View pictures? "    . $objAlbumGuest->getCombo('allow_view', $arrOptions)            . "<br>";
		$strGuest .= "Rate pictures? "    . $objAlbumGuest->getCombo('allow_rate', $arrOptions)            . "<br>";
		$strGuest .= "Post pictures? "    . $objAlbumGuest->getCombo('allow_post_picture', $arrOptions)    . "<br>";
		$strGuest .= "Post comments? "    . $objAlbumGuest->getCombo('allow_post_comment', $arrOptions)    . "<br>";
		$strGuest .= "Delete pictures? "  . $objAlbumGuest->getCombo('allow_delete_picture', $arrOptions)  . "<br>";
		$strGuest .= "Create sub-albums?" . $objAlbumGuest->getCombo('allow_create_subalbum', $arrOptions) . "<br>";
		$objEditTemplate->setText('GUESTPERMISSIONS', $strGuest);

		if($objAlbum->isNew())
			unset($arrSimplePermissions[-1]);
		if($objParent->isNew())
			unset($arrSimplePermissions[0]);
		$arrKeys = array_keys($arrSimplePermissions);
		$objAlbum->set('simple_permissions', $arrKeys[0]);
		$arrSimplePermissions[$arrKeys[0]] .= " <em>(recommended)</em>";
		$objEditTemplate->setText('SIMPLEPERMISSIONS', $objAlbum->getRadioString('simple_permissions', $arrSimplePermissions));

		$objEditTemplate->setText('SUBMIT', $objAlbum->getSubmit('Save'));

		print $objEditTemplate->get();
	}

?>
