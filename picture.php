<?php
	require_once('cls/clsAlbum.php');
	require_once('cls/clsDB.php');
	require_once('cls/clsPicture.php');
	require_once('cls/clsSetting.php');
	require_once('cls/clsThumbnail.php');
	require_once('cls/clsUser.php');

	require_once('include/messages.php');

	session_start();
	clsSetting::load_settings();

	try
	{
        if(!isset($_SESSION['objUser']))
            $objUser = clsUser::getCookie();
        else
            $objUser = $_SESSION['objUser'];

		$objPicture = new clsPicture();
		$objPicture->getFromRequest(array('id'));
		$objPicture->load();

		if($objPicture->isnew())
			throw new Exception('exception_invalidrequest');

		$objAlbum = new clsAlbum($objPicture->get('album_id'));
		if(!$objAlbum->canView($objUser))
			throw new Exception('exception_invalidrequest');

		if(isset($_REQUEST['tn']) || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'tn'))
		{
			$intWidth  = isset($_REQUEST['w']) ? $_REQUEST['w'] : -1;
			$intHeight = isset($_REQUEST['h']) ? $_REQUEST['h'] : -1;

			if(!is_numeric($intWidth)  || $intWidth  < 0 || $intWidth  > MAX_X)
				throw new Exception('exception_invalidrequest');
			if(!is_numeric($intHeight) || $intHeight < 0 || $intHeight > MAX_Y)
				throw new Exception('exception_invalidrequest');

			$objThumbnail = clsThumbnail::getThumbnail($objUser, $objPicture->get('id'), $intWidth, $intHeight, $objAlbum);

			if(is_string($objThumbnail))
				throw new Exception($objThumbnail);

			if($objPicture->hasViewed($objUser))
			{
				header('Last-modified: ' . $objThumbnail->get('date'));
				header('Expires: ' . (date('Y-m-d H:i:s') + (60 * 60 * 24)));
				header('Cache-control: public');
				header('Pragma: public');
			}

			$objThumbnail->setMime();
			echo $objThumbnail->getImageRaw($objUser);
		}
		else
		{
			$objPicture->load();

			header('Last-modified: ' . $objPicture->get('date'));
			header('Expires: ' . (date('Y-m-d H:i:s') + (60 * 60 * 24)));
			header('Cache-control: public');
			header('Pragma: public');

			$objPicture->setMime();
			echo $objPicture->getImageRaw($objUser);
		}
	}
	catch(Exception $e)
	{	
die($e);
		$intWidth = 640;
		$intHeight = 480;
		if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'tn')
		{
			$intWidth  = isset($_REQUEST['w']) ? $_REQUEST['w'] : $intWidth;
			$intHeight = isset($_REQUEST['h']) ? $_REQUEST['h'] : $intHeight;
		}

        $imgError       = imagecreatetruecolor($intWidth, $intHeight); /* Create a black image */
        $colBackground  = imagecolorallocate($imgError, 255, 255, 255);
        $colForeground  = imagecolorallocate($imgError, 0, 0, 128);
        imagefilledrectangle($imgError, 0, 0, MAX_X, MAX_Y, $colBackground);
        imagestring($imgError, 4, 5, 5, "Error: " . $arrMessages[$e->getMessage()], $colForeground);

		header("Content-type: image/jpeg");
		echo imagejpeg($imgError);
	}
?>
