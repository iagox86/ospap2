<?php
	require_once('cls/clsTemplate.php');
	require_once('cls/clsUser.php');

	$objBreadcrumbs->add('Members', 'index.php?action=members');

	$objMember = new clsUser();
	$objMember->getFromRequest();
	$objMember->load();

	if($strSubAction == '') /* Display a list of users. */
	{
		$objTemplate->setText('PAGETITLE', "Members");
		$objMiniMenu->add('Groups', 'index.php?action=groups');

		$arrUsers = clsUser::getUserList();

		print "<table class='members'>";
		print "<tr>";
		print "<th width='150' align='left'>Name</td>";
		print "<th width='100'>Albums</td>";
		print "<th width='100'>Pictures</td>";
		print "</tr>";
		foreach($arrUsers as $objMember)
		{
			print "<tr>";
			print "<td><a href='index.php?action=members&subaction=view&".$objMember->getIDPair()."' class='nounderline'>" . $objMember->get('username') . "</a></td>";
			print "<td align='center'><a href='index.php?action=albums&subaction=useralbums&".$objMember->getIDPair()."' class='nounderline'>" . clsAlbum::getUserAlbumCount($objUser, $objMember) . "</a></td>";
			print "<td align='center'><a href='index.php?action=albums&subaction=userpictures&".$objMember->getIDPair()."' class='nounderline'>" . clsAlbum::getUserPictureCount($objUser, $objMember) . "</a></td>";
			print "</tr>";
		}
		print "</table>";
	}

	if($strSubAction == 'view') /* View details for a particular user. */
	{
		if(clsUser::canEdit($objMember, $objUser))
		{
			if($objMember->isNew())
			{
				$objMember->getFromRequest(array('id', 'username', 'password1', 'password2', 'email', 'is_advanced', 'show_empty', 'remember_filter', 'realname', 'location'));
				$objTemplate->setText('PAGETITLE', 'Registration');
				$objBreadcrumbs->add('Registration', "index.php?action=members&subaction=view");
			}
			else
			{
				$objTemplate->setText('PAGETITLE', 'Editing ' . $objMember->get('username'));
				$objBreadcrumbs->add('Registration', "index.php?action=members&subaction=view&".$objMember->getIDPair());
			}

			$objMemberTemplate = new clsTemplate('edituser');
			$objMemberTemplate->setText('HIDDEN', "<input type='hidden' name='action' value='members'>");
			$objMemberTemplate->setText('HIDDEN', "<input type='hidden' name='subaction' value='save'>");
			$objMemberTemplate->setText('HIDDEN', $objMember->getHiddenField('id'));

			if($objMember->isNew())
				$objMemberTemplate->setText('USERNAME', $objMember->getTextField('username'));
			else
				$objMemberTemplate->setText('USERNAME', $objMember->get('username'));
			$objMemberTemplate->setText('PASSWORD1', $objMember->getPasswordField('password1'));
			$objMemberTemplate->setText('PASSWORD2', $objMember->getPasswordField('password2'));
			$objMemberTemplate->setText('EMAIL', $objMember->getTextField('email'));
			$objMemberTemplate->setText('ADVANCED', $objMember->getCheckNoJavascript('is_advanced'));
			$objMemberTemplate->setText('SHOWEMPTY', $objMember->getCheckNoJavascript('show_empty'));
			$objMemberTemplate->setText('REMEMBERFILTER', $objMember->getCheckNoJavascript('remember_filter'));
			$objMemberTemplate->setText('REALNAME', $objMember->getTextField('realname'));
			$objMemberTemplate->setText('LOCATION', $objMember->getTextField('location'));
			$objMemberTemplate->setText('SAVE', $objMember->getSubmit('Save'));

			if(!$objMember->isNew())
				$objMemberTemplate->setText('BLANK', "(Blank not to change it.)");

			print $objMemberTemplate->get();
		}
		else
		{
			$objTemplate->setText('PAGETITLE', 'Viewing ' . $objMember->get('username'));
			$objBreadcrumbs->add($objMember->get('username'), "index.php?action=members&subaction=view&".$objMember->getIDPair());

			$objMemberTemplate = new clsTemplate('viewuser');
			$objMemberTemplate->setText('USERNAME', $objMember->get('username'));
			$objMemberTemplate->setText('REALNAME', $objMember->get('realname'));
			$objMemberTemplate->setText('LOCATION', $objMember->get('location'));

			print $objMemberTemplate->get();
		}
	}

	if($strSubAction == 'save') /* Save the user's details. */
	{
		if(!clsUser::canEdit($objMember, $objUser))
			throw new Exception('exception_accessdenied');

		$objMember->getFromRequest(array('id', 'username', 'password1', 'password2', 'email', 'is_advanced', 'show_empty', 'remember_filter', 'realname', 'location'));

		if($objMember->isNew())
		{
			$ret = $objMember->attemptCreate();
			if(is_string($ret))
			{
				$objMember->remove('password1');
				$objMember->remove('password2');
				header("Location: index.php?action=members&subaction=view&error=$ret&" . $objMember->getQueryString());
			}
			else
			{
				$objUser = $ret;
				$_SESSION['objUser'] = $objUser;
				clsAlbum::markSeen($objUser); 
				header("Location: index.php?message=register_successful");
			}
		}
		else
		{
			if(strlen($objMember->get('password1')))
			{
				$ret = $objMember->changePassword();
				if(is_string($ret))
				{
					header("Location: index.php?action=members&subaction=view&".$objMember->getIDPair()."&error=$ret");
					exit;
				}
			}
			$objMember->remove('password1');
			$objMember->remove('password2');
			$objMember->save();

			if($objMember->get('id') == $objUser->get('id'))
				$_SESSION['objUser'] = $objMember;

			header("Location: index.php?action=members&subaction=view&".$objMember->getIDPair()."&message=edit_successful");
		}

	}
?>
