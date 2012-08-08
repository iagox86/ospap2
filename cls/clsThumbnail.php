<?php

require_once('cls/clsDB.php');
require_once('cls/clsPicture.php');

require_once('include/functions.php');


class clsThumbnail extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('thumbnail', $id, $row);
	}

	private static function getNewImage()
	{
		return ImageCreateFromPng('images/new.png');
	}

	private static function getNewSize()
	{
		return GetImageSize('images/new.png');
	}

	public function getImageRaw($objUser)
	{
		$objPicture = new clsPicture($this->get('picture_id'));

		if($objPicture->hasViewed($objUser))
			return base64_decode($this->getFrom('imagedata', 'data'));

		$mime_type = $objPicture->getFrom('album', 'mime');
		if(!$mime_type || $mime_type == '')
			$mime_type = DEFAULT_MIME;

		$img = $this->getImage($objUser);
		$ret = imgToString($img, $mime_type);
		ImageDestroy($img);

		return $ret;
	}

	public function getImage($objUser)
	{
		$img = ImageCreateFromString(base64_decode($this->getFrom('imagedata', 'data')));

		$objPicture = new clsPicture($this->get('picture_id'));

		if(!$objPicture->hasViewed($objUser))
		{
			$newImage = clsThumbnail::getNewImage();
			list($newWidth, $newHeight) = clsThumbnail::getNewSize();

			ImageCopyMerge($img, $newImage, $this->get('actual_width') - $newWidth, $this->get('actual_height') - $newHeight, 0, 0, $newWidth, $newHeight, 75);
			ImageDestroy($newImage);
		}

		return $img;
	}

	public function setImage($img)
	{
		$objPicture = $this->getForeignObject('picture');
		$objAlbum = $objPicture->getForeignObject('album');

		$mime_type = $objAlbum->get('mime');
		if(!$mime_type || $mime_type == '')
			$mime_type = DEFAULT_MIME;

		$objImageData = new clsDB('imagedata');
		$objImageData->set('data',   base64_encode(imgToString($img, $mime_type)), false);
		$objImageData->set('mime',   $mime_type);
		$objImageData->save();

		$this->set('imagedata_id', $objImageData->get('id'));

		return 0;
	}

	public static function getThumbnail($objUser, $picture_id, $intWidth, $intHeight, $objAlbum)
	{
		if(!is_numeric($picture_id))
			throw new Exception(INVALID_VALUE);
		if(!is_numeric($intWidth))
			throw new Exception(INVALID_VALUE);
		if(!is_numeric($intHeight))
			throw new Exception(INVALID_VALUE);

		/* This prevents the thumbnails from being bigger than the original. */
		if(!$objAlbum->isNew())
		{
			$intWidth  = min($intWidth,  $objAlbum->get('max_width'));
			$intHeight = min($intHeight, $objAlbum->get('max_height'));
		}

		$arrThumbnails = clsDB::getListStatic('thumbnail', "`<<foreign><thumbnail><picture>>`='$picture_id' AND `<<thumbnail><width>>`='$intWidth' AND `<<thumbnail><height>>`='$intHeight'");

		if(sizeof($arrThumbnails) == 0)
		{
			$objPicture = new clsPicture($picture_id);
			list($img, $intActualWidth, $intActualHeight) = $objPicture->getResized($objUser, $intWidth, $intHeight);

			if(is_string($img))
				return $img;

			$objThumbnail = new clsThumbnail();
			$objThumbnail->set('picture_id',    $picture_id);
			$objThumbnail->set('width',         $intWidth);
			$objThumbnail->set('height',        $intHeight);
			$objThumbnail->set('actual_width',  $intActualWidth);
			$objThumbnail->set('actual_height', $intActualHeight);
			$objThumbnail->set('date',       date('Y-m-d H:i:s'));

			$result = $objThumbnail->setImage($img);
			if($result)
				return $result;

			$objThumbnail->save();
		}
		else
		{
			$objThumbnail = new clsThumbnail($arrThumbnails[0]->get('id'));
		}

		return $objThumbnail;
	}

	public function setMime()
	{
		header("Content-type: " . $this->getFrom('imagedata', 'mime'));
	}

	public static function getUrl($objPicture, $width, $height)
	{
		return "picture.php?action=tn&w=$width&h=$height&" . $objPicture->getIDPair();
	}

	public static function getImg($objPicture, $width, $height)
	{
		return "<img src='" . clsThumbnail::getUrl($objPicture, $width, $height) . "' />";
	}

}

?>
