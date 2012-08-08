<?php

require_once('cls/clsDB.php');

class clsGroup extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('group', $id, $row);
	}

	/* Retrieve the list of groups that the user is allowed to see. A user can see a group if:
	 * - The group is not set to hidden, or
	 * - The user is a member of the group. 
	 */
	public static function getGroupList($objUser)
	{
		if($objUser && $objUser->get('is_admin'))
		{
			$arrGroups = clsDB::getListStatic('group');
		}
		else
		{
			$arrGroups = clsDB::selectQueryObjects('group', "
					SELECT * FROM
					(
							SELECT `<<group><id>>`, `<<group><name>>`
								FROM `<<tbl><group>>`
								WHERE `<<group><is_hidden>>`='0'
									AND `<<isdel><group>>`='0'
					" . ($objUser ? "
							UNION
								SELECT `<<group><id>>`, `<<group><name>>`
									FROM `<<tbl><group>>` 
										JOIN `<<tbl><usergroup>>` ON `<<group><id>>`=`<<foreign><usergroup><group>>`
									WHERE `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "' 
										AND `<<isdel><group>>`='0'
										AND `<<isdel><usergroup>>`='0'
							UNION
								SELECT `<<group><id>>`, `<<group><name>>`
									FROM `<<tbl><group>>`
									WHERE `<<foreign><group><user>>`='" . $objUser->get('id') . "'
										AND `<<isdel><group>>`='0'
						" : "") . "
					) AS i
					GROUP BY `<<group><id>>` 
					ORDER BY `<<group><name>>`
				");
		}

		$arrRet = array();
		foreach($arrGroups as $objGroup)
			$arrRet[] = new clsGroup($objGroup->get('id'));

		return $arrRet;
	}

	public static function getGroups($objUser)
	{
		return clsDB::selectQueryObjects('group', "
				SELECT `<<tbl><group>>`.*
					FROM `<<tbl><group>>`
						JOIN `<<tbl><usergroup>>` ON `<<group><id>>`=`<<foreign><usergroup><group>>`
					WHERE `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
						AND `<<isdel><usergroup>>`='0'
						AND `<<isdel><group>>`='0'
						AND `<<usergroup><has_accepted>>`='1'
						AND `<<usergroup><is_approved>>`='1'
			UNION
				SELECT `<<tbl><group>>`.*
					FROM `<<tbl><group>>`
						WHERE `<<foreign><group><user>>`='" . $objUser->get('id') . "'
						AND `<<isdel><group>>`='0'
			
							");

	}

	/** Gets a list of a group's members. */
	public function getMembers()
	{
		return array_merge(array($this->getForeignObject('user')), 
							clsDB::selectQueryObjects('user', "
								SELECT * 
									FROM `<<tbl><user>>` 
										JOIN `<<tbl><usergroup>>` ON `<<foreign><usergroup><user>>`=`<<user><id>>`
										WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
											AND `<<usergroup><is_approved>>`='1'
											AND `<<usergroup><has_accepted>>`='1'
											AND `<<isdel><usergroup>>`='0'
											AND `<<isdel><user>>`='0'
										ORDER BY
											`<<usergroup><is_administrator>>` DESC,
											`<<user><username>>` ASC
				"));
	}

	/* Gets a list of groups that a user has been invited to. */
	public static function getInvitations($objUser)
	{
		if(!$objUser)
			return array();

		$arrGroups = clsDB::selectQueryObjects('group', "
					SELECT `<<group><id>>`, `<<group><name>>`
						FROM `<<tbl><group>>`
							JOIN `<<tbl><usergroup>>` ON `<<foreign><usergroup><group>>`=`<<group><id>>`
					WHERE `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
						AND `<<usergroup><has_accepted>>`='0'
						AND `<<isdel><group>>`='0'
						AND `<<isdel><usergroup>>`='0'
					ORDER BY `<<group><name>>`
				");

		$arrRet = array();
		foreach($arrGroups as $objGroup)
			$arrRet[] = new clsGroup($objGroup->get('id'));

		return $arrRet;
	}

	public function isFounder($objUser)
	{
		return $objUser && ($objUser->get('id') == $this->get('user_id'));
	}

	/** Checks if the user is an approved member of the group, or is waiting to become one. */
	public function isMemberOrPotential($objUser)
	{
		if(!$objUser)
			return false;

		if($this->isFounder($objUser))
			return true;

		$arrGroups = clsDB::selectQuery("
									SELECT * 
										FROM `<<tbl><usergroup>>`
											WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
												AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
												AND `<<isdel><usergroup>>`='0'
									LIMIT 0, 1
				");

		return sizeof($arrGroups);
	}

	/** Checks if the user is an approved member of the group. */
	public function isMember($objUser)
	{
		if(!$objUser)
			return false;

		if($this->isFounder($objUser))
			return true;

		$arrGroups = clsDB::selectQuery("
									SELECT * 
										FROM `<<tbl><usergroup>>`
											WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
												AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
												AND `<<usergroup><is_approved>>`='1'
												AND `<<usergroup><has_accepted>>`='1'
												AND `<<isdel><usergroup>>`='0'
									LIMIT 0, 1
				");

		return sizeof($arrGroups);
	}

	/** Checks if the user is a non-approved member of the group. */
	public function isRequestedMember($objUser)
	{
		if(!$objUser)
			return false;

		if($this->isFounder($objUser))
			return false;

		$arrGroups = clsDB::selectQuery("
									SELECT * 
										FROM `<<tbl><usergroup>>`
											WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
												AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
												AND `<<usergroup><is_approved>>`='0'
												AND `<<isdel><usergroup>>`='0'
									LIMIT 0, 1
				");

		return sizeof($arrGroups);
	}

	/** Checks if the user is an invited member of the group. */
	public function isInvitedMember($objUser)
	{
		if(!$objUser)
			return false;

		if($this->isFounder($objUser))
			return false;

		$arrGroups = clsDB::selectQuery("
									SELECT * 
										FROM `<<tbl><usergroup>>`
											WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
												AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
												AND `<<usergroup><has_accepted>>`='0'
												AND `<<isdel><usergroup>>`='0'
									LIMIT 0, 1
				");

		return sizeof($arrGroups);
	}

	/* Gets a list of users that are waiting for approval. */
	public function getRequestedJoins()
	{
		return clsDB::selectQueryObjects('user', "
								SELECT * 
									FROM `<<tbl><user>>` 
										JOIN `<<tbl><usergroup>>` ON `<<foreign><usergroup><user>>`=`<<user><id>>`
										WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
											AND `<<usergroup><is_approved>>`='0'
											AND `<<isdel><usergroup>>`='0'
											AND `<<isdel><user>>`='0'
										ORDER BY
											`<<user><username>>` ASC
										");
	}

	/* Gets a list of users that are waiting for approval. */
	public function getInvitedUsers()
	{
		return clsDB::selectQueryObjects('user', "
								SELECT * 
									FROM `<<tbl><user>>` 
										JOIN `<<tbl><usergroup>>` ON `<<foreign><usergroup><user>>`=`<<user><id>>`
										WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
											AND `<<usergroup><has_accepted>>`='0'
											AND `<<isdel><usergroup>>`='0'
											AND `<<isdel><user>>`='0'
										ORDER BY
											`<<user><username>>` ASC
										");
	}

	/* Checks if the user is an administrator of the group. */
	public function isAdministrator($objUser)
	{
		if(!$objUser)
			return false;

		if($objUser->get('is_admin')) /* Might need to get rid of this line, not sure. */
			return true;

		if($this->isFounder($objUser))
			return true;

		$arrGroups = clsDB::selectQueryObjects('group', "
									SELECT * 
										FROM `<<tbl><usergroup>>`
											WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
												AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
												AND `<<usergroup><is_approved>>`='1'
												AND `<<usergroup><has_accepted>>`='1'
												AND `<<usergroup><is_administrator>>`='1'
												AND `<<isdel><usergroup>>`='0'
									LIMIT 0, 1
				");
	}

	/* Called when a user requests to join the group. */
	public function tryJoin($objUser)
	{
		if(!$objUser)
			return 'groupjoin_error';
		if($this->isMemberOrPotential($objUser))
			return 'groupjoin_alreadyin';
		if($this->get('is_hidden') && !$objUser->get('is_admin'))
			return 'groupjoin_error';
		if($this->isNew())
			return 'groupjoin_error';

		$objUserGroup = new clsDB('usergroup');
		$objUserGroup->set('user_id', $objUser->get('id'));
		$objUserGroup->set('group_id', $this->get('id'));
		$objUserGroup->set('has_accepted', '1');

		if($this->get('is_private'))
			$objUserGroup->set('is_approved', 0);
		else
			$objUserGroup->set('is_approved', 1);

		$objUserGroup->save();

		return $this->get('is_private') ? 'groupjoin_pending' : 'groupjoin_success';
	}

	/* Called when a user is invited to join a group. */
	public function inviteUser($objUser, $objInviter)
	{
		if(!$objUser)
			return 'groupinvite_error';
		if($this->isMemberOrPotential($objUser))
			return 'groupinvite_alreadyin';
		if($this->isNew())
			return 'groupinvite_error';
		if(!$this->isMember($objInviter))
			return 'groupinvite_notmember';

		$objUserGroup = new clsDB('usergroup');
		$objUserGroup->set('user_id', $objUser->get('id'));
		$objUserGroup->set('user_inviter_id', $objInviter->get('id'));
		$objUserGroup->set('group_id', $this->get('id'));
		$objUserGroup->set('has_accepted', '0');
		$objUserGroup->set('is_approved', '1');
		$objUserGroup->save();

		return 'groupinvite_successful';
	}

	private function getUserGroupObject($objMember)
	{
		$arrUserGroups = clsDB::selectQueryObjects('usergroup', "
									SELECT * 
										FROM `<<tbl><usergroup>>`
											WHERE `<<foreign><usergroup><group>>`='" . $this->get('id') . "'
												AND `<<foreign><usergroup><user>>`='" . $objMember->get('id') . "'
												AND `<<isdel><usergroup>>`='0'
									LIMIT 0, 1
				");

		if(!sizeof($arrUserGroups))
			return null;

		return $arrUserGroups[0];
	}

	public function approveMember($objUser, $objAuthorizer)
	{
		if(!$objUser)
			return 'groupapprove_error';

		if(!$this->isMember($objAuthorizer))
			return 'groupapprove_error';

		$objUserGroup = $this->getUserGroupObject($objUser);
		if(!$objUserGroup)
			return 'groupapprove_error';

		$objUserGroup->set('is_approved', 1);
		$objUserGroup->set('user_approver_id', $objAuthorizer->get('id'));
		$objUserGroup->save();

		return 'groupapprove_success';
	}

	public function acceptInvite($objUser)
	{
		if(!$objUser)
			return 'groupaccept_error';

		$objUserGroup = $this->getUserGroupObject($objUser);

		if(!$objUserGroup)
			return 'groupaccept_error';

		$objUserGroup->set('has_accepted', 1);
		$objUserGroup->save();

		return 'groupaccept_success';
	}

	public function declineInvite($objUser)
	{
		if(!$objUser)
			return 'groupdecline_error';

		$objUserGroup = $this->getUserGroupObject($objUser);

		if(!$objUserGroup)
			return 'groupdecline_error';

		$objUserGroup->delete();
		$objUserGroup->save();

		return 'groupdecline_success';
	}

	public function cancelJoin($objUser)
	{
		if(!$objUser)
			return 'groupcancel_error';

		$objUserGroup = $this->getUserGroupObject($objUser);

		if(!$objUserGroup)
			return 'groupcancel_error';

		$objUserGroup->delete();
		$objUserGroup->save();

		return 'groupcancel_success';
	}

	public function leaveGroup($objUser)
	{
		if(!$objUser)
			return 'groupleave_error';

		$objUserGroup = $this->getUserGroupObject($objUser);

		if(!$objUserGroup)
			return 'groupleave_error';

		$objUserGroup->delete();
		$objUserGroup->save();

		return 'groupleave_success';
	}

	public function canEdit($objUser)
	{
		if(!$objUser)
			return false;

		if($objUser->get('is_admin'))
			return true;

		if($this->isNew())
			return true;

		return $this->isAdministrator($objUser);
	}

    public static function getAlbumsByGroup($objUser, $objGroup)
    {   
        $arrAlbums = clsDB::selectQueryObjects('album', "SELECT *
                                        FROM `<<tbl><album>>`
                                            LEFT JOIN
                                                (
                                                    SELECT `<<foreign><picture><album>>`, MAX(`<<picture><date>>`) AS `<<album><last_updated>>`
                                                    FROM `<<tbl><picture>>`
                                                        WHERE `<<isdel><picture>>`='0'
                                                            AND `<<picture><confirmed>>`='1'
                                                    GROUP BY `<<foreign><picture><album>>`
                                                ) AS a
                                                ON `<<album><id>>` = `<<foreign><picture><album>>`
                                        WHERE `<<isdel><album>>`='0'
                                            AND `<<foreign><album><group>>`='" . $objGroup->get('id') . "'
                                        ORDER BY `<<album><last_updated>>` DESC ");

        $arrRet = array();

        foreach($arrAlbums as $objAlbum)
        {
            $objAlbum = new clsAlbum($objAlbum->get('id'));
            if($objAlbum->canView($objUser))
                $arrRet[] = $objAlbum;
        }

        return $arrRet;
    }


    public static function getPicturesByGroup($objUser, $objGroup)
    {   
        $arrPictures = clsDB::selectQueryObjects('picture', "SELECT `<<tbl><picture>>`.*
                                        FROM `<<tbl><album>>`
                                            LEFT JOIN `<<tbl><picture>>` ON `<<foreign><picture><album>>`=`<<album><id>>`
                                        WHERE `<<foreign><album><group>>`='" . $objGroup->get('id') . "' 
                                            AND `<<isdel><album>>`='0'
                                            AND `<<isdel><picture>>`='0'
                                            AND `<<picture><confirmed>>`='1'
                                        ORDER BY `<<picture><date>>` DESC
                                        ");

        $arrRet = array();

        /* TODO: Might be able to make this more efficient. Make sure that canView() isn't running a query every time. */
        foreach($arrPictures as $objPicture)
        {   
            $objAlbum = new clsAlbum($objPicture->get('album_id'));
            if($objAlbum->canView($objUser))
                $arrRet[] = new clsPicture($objPicture->get('id'));
        }

        return $arrRet;
    }

}

?>
