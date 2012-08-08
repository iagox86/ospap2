<?php
	require_once('cls/clsParameters.php');
	require_once('cls/clsTemplate.php');

	$objTemplate->setText('PAGETITLE', 'News');

	if($strSubAction == '' || $strSubAction == 'archive')
	{
		if($strSubAction == 'archive')
		{
			$arrNews = clsDB::getListStatic('news', "", 'date', 'DESC');
		}
		else
		{
			$arrNews = clsDB::getListStatic('news', "", 'date', 'DESC', 0, 5);
			$objMiniMenu->add('News Archive', 'index.php?subaction=archive');
		}
	
		if($objUser && $objUser->get('is_admin'))
			$objMiniMenu->add('Post News', 'index.php?subaction=edit');

		$objMiniMenu->add('Upload Image', 'index.php?action=upload');
		$objMiniMenu->add('Pending Uploads', 'index.php?action=upload&subaction=preview');
	
	
		foreach($arrNews as $objNews)
		{
			if($objUser && $objUser->get('is_admin'))
				$objNewsTemplate = new clsTemplate('newsitemadmin');
			else
				$objNewsTemplate = new clsTemplate('newsitem');
		
			$objNewsUser = $objNews->getForeignObject('user');
			$objAlbum = $objNews->getForeignObject('album');


			$objNewsTemplate->setText('ID',       $objNews->get('id'));
			$objNewsTemplate->setText('USERID',   $objNewsUser->get('id'));
			$objNewsTemplate->setText('USERNAME', $objNewsUser->get('username'));
			$objNewsTemplate->setText('DATE',     date('Y-m-d', strtotime($objNews->get('date'))));
			$objNewsTemplate->setText('TITLE',    $objNews->get('title'));
			$objNewsTemplate->setText('TEXT',     bbcode_format($objNews->get('text')));
	
			echo $objNewsTemplate->get();
		}
	}
	else
	{
		if(!$objUser || $objUser->get('is_admin') != 1)
			throw new Exception("exception_accessdenied");

		$objNews = new clsDB('news');
		$objNews->getFromRequest(array('id', 'title', 'text'));

		if($strSubAction == 'edit')
		{
			$objNews->load();

			echo "<form action='index.php' method='post'>";
			echo "<input type='hidden' name='subaction' value='save'>";
			echo $objNews->getHiddenField('id');

			echo "Title:<br>";
			echo $objNews->getTextField('title', new clsParameters('size', 40)) . "<br><br>";

			echo "Post:<br>";
			echo $objNews->getTextArea('text', 4, 45) . "<br><br>";

			echo $objNews->getSubmit('Post');
		}
		else if($strSubAction == 'save')
		{
			if($objNews->isNew())
			{
				$objNews->set('user_id', $objUser->get('id'));
				$objNews->set('date', date('Y-m-d H:i:s'));
			}
			$objNews->save();

			header("Location: index.php");
		}
		else if($strSubAction == 'delete')
		{
			$objNews->delete();
			$objNews->save();

			header("Location: index.php");
		}

	}

?>
