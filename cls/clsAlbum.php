<?php

require_once('cls/clsDB.php');
require_once('cls/clsGroup.php');
require_once('cls/clsPicture.php');
require_once('cls/clsTemplate.php');

class clsAlbum extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('album', $id, $row);
	}

	/** Retrieve all of a specific album's children. */
	public function getSubAlbums($objUser)
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
										WHERE `<<foreign><album><album>>`='" . $this->get('id') . "' 
											AND `<<isdel><album>>`='0'
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

	/** Get all albums belonging to a specific user. */
	public static function getUserAlbums($objUser, $objMember)
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
										WHERE `<<foreign><album><user>>`='" . $objMember->get('id') . "' 
											AND `<<isdel><album>>`='0'
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

	/** Get the number of albums belonging to a user. */
	public static function getUserAlbumCount($objUser, $objMember)
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
										WHERE `<<foreign><album><user>>`='" . $objMember->get('id') . "' 
											AND `<<isdel><album>>`='0'
										");

		$i = 0;
		foreach($arrAlbums as $objAlbum)
		{
			$objAlbum = new clsAlbum($objAlbum->get('id'));
			if($objAlbum->canView($objUser))
				$i++;
		}

		return $i;
	}

	/** Get the number of pictures belongong to a user */
	public static function getUserPictureCount($objUser, $objMember)
	{
		$arrAlbums = clsDB::selectQueryObjects('album', "SELECT `<<album><id>>`
										FROM `<<tbl><album>>`
											LEFT JOIN `<<tbl><picture>>` ON `<<foreign><picture><album>>`=`<<album><id>>`
										WHERE `<<foreign><picture><user>>`='" . $objMember->get('id') . "' 
											AND `<<isdel><album>>`='0'
											AND `<<isdel><picture>>`='0'
											AND `<<picture><confirmed>>`='1'
										");

		$i = 0;
		foreach($arrAlbums as $objAlbum)
		{
			$objAlbum = new clsAlbum($objAlbum->get('id'));
			if($objAlbum->canView($objUser))
				$i++;
		}

		return $i;
	}

	/** Get all pictures for a specific user. */
	public static function getPicturesByUser($objUser, $objMember)
	{
		$arrPictures = clsDB::selectQueryObjects('picture', "SELECT `<<tbl><picture>>`.*
										FROM `<<tbl><album>>`
											LEFT JOIN `<<tbl><picture>>` ON `<<foreign><picture><album>>`=`<<album><id>>`
										WHERE `<<foreign><picture><user>>`='" . $objMember->get('id') . "' 
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

	/** Get recently updated albums for a particular user. */
	public static function getRecentAlbums($objUser, $num)
	{
		if(!is_numeric($num))
			throw new exception('exception_internalerror');

		$arrAlbums = clsDB::selectQueryObjects('album', "
										SELECT `<<tbl><album>>`.*, `<<picture><confirmed>>`, MAX(`<<picture><date>>`) AS `<<album><last_updated>>`
										FROM `<<tbl><album>>`
											JOIN `<<tbl><picture>>` ON `<<foreign><picture><album>>`=`<<album><id>>`
											WHERE `<<isdel><album>>`='0'
												AND `<<isdel><picture>>`='0'
												AND `<<picture><confirmed>>`='1'
										GROUP BY `<<album><id>>`
										ORDER BY `<<album><last_updated>>` DESC
		");

		$arrRet = array();
		foreach($arrAlbums as $objAlbum)
		{
			if(sizeof($arrRet) == $num)
				return $arrRet;

			$objAlbum = new clsAlbum($objAlbum->get('id'));
			if($objAlbum->canView($objUser))
				$arrRet[] = $objAlbum;
		}

		return $arrRet;
	}

	/** Returns a list of all albums that a user can create a sub-album under. This is used when determining
	 * where an album can be moved to. */
	public function getPotentialParents($objUser)
	{
		/* Guests can't move things. In fact, they shouldn't even be here... */
		if(!$objUser)
			return array();

		/* Admins can move things anywhere. */
		if($objUser && $objUser->get('is_admin'))
			return clsDB::getListStatic('album', "`<<album><id>>`!='" . $this->get('id') . "'", 'name');

		/* Everybody else is where it gets complicated... each of these takes place in a UNION element: 
		 * First, we get all albums that the user owns. 
		 * Then, get all albums that allow members to create a subalbum. 
		 * Finally, get all albums that share a group in common with the member. */
		return clsDB::selectQueryObjects('album', "
			SELECT * 
			FROM 
			(
				SELECT `<<tbl><album>>`.* 
					FROM `<<tbl><album>>`
					WHERE `<<foreign><album><user>>`='" . $objUser->get('id') . "' 
						AND `<<isdel><album>>`='0'
						AND `<<album><id>>`!='" . $this->get('id') . "' 
			UNION
				SELECT `<<tbl><album>>`.* 
					FROM `<<tbl><album>>` 
						JOIN `<<tbl><albumpolicy>>` ON `<<album><albumpolicy_member_id>>`=`<<albumpolicy><id>>`
					WHERE `<<albumpolicy><allow_create_subalbum>>`='1'
						AND `<<isdel><album>>`='0'
						AND `<<album><id>>`!='" . $this->get('id') . "' 
			UNION
				SELECT `<<tbl><album>>`.*
					FROM `<<tbl><album>>`
						JOIN `<<tbl><albumpolicy>>` ON `<<album><albumpolicy_group_id>>`=`<<albumpolicy><id>>`
						JOIN `<<tbl><group>>` ON `<<foreign><album><group>>`=`<<group><id>>`
						JOIN `<<tbl><usergroup>>` ON `<<group><id>>`=`<<foreign><usergroup><group>>`
					WHERE `<<albumpolicy><allow_create_subalbum>>`='1'
						AND `<<isdel><group>>`='0'
						AND `<<isdel><album>>`='0'
						AND `<<isdel><usergroup>>`='0'
						AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
						AND `<<usergroup><has_accepted>>`='1'
						AND `<<usergroup><is_approved>>`='1'
						AND `<<album><id>>`!='" . $this->get('id') . "' 
			) AS a
			GROUP BY `<<album><id>>`
			ORDER BY `<<album><name>>`
		");
	}

	/** Get all the albums that the user is allowed to post pictures in. */
	public static function getPostableAlbums($objUser)
	{
		/* Guests can only post to albums with guest permissions. */
		if(!$objUser)
		{
			return clsDB::selectQueryObjects('album', "
				SELECT `<<tbl><album>>`.* 
					FROM `<<tbl><album>>` 
						JOIN `<<tbl><albumpolicy>>` ON `<<album><albumpolicy_guest_id>>`=`<<albumpolicy><id>>`
					WHERE `<<albumpolicy><allow_post_picture>>`='1'
						AND `<<isdel><album>>`='0'
					ORDER BY `<<album><name>>`
				");
		}

		/* Admins can move things anywhere. */
		if($objUser->get('is_admin'))
			return clsDB::getListStatic('album', "", 'name');

		/* Everybody else is where it gets complicated... each of these takes place in a UNION element: 
		 * First, we get all albums that the user owns. 
		 * Then, get all albums that allow members to post a picture
		 * Finally, get all albums that share a group in common with the member that allow group members to post. */
		return clsDB::selectQueryObjects('album', "
			SELECT * 
			FROM 
			(
				SELECT `<<tbl><album>>`.* 
					FROM `<<tbl><album>>`
					WHERE `<<foreign><album><user>>`='" . $objUser->get('id') . "' 
						AND `<<isdel><album>>`='0'
			UNION
				SELECT `<<tbl><album>>`.* 
					FROM `<<tbl><album>>` 
						JOIN `<<tbl><albumpolicy>>` ON `<<album><albumpolicy_member_id>>`=`<<albumpolicy><id>>`
					WHERE `<<albumpolicy><allow_post_picture>>`='1'
						AND `<<isdel><album>>`='0'
			UNION
				SELECT `<<tbl><album>>`.*
					FROM `<<tbl><album>>`
						JOIN `<<tbl><albumpolicy>>` ON `<<album><albumpolicy_group_id>>`=`<<albumpolicy><id>>`
						JOIN `<<tbl><group>>` ON `<<foreign><album><group>>`=`<<group><id>>`
						JOIN `<<tbl><usergroup>>` ON `<<group><id>>`=`<<foreign><usergroup><group>>`
					WHERE `<<albumpolicy><allow_post_picture>>`='1'
						AND `<<isdel><group>>`='0'
						AND `<<isdel><album>>`='0'
						AND `<<isdel><usergroup>>`='0'
						AND `<<foreign><usergroup><user>>`='" . $objUser->get('id') . "'
						AND `<<usergroup><has_accepted>>`='1'
						AND `<<usergroup><is_approved>>`='1'
			) AS a
			GROUP BY `<<album><id>>`
			ORDER BY `<<album><name>>`
		");
	}

	public function getSubAlbumIDs($objUser)
	{
		$arrAlbums = $this->getSubAlbums($objUser);
		$arrResults = array();

		foreach($arrAlbums as $objAlbum)
			if($objAlbum->canView($objUser))
				$arrResults[] = $objAlbum->get('id');

		return $arrResults;
	}

	public static function getSubAlbumScript()
	{
		return "
			function toggle_album(id)
			{
				var row = $('expanddata' + id);
				var button = $('expandbutton' + id);
				if(row.style.display == 'none')
				{
					row.style.display = 'table-row';
					button.src = 'images/contract.png';
				}
				else
				{
					row.style.display = 'none';
					button.src = 'images/expand.png';
				}
			}

		";
	}

	public function getAllAlbumScript($arrSubAlbums)
	{
		$str = "
			function expand_all()
			{
				";
				foreach($arrSubAlbums as $objSubAlbum)
				{
					$str .= "
						$('expanddata"   . $objSubAlbum->get('id') . "').style.display = 'table-row';
						$('expandbutton" . $objSubAlbum->get('id') . "').src           = 'images/contract.png';
					";
				}
		$str .= "
			}

			function contract_all()
			{
				";
				foreach($arrSubAlbums as $objSubAlbum)
				{
					$str .= "
						$('expanddata"   . $objSubAlbum->get('id') . "').style.display = 'none';
						$('expandbutton" . $objSubAlbum->get('id') . "').src           = 'images/expand.png';
					";
				}
		$str .= "
			}
		";

		return $str;
	}

	public function getPictures()
	{
		$arrPictures = clsDB::selectQueryObjects('picture', "SELECT * 
													FROM `<<tbl><picture>>` 
													WHERE `<<foreign><picture><album>>`='" . $this->get('id') . "' 
														AND `<<picture><confirmed>>`='1'
														AND `<<isdel><picture>>`='0'
													ORDER BY `<<picture><date>>` ASC");

		$arrRet = array();
		foreach($arrPictures as $objPicture)
			$arrRet[] = new clsPicture($objPicture->get('id'));
		return $arrRet;
	}


	public function getTopPictures($num = 5)
	{
		$arrResults =  clsDB::selectQueryObjects('picture', "SELECT `<<picture><id>>` 
													FROM `<<tbl><picture>>` 
													WHERE `<<foreign><picture><album>>`='" . $this->get('id') . "' 
														AND `<<picture><confirmed>>`='1'
														AND `<<isdel><picture>>`='0'
													ORDER BY RAND()
													LIMIT 0, $num");

		$arrPictures = array();
		foreach($arrResults as $objResult)
			$arrPictures[] = new clsPicture($objResult->get('id'));
		return $arrPictures;
	}

	public function getParents()
	{
		$arrParents = array();

		$objAlbum = $this->getForeignObject('album');
		while(!$objAlbum->isNew())
		{
			$arrParents[] = $objAlbum;
			$objAlbum = $objAlbum->getForeignObject('album');
		}

		return $arrParents;
	}

	public function addBreadcrumbs($objBreadcrumbs, $blnShowMe = true)
	{
        $arrParents = $this->getParents();
        $arrParents = array_reverse($arrParents);
        foreach($arrParents as $objParent)
            $objBreadcrumbs->add($objParent->get('name'), 'index.php?action=albums&' . $objParent->getIDPair());

		if($blnShowMe && !$this->isNew())
	        $objBreadcrumbs->add($this->get('name'),  'index.php?action=albums&' . $this->getIDPair());
	}


	public function getPolicy($objUser, $strPolicy)
	{
		/* Admins are always allowed. */
		if($objUser && $objUser->get('is_admin'))
			return true;

		/* Owners are also always allowed. */
		if($objUser && $this->get('user_id') == $objUser->get('id'))
			return true;

		$objAlbum = $this;

		/* For recursive checking. */
		$arrParents = $this->getParents();

		do
		{
			$objGroup = new clsGroup($objAlbum->get('group_id'));

			if($objUser == null || $objUser->isNew()) /* Guests */
			{
				$objPolicy = $objAlbum->getForeignObject('albumpolicy', 'guest');
			}
			elseif($objGroup->isMember($objUser))
			{
				$objPolicy = $objAlbum->getForeignObject('albumpolicy', 'group');
			}
			else
				$objPolicy = $objAlbum->getForeignObject('albumpolicy', 'member');

			if($objPolicy->get($strPolicy) != INHERIT)
				return $objPolicy->get($strPolicy);

			$objAlbum = array_shift($arrParents);
		}
		while($objAlbum);

		return NO; /* Default to no if no policy is found (shouldn't happen). */
	}

	/** For now, only the album's creator can edit it. Perhaps I should revisit this later. */
	public function canEdit($objUser)
	{
		if($this->isNew())
			return $objUser != null; /* This is true because editing an album with no ID is just creating a new one. */

		if(!$objUser)
			return false;

		return $objUser->get('is_admin') || ($this->get('user_id') == $objUser->get('id'));
	}

	public function canPostPicture($objUser)
	{
		if($this->isNew())
			return true;
		return $this->getPolicy($objUser, 'allow_post_picture');
	}

	public function canPostComment($objUser)
	{
		if($this->isNew())
			return false;

		return $this->getPolicy($objUser, 'allow_post_comment');
	}

	public function canRate($objUser)
	{
		if($this->isNew())
			return false;

		return $this->getPolicy($objUser, 'allow_rate');
	}

	public function canView($objUser)
	{
		if($this->isNew())
			return true;

		return $this->getPolicy($objUser, 'allow_view');
	}

	public function canDeletePicture($objUser)
	{
		if($this->isNew())
			return false;

		return $this->getPolicy($objUser, 'allow_delete_picture');
	}

	public function canCreateSubalbum($objUser)
	{
		if($this->isNew())
			return true;

		return $this->getPolicy($objUser, 'allow_create_subalbum');
	}

	public function setDefaultPolicies($objUser)
	{
		$objGuest = new clsDB('albumpolicy');
		$objMember = new clsDB('albumpolicy');
		$objGroup = new clsDB('albumpolicy');

		$objGuest->set('user_id',  $objUser->get('id'));
		$objMember->set('user_id', $objUser->get('id'));
		$objGroup->set('user_id',  $objUser->get('id'));

		/* Everything hinges on whether or not there's a parent... */
		if($this->get('album_id'))
		{
			$objGuest->set('allow_post_picture',     INHERIT);
			$objGuest->set('allow_post_comment',     INHERIT);
			$objGuest->set('allow_rate',             INHERIT);
			$objGuest->set('allow_view',             INHERIT);
			$objGuest->set('allow_delete_picture',   INHERIT);
			$objGuest->set('allow_create_subalbum',  INHERIT);

			$objMember->set('allow_post_picture',    INHERIT);
			$objMember->set('allow_post_comment',    INHERIT);
			$objMember->set('allow_rate',            INHERIT);
			$objMember->set('allow_view',            INHERIT);
			$objMember->set('allow_delete_picture',  INHERIT);
			$objMember->set('allow_create_subalbum', INHERIT);

			$objGroup->set('allow_post_picture',     INHERIT);
			$objGroup->set('allow_post_comment',     INHERIT);
			$objGroup->set('allow_rate',             INHERIT);
			$objGroup->set('allow_view',             INHERIT);
			$objGroup->set('allow_delete_picture',   INHERIT);
			$objGroup->set('allow_create_subalbum',  INHERIT);
		}
		else
		{
			$objGuest->set('allow_post_picture',     NO);
			$objGuest->set('allow_post_comment',     YES);
			$objGuest->set('allow_rate',             NO);
			$objGuest->set('allow_view',             YES);
			$objGuest->set('allow_delete_picture',   NO);
			$objGuest->set('allow_create_subalbum',  NO);

			$objMember->set('allow_post_picture',    NO);
			$objMember->set('allow_post_comment',    YES);
			$objMember->set('allow_rate',            NO);
			$objMember->set('allow_view',            YES);
			$objMember->set('allow_delete_picture',  NO);
			$objMember->set('allow_create_subalbum', NO);

			$objGroup->set('allow_post_picture',     NO);
			$objGroup->set('allow_post_comment',     YES);
			$objGroup->set('allow_rate',             YES);
			$objGroup->set('allow_view',             YES);
			$objGroup->set('allow_delete_picture',   NO);
			$objGroup->set('allow_create_subalbum',  NO);
		}

		$objGuest->save();
		$objMember->save();
		$objGroup->save();

		$this->set('albumpolicy_guest_id', $objGuest->get('id'));
		$this->set('albumpolicy_member_id', $objMember->get('id'));
		$this->set('albumpolicy_group_id', $objGroup->get('id'));
	}

	/** This gets a little tricky... */
	public static function getPolicyFromRequest($strName, $objUser)
	{
		/* Create the object that'll be able to read the request. */
		$objPolicy = new clsDB($strName);

		/* Load the fields from the request. */
		$objPolicy->getFromRequest(array('id', 'allow_post_picture', 'allow_post_comment', 'allow_rate', 'allow_view', 'allow_delete_picture', 'allow_create_subalbum'));

		/* Set the name so we can access the database. */
		$objPolicy->setName('albumpolicy');

		/* Load it (to get the user_id). */
		$objPolicy->load();

		/* Check the user_id to see if we have any issues. */
		if(!$objUser->get('is_admin') && $objPolicy->get('user_id') != $objUser->get('id'))
			throw new Exception('exception_accessdenied');

		/* Set the name back so we can read the request again. */
		$objPolicy->setName($strName);

		/* Read the user's input from the request. */
		$objPolicy->getFromRequest(array('id', 'allow_post_picture', 'allow_post_comment', 'allow_rate', 'allow_view', 'allow_delete_picture', 'allow_create_subalbum'));

		/* Set the name back to what it ought to be (so we can save it). */
		$objPolicy->setName('albumpolicy');

		/* And that it! */
		return $objPolicy;
	}

	public function getUsername()
	{
		$objUser = new clsUser($this->get('user_id'));

		if($objUser->isNew())
			return $this->get('username');

		return $objUser->get('username');
	}

	public function getLastUpdated()
	{
		$arrPictures = clsDB::selectQueryObjects('picture', 
							"SELECT * 
							FROM `<<tbl><picture>>`
							WHERE `<<foreign><picture><album>>`='" . $this->get('id') . "'
								AND `<<isdel><picture>>`='0'
								AND `<<picture><confirmed>>`='1'
							ORDER BY `<<picture><date>>` DESC
							LIMIT 0, 1");

		if(sizeof($arrPictures) == 0)
			return 'Never';
		else
			return time_to_text(strtotime($arrPictures[0]->get('date')));
	}

	public function getNewIcon($objUser)
	{
		return $this->hasNewPictures($objUser) ? "<img src='images/new.png'>" : '';
	}

	public function isEmpty()
	{
		$arrDB = clsDB::selectQuery("
					SELECT *
						FROM `<<tbl><picture>>`
						WHERE
							`<<isdel><picture>>`='0' 
							AND `<<foreign><picture><album>>`='" . $this->get('id') . "'
						LIMIT 0, 1
					");

		return sizeof($arrDB) == 0;
	}

	public function hasNewPictures($objUser)
	{
		if(!$objUser)
			return false;

		if($this->isEmpty())
			return false;

		$arrDB = clsDB::selectQuery("
					SELECT * 
						FROM `<<tbl><picture>>`
						WHERE
							`<<isdel><picture>>`='0' 
							AND `<<picture><confirmed>>`='1'
							AND `<<foreign><picture><album>>`='" . $this->get('id') . "'
							AND `<<picture><id>>` NOT IN
								(
									SELECT `<<foreign><userpictureview><picture>>`
										FROM `<<tbl><userpictureview>>`
										WHERE `<<foreign><userpictureview><user>>`='" . $objUser->get('id') . "'
											AND `<<isdel><userpictureview>>`='0'
								)
						LIMIT 0, 1
					");

		return sizeof($arrDB);
							
	}

	public function display($objUser)
	{
		$objAlbumOwner = $this->getForeignObject('user');
		$intPictureCount = sizeof($this->getPictures());
		$intSubAlbumCount = sizeof($this->getSubAlbums($objUser));

		if($intPictureCount == 0 && $intAlbumCount == 0 && $objUser && $objUser->get('show_empty') == 0)
			return '';

		$objAlbumTemplate = new clsTemplate('album');
		$objAlbumTemplate->setText('NAME', "<a href='index.php?action=albums&" . $this->getIDPair() . "' class='albumentrylink'>" . $this->get('name') . "</a> " . $this->getNewIcon($objUser));

		$objAlbumTemplate->setText('ID',           $this->get('id'));
		$objAlbumTemplate->setText('USERNAME',     $objAlbumOwner->get('username'));
		$objAlbumTemplate->setText('CAPTION',      bbcode_format($this->get('caption')));
		$objAlbumTemplate->setText('EXPANDCLICK',  "toggle_album(\"" . $this->get('id') . "\");");
		$objAlbumTemplate->setText('LASTUPDATED',  $this->getLastUpdated());
		$objAlbumTemplate->setText('PICTURECOUNT', $intPictureCount);
		$objAlbumTemplate->setText('ALBUMCOUNT',   $intSubAlbumCount);

		if($intPictureCount == 0 && $intSubAlbumCount == 0)
			$objAlbumTemplate->setText('ISEMPTY', '(empty)');

		$strPreview = "";
		$arrPictures = $this->getTopPictures(ALBUM_NUMPREVIEW);
		foreach($arrPictures as $objPicture)
			$objAlbumTemplate->setText('PREVIEW', $objPicture->getHtmlThumbnail(ALBUM_PREVIEWSIZE, ALBUM_PREVIEWSIZE));

		print $objAlbumTemplate->get();
	}

	public static function displayAlbums($arrAlbums, $objUser)
	{
		foreach($arrAlbums as $objAlbum)
			$objAlbum->display($objUser);
	}

	public static function markSeen($objUser, $objAlbum = null)
	{
		if(!$objAlbum || $objAlbum->isNew())
			$arrPictures = clsDB::getListStatic('picture');
		else
			$arrPictures = $objAlbum->getForeignObjects('picture');

		foreach($arrPictures as $objPicture)
		{   
			$objPicture = new clsPicture($objPicture->get('id'));
			$objPicture->setViewed($objUser);
		}
	}

	public static function getUserFilter($strZeroCaption)
	{
		$str = "<form method='get'>
					<input type='hidden' name='action' value='albums'>
					<input type='hidden' name='subaction' value='useralbums'>
					<select name='user_id'> 
						<option value='0'>$strZeroCaption</option>
					";
		$arrUsers = clsDB::getListStatic('user', '', 'username');
		foreach($arrUsers as $objUser)
			$str .= "<option value='" . $objUser->get('id') . "'>" . $objUser->get('username') . "</option>\n";

		$str .= "
					</select>
					<input type='submit' value='Filter'>
				</form>";

		return $str;
	}
}



?>
