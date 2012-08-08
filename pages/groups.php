<?php
	require_once('cls/clsGroup.php');

	require_once('cls/clsTemplate.php');
	require_once('cls/clsUser.php');

	$objGroup = new clsGroup();
	$objGroup->getFromRequest();
	$objGroup->load();

	$objMember = new clsUser();
	$objMember->getFromRequest();
	$objMember->load();

	$objBreadcrumbs->add('Groups', 'index.php?action=groups');
	if(!$objGroup->isNew())
		$objBreadcrumbs->add($objGroup->get('name'), 'index.php?action=groups&subaction=view&' . $objGroup->getIDPair());

	if($strSubAction == '') /* Display a list of groups. */
	{
		$objTemplate->setText('PAGETITLE', "Groups");

		if($objUser)
			$objMiniMenu->add('Create', 'index.php?action=groups&subaction=edit');

		$arrGroups = clsGroup::getGroupList($objUser);

		print "<table class='groups'>";
		print "<tr>";
		print "<th width='300' align='left'>Name</td>";
		print "<th width='150' align='left'>Founder</td>";
		print "<th width='75'>Members</td>";
		print "<th width='75'>Albums</td>";
		print "<th width='75'>Pictures</td>";
		print "</tr>";

		foreach($arrGroups as $objGroup)
		{
			$objFounder = $objGroup->getForeignObject('user');
			$isMember = $objGroup->isMember($objUser);
			$isPotential = $objGroup->isRequestedMember($objUser) || $objGroup->isInvitedMember($objUser);

			print "<tr>";
			print "	<td>";
			if($isMember)
				print "<strong>";
			if($isPotential)
				print "<em>";


			print "		<a href='index.php?action=groups&subaction=view&".$objGroup->getIDPair()."' class='nounderline'>" . $objGroup->get('name') . "</a></td>";

			if($isPotential)
				print "</em>";
			if($isMember)
				print "</strong>";
			print "	</td>";

			print "<td><a href='index.php?action=members&subaction=view&" . $objFounder->getIDPair() . "'>" . $objFounder->get('username') . "</a></td>";

			print "<td align='center'>" . sizeof($objGroup->getMembers()) . "</td>";
			print "<td align='center'><a href='index.php?action=albums&subaction=groupalbums&" . $objGroup->getIDPair() . "'>" . sizeof(clsGroup::getAlbumsByGroup($objUser, $objGroup)) . "</a></td>";
			print "<td align='center'><a href='index.php?action=albums&subaction=grouppictures&" . $objGroup->getIDPair() . "'>" . sizeof(clsGroup::getPicturesByGroup($objUser, $objGroup)) . "</a></td>";
			print "</tr>";
		}
		print "</table>";
	}

	if($strSubAction == 'view') /* View details for a particular group. */
	{
		if($objGroup->get('is_hidden') && !$objGroup->isMemberOrPotential($objUser) && (!$objUser || !$objUser->get('is_admin')))
			throw new Exception('exception_accessdenied');

		$objTemplate->setText('PAGETITLE', "Viewing Group: " . $objGroup->get('name'));

		$objMiniMenu->add('List', 'index.php?action=groups');

		/* The user is not a member of the group. */
		if(!$objGroup->isMemberOrPotential($objUser))
		{
			$objMiniMenu->add('Join', 'index.php?action=groups&subaction=join&' . $objGroup->getIDPair());
		}

		/* The user has requested a join, but it hasn't happened yet. */
		if($objGroup->isRequestedMember($objUser))
		{
			$objMiniMenu->add('Cancel Join Request', 'index.php?action=groups&subaction=cancel&' . $objGroup->getIDPair());
		}

		/* The user has been invited, but hasn't accepted/turned it down yet. */
		if($objGroup->isInvitedMember($objUser))
		{
			$objMiniMenu->add('Accept Invitation', 'index.php?action=groups&subaction=accept&' . $objGroup->getIDPair());
			$objMiniMenu->add('Decline Invitation', 'index.php?action=groups&subaction=decline&' . $objGroup->getIDPair());
		}

		/* The user is a full member of the group but is not the founder. */
		if($objGroup->isMember($objUser) && !$objGroup->isFounder($objUser))
		{
			$objMiniMenu->add('Leave', 'index.php?action=groups&subaction=leave&' . $objGroup->getIDPair());
		}

		/* The user is a full member of the group. */
		if($objGroup->isMember($objUser))
		{
			$objMiniMenu->add('Invite', 'index.php?action=groups&subaction=invite&' . $objGroup->getIDPair());
		}

		/* The user is the founder of the group. */
		if($objGroup->isFounder($objUser))
		{
/* TODO: Add disband later. */
//			$objMiniMenu->add('Disband', 'index.php?action=groups&subaction=disband&' . $objGroup->getIDPair());
		}

		/* The user can edit the group. */
		if($objGroup->canEdit($objUser))
		{
			$objMiniMenu->add('Edit', 'index.php?action=groups&subaction=edit&' . $objGroup->getIDPair());
		}


		$objGroupTemplate = new clsTemplate('viewgroup');
		$objGroupTemplate->setText('NAME', $objGroup->get('name'));
		$objGroupTemplate->setText('FOUNDER', $objGroup->getFrom('user', 'username'));
		$objGroupTemplate->setText('ISPRIVATE', $objGroup->get('is_private') ? "Yes" : "No");
		$objGroupTemplate->setText('ISHIDDEN', $objGroup->get('is_hidden') ? "Yes" : "No");

		$arrMembers = $objGroup->getMembers();
		foreach($arrMembers as $objMember) /* Members of the group (including the founder, admins). */
		{
			$str = '';
			$str .= "<tr>";
			$str .= "<td><a href='index.php?action=members&subaction=view&" . $objMember->getIDPair() . "'>" . $objMember->get('username') . "</a></td>";
			$str .= "</tr>";

			$objGroupTemplate->setText('MEMBERS', $str);
		}

		$arrRequestedJoins = $objGroup->getRequestedJoins();
		foreach($arrRequestedJoins as $objMember) /* Users that have requested to join. */
		{
			$str = '';
			$str .= "<tr>";
			$str .= "<td><a href='index.php?action=members&subaction=view&" . $objMember->getIDPair() . "'>" . $objMember->get('username') . "</a></td>";

			if($objGroup->isMember($objUser))
				$str .= "<td><a href='index.php?action=groups&subaction=approve&" . $objGroup->getIDPair() . "&" . $objMember->getIDPair() . "'>Approve</td>";
			else
				$str .= "<td>Awaiting Approval</td>";

			$objGroupTemplate->setText('PENDING', $str);
		}


		$arrInvitedUsers = $objGroup->getInvitedUsers();
		foreach($arrInvitedUsers as $objMember) /* Users that have been invited. */
		{
			$str = '';
			$str .= "<tr>";
			$str .= "<td><a href='index.php?action=members&subaction=view&" . $objMember->getIDPair() . "'>" . $objMember->get('username') . "</a></td>";
			$str .= "<td>Awaiting Acceptance</td>";
			$str .= "</tr>";

			$objGroupTemplate->setText('PENDING', $str);
		}

		print $objGroupTemplate->get();
	}

	if($strSubAction == 'edit') /* Create or edit a group. */
	{
		if(!$objGroup->canEdit($objUser))
			throw new Exception('exception_accessdenied');
		
		$objTemplate->setText('PAGETITLE', "Editing Group: " . $objGroup->get('name'));

		$objGroupTemplate = new clsTemplate('editgroup');
		$objGroupTemplate->setText('HIDDEN',    $objGroup->getHiddenField('id'));
		$objGroupTemplate->setText('HIDDEN',    "<input type='hidden' name='action'    value='groups'>");
		$objGroupTemplate->setText('HIDDEN',    "<input type='hidden' name='subaction' value='save'>");
		$objGroupTemplate->setText('NAME',      $objGroup->getTextField('name'));
		$objGroupTemplate->setText('ISPRIVATE', $objGroup->getCheckNoJavascript('is_private'));
		$objGroupTemplate->setText('ISHIDDEN',  $objGroup->getCheckNoJavascript('is_hidden'));
		$objGroupTemplate->setText('SAVE',      $objGroup->getSubmit('Save'));

		print $objGroupTemplate->get();
	}

	if($strSubAction == 'save')
	{
		if(!$objGroup->canEdit($objUser))
			throw new Exception('exception_accessdenied');

		$objGroup->getFromRequest(array('id', 'name', 'is_private', 'is_hidden'));

		if($objGroup->isNew())
			$objGroup->set('user_id', $objUser->get('id'));

		$objGroup->save();

		header("Location: index.php?action=groups&subaction=view&message=group_saved&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'invite') /* Invite a user to this group. */
	{
		if(!$objGroup->isMember($objUser))
			throw new Exception('exception_accessdenied');

		if($objMember->isNew())
		{
			$objTemplate->setText('PAGETITLE', "Inviting a user");
			$objBreadcrumbs->add('Inviting', 'index.php?action=groups&subaction=invite&' . $objGroup->getIDPair());
			$objMiniMenu->add('Back', 'index.php?action=groups&subaction=view&' . $objGroup->getIDPair());

			$arrMembers = clsDB::getListStatic('user', '', 'username');
			foreach($arrMembers as $objMember)
			{
				print "<ul>";
				if(!$objGroup->isMemberOrPotential($objMember))
				{
					print "<li><a href='index.php?action=groups&subaction=invite&" . $objGroup->getIDPair() . "&" . $objMember->getIDPair() . "'>" . $objMember->get('username') . "</a></li>";
				}
				print "</ul>";
			}
		}
		else
		{
			$strResult = $objGroup->inviteUser($objMember, $objUser);
			header("Location: index.php?action=groups&subaction=invite&message=$strResult&" . $objGroup->getIDPair());
		}
	}

	if($strSubAction == 'join') /* Attempt to join the group. */
	{
		if($objGroup->isMemberOrPotential($objUser))
			throw new Exception('exception_accessdenied');

		$strResult = $objGroup->tryJoin($objUser);

		header("Location: index.php?action=groups&subaction=view&message=$strResult&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'leave') /* Leave the group. */
	{
		if(!$objGroup->isMemberOrPotential($objUser))
			throw new Exception('exception_accessdenied');

		$strResult = $objGroup->leaveGroup($objUser);

		header("Location: index.php?action=groups&subaction=view&message=$strResult&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'disband') /* Disband the group. */
	{
		if(!$objGroup->isFounder($objUser))
			throw new Exception('exception_accessdenied');
		/* TODO */
		print "Sorry, not implemented.";
	}

	if($strSubAction == 'cancel') /* Cancel a join request. */
	{
		$strResult = $objGroup->cancelJoin($objUser);

		header("Location: index.php?action=groups&subaction=view&message=$strResult&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'approve') /* Approve a member. */
	{
		if(!$objGroup->isMember($objUser))
			throw new Exception('exception_accessdenied');

		$strResult = $objGroup->approveMember($objMember, $objUser);

		header("Location: index.php?action=groups&subaction=view&message=$strResult&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'accept') /* Accept an invitation. */
	{
		$strResult = $objGroup->acceptInvite($objUser);

		header("Location: index.php?action=groups&subaction=view&message=$strResult&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'decline') /* Decline an invitation. */
	{
		$strResult = $objGroup->declineInvite($objUser);

		header("Location: index.php?action=groups&subaction=view&message=$strResult&" . $objGroup->getIDPair());
	}

	if($strSubAction == 'invitations')
	{
		$objTemplate->setText('PAGETITLE', "Invitations");
		$objBreadcrumbs->add('Invitations', 'index.php?action=groups&subaction=invite&' . $objGroup->getIDPair());

		$arrGroups = clsGroup::getInvitations($objUser);
		print "<table>";
		print "<th align='left' width='250'>Group</th>";
		print "<th align='left' width='100'>Accept</th>";
		print "<th align='left' width='100'>Decline</th>";

		foreach($arrGroups as $objGroup)
		{
			print "<tr>";
			print "<td><a href='index.php?action=groups&subaction=view&" . $objGroup->getIDPair() . "'>" . $objGroup->get('name') . "</a></td>";
			print "<td><a href='index.php?action=groups&subaction=accept&" . $objGroup->getIDPair() . "'>Accept</a></td>";
			print "<td><a href='index.php?action=groups&subaction=decline&" . $objGroup->getIDPair() . "'>Decline</a></td>";
			print "</tr>";
		}
		print "</table>";
	}

?>
