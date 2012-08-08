<?php
	require_once('include/constants.php');
	require_once('include/functions.php');
	require_once('include/messages.php');

	require_once('cls/clsAlbum.php');
	require_once('cls/clsBreadcrumbs.php');
	require_once('cls/clsDB.php');
	require_once('cls/clsMiniMenu.php');
	require_once('cls/clsPicture.php');
	require_once('cls/clsSetting.php');
	require_once('cls/clsTemplate.php');
	require_once('cls/clsUser.php');

	ini_set('memory_limit', '256M');
	ini_set('max_execution_time', '600'); 

	session_start();
	clsSetting::load_settings();


	try
	{
		/* Update the DB if we need to. */
		require_once('include/upgrade.php');

		$strAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$strSubAction = isset($_REQUEST['subaction']) ? $_REQUEST['subaction'] : '';
		$objBreadcrumbs = new clsBreadcrumbs();
		$objBreadcrumbs->add('Home', 'index.php');
		$objMiniMenu = new clsMiniMenu();

		if(!isset($_SESSION['objUser']))
			$objUser = clsUser::getCookie();
		else
			$objUser = $_SESSION['objUser'];

		/* This re-loads the user object in case it's changed. */
		if($objUser)
			$objUser = new clsUser($objUser->get('id'));

		if($objUser && $objUser->isNew())
			$objUser = null;
	
		if(!preg_match('/^[a-zA-Z2-9_-]*$/', $strAction))
			throw new Exception(ERRORMSG_INVALID);

		

		$objTemplate = new clsTemplate('default');
	
		$objTemplate->setText('SCRIPT', clsDB::initializeJS());
		$objTemplate->setText('TITLE', "OSPAP2");

		/* Inline CSS for advanced. */
		$objTemplate->setText('HEAD', clsUser::getAdvancedStyle($objUser));
	
		if(isset($_REQUEST['error']) && isset($arrMessages[$_REQUEST['error']]))
			$objTemplate->setText('ERROR', $arrMessages[$_REQUEST['error']]);
		else if(isset($_REQUEST['message']) && isset($arrMessages[$_REQUEST['message']]))
			$objTemplate->setText('MESSAGE', $arrMessages[$_REQUEST['message']]);
	
		$objTemplate->setScript('MENU', 'menu');
	
		$objTemplate->setScript('LOGO', 'logo');
		$objTemplate->setText('COPYRIGHT', "Written by <a href='mailto:ronospap@skullsecurity.org'>Ron</a>. This page and code are public domain. Code is available upon request. No warranty or promises of any kind.");
	
		switch($strAction)
		{
			case '':
				$objTemplate->setScript('CONTENT', 'news');
				break;
	
			case 'login':
				$objTemplate->setScript('CONTENT', 'login');
				break;
	
			case 'logout':
				$objTemplate->setScript('CONTENT', 'logout');
				break;

			case 'upload':
				$objTemplate->setScript('CONTENT', 'upload');
				break;

			case 'albums':
				$objTemplate->setScript('CONTENT', 'albums');
				break;

			case 'picture':
				$objTemplate->setScript('CONTENT', 'picture');
				break;

			case 'admin':
				$objTemplate->setScript('CONTENT', 'admin');
				break;

			case 'comment':
				$objTemplate->setScript('CONTENT', 'comment');
				break;

			case 'members':
				$objTemplate->setScript('CONTENT', 'members');
				break;

			case 'groups':
				$objTemplate->setScript('CONTENT', 'groups');
				break;

			case 'forgotpassword':
				$objTemplate->setScript('CONTENT', 'forgotpassword');
				break;

			default:
				$objTemplate->setScript('CONTENT', 'error');
				break;
		}

		$objTemplate->setScript('RECENTALBUMS', 'recentalbums');
		$objTemplate->setScript('RECENTPICTURES', 'recentpictures');
		$objTemplate->setScript('BREADCRUMBS', 'breadcrumbs');
		$objTemplate->setScript('MINIMENU',    'minimenu');
		$objTemplate->setScript('WELCOME', 'welcome'); /* Welcome has to be at the bottom, so that comments/pictures being seen aren't counted. */
	
		echo $objTemplate->get();

	}
	catch(Exception $e)
	{
        echo "Sorry, there was an error that couldn't be handled. Please try again later! <br><br>";
        if(DEBUG)
        {
            print "Please report this error along with the following text to <a href='mailto:ronospap@skullsecurity.org'>Ron</a>:";
            print "<pre>";
            print $e;
            print "</pre>";
        }
	}
?>
