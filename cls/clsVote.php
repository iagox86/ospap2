<?php

require_once('cls/clsAlbum.php');
require_once('cls/clsDB.php');


class clsVote extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('vote', $id, $row);
	}

	public static function hasVoted($objPicture, $objUser, $strIP)
	{
		if($objUser)
			if(clsDB::getCountStatic('vote', "`<<foreign><vote><user>>`='" . $objUser->get('id') . "' AND `<<foreign><vote><picture>>`='" . $objPicture->get('id') . "' "))
				return true;

			return clsDB::getCountStatic('vote', "`<<vote><ip>>`='$strIP' AND `<<foreign><vote><picture>>`='" . $objPicture->get('id') . "' ");
	}

	public static function canVote($objPicture, $objUser, $strIP)
	{
		$objAlbum = new clsAlbum($objPicture->get('album_id'));
		if(!$objAlbum->canRate($objUser))
			return false;

		if(clsVote::hasVoted($objPicture, $objUser, $strIP))
			return false;

		return true;
	}

	public static function recordVote($objPicture, $objUser, $strIP, $intVote)
	{
/* TODO: Uncomment this. */
//		if(clsVote::canVote($objPicture, $objUser, $strIP))
//			throw new Exception('exception_internalerror');

		if(!is_numeric($intVote))
			throw new Exception('exception_internalerror');

		if($intVote < 0 || $intVote > MAX_VOTE)
			throw new Exception('exception_internalerror');


		$objVote = new clsVote();
		$objVote->set('picture_id', $objPicture->get('id'));
		$objVote->set('user_id', $objUser ? $objUser->get('id') : 0);
		$objVote->set('ip', $strIP);
		$objVote->set('vote', $intVote);
		$objVote->save();
	}

	public static function getRating($objPicture)
	{
		$arrRatings = clsDB::getListStatic('vote', "`<<foreign><vote><picture>>`='" . $objPicture->get('id') . "'");

		if(sizeof($arrRatings) == 0)
			return -1;

		if(SETTING_WEIGHTED_AVERAGE)
		{
			$arrWeights = array();
			/* Generate the weight array. For a MAX_VOTE of 10, it'll be 50, 51, 52, 53, 54, 55, 54, 53, 52, 51, 50. */
			for($i = 0; $i <= MAX_VOTE; $i++)
				$arrWeights[$i] = ((MAX_VOTE / 2) - abs($i - (MAX_VOTE / 2))) + (5 * MAX_VOTE);

			$intTotal = 0;
			$intTotalWeights = 0;
			foreach($arrRatings as $objRating)
			{
				$intTotal += ($objRating->get('vote') * $arrWeights[$objRating->get('vote')]);
				$intTotalWeights += $arrWeights[$objRating->get('vote')];
			}

			return round($intTotal / $intTotalWeights, 2);
		}
		else
		{
			$intTotal = 0;
			foreach($arrRatings as $objRating)
				$intTotal += $objRating->get('vote');

			return round($intTotal / sizeof($arrRatings), 2);
		}
	}

	public static function getMaxRating()
	{
		return MAX_VOTE;
	}

	public static function getVoteField($objPicture)
	{
		$strRet = '';
		for($i = 0; $i <= MAX_VOTE; $i++)
			$strRet .= "<a href='index.php?action=picture&subaction=vote&" . $objPicture->getIDPair() . "&vote=$i'><img src='images/star.png' title='Vote $i' border='0'></a>&nbsp;";

		return $strRet;
	}

	public static function getVoteCount($objPicture)
	{
		return clsDB::getCountStatic('vote', "`<<foreign><vote><picture>>`='" . $objPicture->get('id') . "'");
	}
}



?>
