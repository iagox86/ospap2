<?php
require_once('cls/clsDB.php');
require_once('cls/clsThumbnail.php');


class clsComment extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('comment', $id, $row);
	}

	public function getUsername()
	{
		$objUser = new clsUser($this->get('user_id'));

		if($objUser->isNew())
			return $this->get('username');

		return $objUser->get('username');
	}

	public function getNewIcon($objUser)
	{   
		return $this->hasViewed($objUser) ? '' : "<img src='images/new.png'>";
	}

	public function hasViewed($objUser)
	{   
		if(!$objUser)
			return true;

		$arrView = clsDB::selectQuery("SELECT *
										FROM `<<tbl><usercommentview>>`
										WHERE `<<foreign><usercommentview><user>>`='".$objUser->get('id')."'
											AND `<<foreign><usercommentview><comment>>`='".$this->get('id')."'
											AND `<<isdel><usercommentview>>`='0'
										LIMIT 0, 1 ");

		return sizeof($arrView);
	}

	public function setViewed($objUser)
	{   
		if(!$objUser)
			return;

		if($this->hasViewed($objUser))
			return;

		$objUserPictureView = new clsDB('usercommentview');
		$objUserPictureView->set('user_id', $objUser->get('id'));
		$objUserPictureView->set('comment_id', $this->get('id'));
		$objUserPictureView->set('date', date('Y-m-d H:i:s'));
		$objUserPictureView->save();
	}

	public static function getNewComments($objUser)
	{
		$arrPictures = clsDB::selectQueryObjects('comment', "
					SELECT * 
							, `<<comment><id>>` AS COMMENTFILTER 
						FROM `<<tbl><comment>>`
							JOIN `<<tbl><picture>>` ON `<<foreign><comment><picture>>`=`<<picture><id>>`
						WHERE
							`<<isdel><comment>>`='0'
							AND `<<isdel><picture>>`='0'
							AND `<<picture><confirmed>>`='1'
							AND `<<foreign><picture><user>>`='" . $objUser->get('id') . "' 
							AND `<<comment><id>>` NOT IN
							(
								SELECT `<<comment><id>>`
									FROM `<<tbl><usercommentview>>` 
										JOIN `<<tbl><comment>>` ON `<<foreign><usercommentview><comment>>`=`<<comment><id>>`
										JOIN `<<tbl><picture>>` ON `<<foreign><comment><picture>>`=`<<picture><id>>`
									WHERE `<<isdel><comment>>`='0'
										AND `<<isdel><usercommentview>>`='0'
										AND `<<isdel><picture>>`='0'
										AND `<<picture><confirmed>>`='1'
										AND `<<foreign><usercommentview><user>>`='" . $objUser->get('id') . "'
										" . ($blnAllPictures ? "" : " AND `<<foreign><picture><user>>`='" . $objUser->get('id') . "' ") . "
										AND `<<comment><id>>`=`COMMENTFILTER`
							)
						"); 

		return $arrPictures;
	}

	public function canEdit($objUser)
	{
		if(!$objUser)
			return false;

		if($this->isNew())
			return true;

		if($objUser->get('is_admin'))
			return true;

		return $objUser->get('id') == $this->get('user_id');
	}

	public function canDelete($objUser)
	{
		if(!$objUser)
			return false;

		if($objUser->get('is_admin'))
			return true;

		if($this->isNew())
			return false;

		return $objUser->get('id') == $this->get('user_id');
	}
}

?>
