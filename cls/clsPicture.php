<?php

require_once('cls/clsDB.php');
require_once('cls/clsThumbnail.php');


class clsPicture extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('picture', $id, $row);
	}

	/** Returns an array of strings (for error messages) and image objects (for successful). */
	public static function createFromArchive($filename, $objAlbum)
	{
		if(!class_exists('ZipArchive'))
			return "image_nozip";

		$arrRet = array();

		$zipArchive = new ZipArchive();
		$zipArchive->open($filename);

		for($i = 0; $i < $zipArchive->numFiles; $i++)
		{
			/* We stat the file, to get its name. */
			$stat = $zipArchive->statIndex($i);
			$strInName = $stat['name'];

			/* Open the temp output file. This is vulnerable to a race condition. TODO. */
			$filename = tempnam("/tmp", "OSPAP2");
			$out = fopen($filename, 'w');

			/* Get the file stream from the archive. */
			$in = $zipArchive->getStream($stat['name']);

			/* Read the file from the zip. It takes several iterations to get to the end, so we loop. */
			while(!feof($in))
				fputs($out, fgets($in));

			/* Close the input and output files. */
			fclose($out);
			fclose($in);

			/* Determine the mimetype from the filename. None of php's built-in functions seem to work.. */
			if(preg_match("/\.png$/", $stat['name']))
				$mimetype = "image/png";
			elseif(preg_match("/\.gif$/", $stat['name']))
				$mimetype = "image/gif";
			elseif(preg_match("/\.jpg$/", $stat['name']))
				$mimetype = "image/jpeg";
			elseif(preg_match("/\.jpeg$/", $stat['name']))
				$mimetype = "image/jpeg";
			else
				$mimetype = "unknown";

			/* Create the new file. */
			$new = clsPicture::createFromFile($filename, $mimetype, $objAlbum);

			/* Delete the file. */
			unlink($filename);

			if(is_string($new))
			{
				$strError = $new;
				$new = new clsDB('image');
				$new->set('error', $strError);
				$new->set('name', $stat['name']);
			}
			else
			{
				$new->set('original_name', $stat['name']);
				$new->set('original_mime', $mimetype);
				$new->set('original_size', $stat['size']);
			}

			/* Add it to the array (note that it can be a string (for an error) or an image object. */
			$arrRet[] = $new;
		}

		if(sizeof($arrRet) == 0)
			return 'image_nofiles';

		return $arrRet;
	}

	public static function createFromFile($filename, $mime_type, $objAlbum)
	{
		$objPicture = new clsPicture();

		/* Decide which incoming mime type it is. */
		switch($mime_type)
		{
			case 'image/jpeg':
				$img = ImageCreateFromJpeg($filename);
				break;

			case 'image/png':
				$img = ImageCreateFromPng($filename);
				break;

			case 'image/gif':
				$img = ImageCreateFromGif($filename);
				break;

			default:	
				return 'image_filetype';
		}


		list($intWidth, $intHeight) = getImageSize($filename);

		$intMaxWidth  = $objAlbum->get('max_width');
		$intMaxHeight = $objAlbum->get('max_height');

		if($intMaxWidth <= 0)
			$intMaxWidth = DEFAULT_X;
		if($intMaxHeight <= 0)
			$intMaxHeight = DEFAULT_Y;

		if($intWidth > $intMaxWidth || $intHeight > $intMaxHeight)
		{
			/* Check whether the image needs to be resized vertically or horizonally more. */
			if($intWidth / $intMaxWidth > $intHeight / $intMaxHeight)
			{
				/* Right-left needs to have priority. */
				$ratio = $intMaxWidth / $intWidth;
			}
			else
			{
				/* Up-down needs to have priority. */
				$ratio = $intMaxHeight / $intHeight; 
			}

			$intNewWidth  = $intWidth * $ratio;
			$intNewHeight = $intHeight * $ratio;
	
			$imgNew = @ImageCreateTrueColor($intNewWidth, $intNewHeight);
	
			if(!@ImageCopyResized($imgNew, $img, 0, 0, 0, 0, $intNewWidth, $intNewHeight, $intWidth, $intHeight))
				return "image_noresize";

			$intWidth  = $intNewWidth;
			$intHeight = $intNewHeight;

			ImageDestroy($img);
			$img = $imgNew;
		}

		/* This has to be done before setImage() because setImage() needs data from the album. */
		$objPicture->set('album_id', $objAlbum->get('id')); 

		$result = $objPicture->setImage($img);
		ImageDestroy($img);

		if($result)
			return $result;

		$objPicture->set('width',  $intWidth);
		$objPicture->set('height', $intHeight);
		$objPicture->save();

		return $objPicture;
	}

	public function getResized($objUser, $maxWidth, $maxHeight)
	{
		$img       = $this->getImage($objUser);
		$intWidth  = $this->get('width');
		$intHeight = $this->get('height');

		if($maxWidth <= 0 || $maxHeight <= 0)
			throw new Exception("Invalid size");

		if($intWidth < $maxWidth && $intHeight < $maxHeight)
			return array($img, $intWidth, $intHeight);

		/* Check whether the image needs to be resized vertically or horizonally more. */
		if($intWidth / $maxWidth > $intHeight / $maxHeight)
			$ratio = $maxWidth  / $intWidth;
		else
			$ratio = $maxHeight / $intHeight; 

		$imgNew = ImageCreateTrueColor($intWidth * $ratio, $intHeight * $ratio);

		if(!ImageCopyResized($imgNew, $img, 0, 0, 0, 0, $intWidth * $ratio, $intHeight * $ratio, $intWidth, $intHeight))
		{
			ImageDestroy($imgNew);
			return array("image_noresize", null, null);
		}

		return array($imgNew, $intWidth * $ratio, $intHeight * $ratio);
	}

	public function getImageRaw($objUser)
	{
		return base64_decode($this->getFrom('imagedata', 'data'));
	}

	public function getImage($objUser)
	{
		return ImageCreateFromString(base64_decode($this->getFrom('imagedata', 'data')));
	}

	public function setImage($img)
	{
		$objAlbum = $this->getForeignObject('album');
		$mime_type = $objAlbum->get('mime');

		if(!$mime_type || $mime_type == '')
			$mime_type = DEFAULT_MIME;
		ob_start(); /* Start output buffering (so we can caption the file). */

		switch($mime_type)
		{
			case 'image/jpeg':
				if(!imagejpeg($img, NULL, 100))
					return "image_nosave";
				break;

			case 'image/png':
				if(!imagepng($img))
					return "image_nosave";
				break;

			case 'image/gif':
				if(!imagegif($img))
					return "image_nosave";
				break;

			default:	
				return 'image_filetype';
		}

		$objImageData = new clsDB('imagedata');
		$objImageData->set('data', base64_encode(ob_get_clean()));
		$objImageData->set('mime', $mime_type);
		$objImageData->save();

		$this->set('imagedata_id', $objImageData->get('id'));

		return 0;
	}

	public function setMime()
	{
		header("Content-type: " . $this->getFrom('imagedata', 'mime'));
	}

	public function getHtml()
	{
		return "<img src='picture.php?" . $this->getIDPair() . "'>";
	}

	public function getHtmlThumbnail($width, $height)
	{
		return "<a href='index.php?action=picture&" . $this->getIDPair() . "'><img src='picture.php?action=tn&w=$width&h=$height&" . $this->getIDPair() . "'></a>";
	}

	public function getPending($objUser)
	{
		$user_id = $objUser ? $objUser->get('id') : 0;

		return clsDB::selectQueryObjects('picture', "SELECT *
													FROM `<<tbl><picture>>`
													WHERE `<<picture><confirmed>>`='0' 
														AND `<<foreign><picture><user>>`='$user_id'
														AND `<<isdel><picture>>` = '0'
													ORDER BY `<<picture><date>>` ASC");
	}

	public function getPrev()
	{
		$arrTemp = clsDB::selectQuery("SELECT * 
										FROM `<<tbl><picture>>`
										WHERE `<<picture><date>>`<'" . $this->get('date') . "'
											AND `<<isdel><picture>>`='0'
											AND `<<foreign><picture><album>>`='" . $this->get('album_id') . "'
											AND `<<picture><confirmed>>`='1'
										ORDER BY `<<picture><date>>` DESC
										LIMIT 0, 1
									");

		if(sizeof($arrTemp) == 0)
			return null;
		else
			return new clsPicture($arrTemp[0]['picture_id']);
	}

	public function getNext()
	{
		$arrTemp = clsDB::selectQuery("SELECT * 
										FROM `<<tbl><picture>>`
										WHERE `<<picture><date>>`>'" . $this->get('date') . "'
											AND `<<isdel><picture>>`='0'
											AND `<<foreign><picture><album>>`='" . $this->get('album_id') . "'
											AND `<<picture><confirmed>>`='1'
										ORDER BY `<<picture><date>>` ASC
										LIMIT 0, 1
									");

		if(sizeof($arrTemp) == 0)
			return null;
		else
			return new clsPicture($arrTemp[0]['picture_id']);
	}

	public function getRecentPictures($objUser, $num)
	{
		if(!is_numeric($num))
			throw new Exception('exception_internalerror');

		$arrPictures = clsDB::selectQueryObjects('picture', "
			SELECT * 
			FROM `<<tbl><picture>>` JOIN `<<tbl><album>>` ON `<<foreign><picture><album>>`=`<<album><id>>`
				WHERE `<<isdel><picture>>`='0'
					AND `<<picture><confirmed>>`='1'
					AND `<<isdel><album>>`='0'
				ORDER BY `<<picture><date>>` DESC
					");

		$arrRet = array();
		foreach($arrPictures as $objPicture)
		{
			if(sizeof($arrRet) == $num)
				return $arrRet;

			$objAlbum = new clsAlbum($objPicture->get('album_id'));
			if($objAlbum->canView($objUser))
				$arrRet[] = $objPicture;
		}

		return $arrRet;
	}

	public function getUsername()
	{
		$objUser = new clsUser($this->get('user_id'));

		if($objUser->isNew())
			return $this->get('username');

		return $objUser->get('username');
	}

	public function canEdit($objUser)
	{
		return $objUser && ($objUser->get('is_admin') || ($objUser->get('id') == $this->get('user_id')));
	}

	public function getComments()
	{
		return clsDB::selectQueryObjects('comment', "
						SELECT *
							FROM `<<tbl><comment>>`
							WHERE `<<foreign><comment><picture>>`='" . $this->get('id') . "' 
								AND `<<isdel><comment>>`='0'
					");
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
										FROM `<<tbl><userpictureview>>`
										WHERE `<<foreign><userpictureview><user>>`='".$objUser->get('id')."'
											AND `<<foreign><userpictureview><picture>>`='".$this->get('id')."'
											AND `<<isdel><userpictureview>>`='0'
										LIMIT 0, 1 ");

		return sizeof($arrView);
	}

	public function setViewed($objUser)
	{
		if(!$objUser)
			return;

		if($this->hasViewed($objUser))
			return;

		$objUserPictureView = new clsDB('userpictureview');
		$objUserPictureView->set('user_id', $objUser->get('id'));
		$objUserPictureView->set('picture_id', $this->get('id'));
		$objUserPictureView->set('date', date('Y-m-d H:i:s'));
		$objUserPictureView->save();
	}

	public function display()
	{
		print "<td width='80' align='center' valign='top' class='albumpicturecaption'>";
		print "<a href='index.php?action=picture&" . $this->getIDPair() . "' class='albumpicturelink'>";
		print  $this->get('title') . "<br>";
		print  clsThumbnail::getImg($this, ALBUM_PREVIEWSIZE, ALBUM_PREVIEWSIZE);
		print "</a>";
		print "</td>";
	}

	public static function displayPictures($arrPictures, $showNoPictures = true)
	{
		if($showNoPictures && sizeof($arrPictures) == 0)
		{
			print "<strong><em>No pictures to display.</strong></em>";
		}
		else
		{
			print "<table cellpadding='2' cellspacing='2' width='100%'>";
			print " <tr>";
			foreach($arrPictures as $objPicture)
			{
				if(($i++ % ALBUM_NUMPERROW) == 0)
					print "</tr><tr>";
	
				$objPicture->display();
			}
	
			while(($i++ % ALBUM_NUMPERROW) != 0)
				print "<td class='albumpicturecaption'>&nbsp;</td>";
			print "</tr></table>";
		}
	}

	public static function getNewPictures($objUser)
	{
		$arrPictures = clsDB::selectQueryObjects('picture', "
			SELECT `<<tbl><picture>>`.*
			FROM `<<tbl><picture>>` 
					JOIN `<<tbl><album>>` ON `<<foreign><picture><album>>`=`<<album><id>>`
				WHERE `<<isdel><picture>>`='0'
					AND `<<picture><confirmed>>`='1'
					AND `<<isdel><album>>`='0'
					AND `<<picture><id>>` NOT IN 
					(
						SELECT `<<foreign><userpictureview><picture>>`
							FROM `<<tbl><userpictureview>>`
							WHERE `<<foreign><userpictureview><user>>`='" . $objUser->get('id') . "'
								AND `<<isdel><userpictureview>>`='0'
					)
				ORDER BY `<<picture><date>>` DESC
					");

		$arrRet = array();
		foreach($arrPictures as $objPicture)
		{
			$objAlbum = new clsAlbum($objPicture->get('album_id')); /* TODO: Speed this up? */

			if($objAlbum->canView($objUser))
				$arrRet[] = new clsPicture($objPicture->get('id'));
		}

		return $arrRet;
	}
}



?>
