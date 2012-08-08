<?php
	require_once('cls/clsAlbum.php');
	require_once('cls/clsParameters.php');
	require_once('cls/clsTemplate.php');
	require_once('cls/clsThumbnail.php');
	require_once('cls/clsUser.php');

	/* Require users to be admins before they can come here. */
	if(!$objUser || !$objUser->get('is_admin'))
		throw new Exception('exception_accessdenied');
	
	$objTemplate->setText('PAGETITLE', 'Admin');

	$objBreadcrumbs->add('Admin', 'index.php?action=admin');
	$objMiniMenu->add('Import Data', 'index.php?action=admin&subaction=import');
	$objMiniMenu->add('Global Settings', 'index.php?action=admin&subaction=settings');

	/* Doesn't actually correspond to a table, but is nice to have. */
	$objAdmin = new clsDB('admin');

	if($strSubAction == '')
	{
	}
	else if($strSubAction == 'import') /* Import data from 'ospap' */
	{
		$objBreadcrumbs->add('Import', 'index.php?action=admin&subaction=import');

		$objAdmin->set('path', "../ospap");

		print "Importing from OSPAP...<br>";
		print "<form method='get' action='index.php'>";
		print "<input type='hidden' name='action' value='admin'>";
		print "<input type='hidden' name='subaction' value='import2'>";
		print "Path to OSPAP " . $objAdmin->getTextField('path') . "<br>";
		print $objAdmin->getSubmit('Go');
		print "</form>";
	}
	else if($strSubAction == 'import2') /* Go through the albums from ospap1, and siplay them. */
	{
		$objBreadcrumbs->add('Import', 'index.php?action=admin&subaction=import');

		$arrUserOptions = clsDB::getOptionsFromTable('user', 'username', 'id', "Don't import");

		$objAdmin->getFromRequest();

		require_once($objAdmin->get('path') . "/configuration.php");

		print "<form method='get' action='index.php'>";
		print "<input type='hidden' name='action' value='admin'>";
		print "<input type='hidden' name='subaction' value='import3'>";
		print "Path to OSPAP " . $objAdmin->getTextField('path') . "<br>";

		$conDB = mysql_connect($db_host, $db_read_user, $db_read_pass);
		if(!mysql_select_db($db_name, $conDB))
		{
			print("Error: couldn't connect to the ospap database: " . mysql_error($conDB));
		}
		else
		{	
			$result = mysql_query("SELECT * FROM users JOIN categories ON users.user_id = categories.user_id ORDER BY users.username, categories.name");

			if(!$result)
			{
				print "Query error: " . mysql_error($conDB);
			}
			else
			{
				print "<table>";
				print "<th>Original Owner</td>";
				print "<th>Category Name</td>";
				print "<th>Give To</td>";
				while($arrResult = mysql_fetch_assoc($result))
				{
					print "<tr>";
					print "<td>" . $arrResult['username'] . "</td>";
					print "<td>" . $arrResult['name'] . "</td>";
					print "<td>" . $objAdmin->getCombo('category' . $arrResult['category_id'], $arrUserOptions) . "</td>";
					print "</tr>";
				}
				print "</table>";
			}
		}
	
		print $objAdmin->getSubmit('Go');
		print "</form>";
	}
	else if($strSubAction == 'import3') /* Save the imported stuff. */
	{
		$objBreadcrumbs->add('Import', 'index.php?action=admin&subaction=import');

		$objAdmin->getFromRequest();

		require_once($objAdmin->get('path') . "/configuration.php");

		$conDB = mysql_connect($db_host, $db_read_user, $db_read_pass);
		if(!mysql_select_db($db_name, $conDB))
		{
			print("Error: couldn't connect to the ospap database: " . mysql_error($conDB));
		}
		else
		{	
			$result = mysql_query("SELECT * FROM categories");

			$arrAlbums = array();
			$i = 0;
			while($arrResult = mysql_fetch_assoc($result))
			{
				if(!$objAdmin->exists('category' . $arrResult['category_id']) || $objAdmin->get('category' . $arrResult['category_id']) == 0)
				{
					print "Skipping '" . $arrResult['name'] . "'<br>";
					continue;
				}

				$user_id = $objAdmin->get('category' . $arrResult['category_id']);
				$objOwner = new clsUser($user_id);

				/* Create the album if we haven't already. */
				if(!isset($arrAlbums[$arrResult['category_id']]))
				{
					$objAlbum = new clsAlbum();
					$objAlbum->set('name', str_replace("<br />", "", html_entity_decode($arrResult['name'])));
					$objAlbum->set('caption', str_replace("<br />", "", html_entity_decode($arrResult['caption'])));
					$objAlbum->set('date', date('Y-m-d H:i:s', strtotime($arrResult['date_created']) + ($i++)), false); /* Adding '$i' here is a bit of a kludge, but it keeps dates sortable (since ospap1 didn't keep track of times). */
					$objAlbum->set('user_id', $user_id);
					$objAlbum->set('mime', 'image/jpeg');
					$objAlbum->set('max_width', '640');
					$objAlbum->set('max_height', '480');
					$objAlbum->setDefaultPolicies($objOwner);
					$objAlbum->save();

					$arrAlbums[$arrResult['category_id']] = $objAlbum;
				}

				$objAlbum = $arrAlbums[$arrResult['category_id']];
				print "Importing from '" . $objAlbum->get('name') . "'<br>";
				$i = 0;

				$pictureResult = mysql_query("SELECT * FROM pictures WHERE category_id = '" . $arrResult['category_id'] . "' ");
				while($arrPictureResult = mysql_fetch_assoc($pictureResult))
				{
					$objPicture = clsPicture::createFromFile($upload_directory . '/' . $arrPictureResult['filename'], 'image/jpeg', $objAlbum);
					$objPicture->set('user_id', $user_id);
					$objPicture->set('album_id', $objAlbum->get('id'));
					$objPicture->set('title', str_replace("<br />", "", html_entity_decode($arrPictureResult['title'])));
					$objPicture->set('caption', str_replace("<br />", "", html_entity_decode($arrPictureResult['caption'])));
					$objPicture->set('date', date('Y-m-d H:i:s', strtotime($arrPictureResult['date_added']) + ($i++)), false);
					$objPicture->set('confirmed', 1);
					$objPicture->save();

					print "<img src='" . clsThumbnail::getUrl($objPicture, 70, 70) . "'> ";
					if(++$i % 6 == 0)
						print "<br>";
				}
				print "<br><br>";
			}
		}
	}

	if($strSubAction == 'settings_save')
	{
		$objSetting = new clsDB('setting');
		$objSetting->getFromRequest(array('id', 'value'));
		$objSetting->save();

		$strSubAction = 'settings';
	}

	if($strSubAction == 'settings')
	{
		$arrSettings = clsDB::getListStatic('setting');

		print "<table>";
		print "<tr>";
		print "<td>Name</td><td>Value</td><td>Comments</td><td>Save</td>";
		print "</tr>";

		foreach($arrSettings as $objSetting)
		{
			print "<form action='index.php' method='get'>";
			print $objSetting->getHiddenField('id');
			print "<input type='hidden' name='action'    value='admin'>";
			print "<input type='hidden' name='subaction' value='settings_save'>";
			print "<tr>";
			print "<td>" . $objSetting->get('name') . "</td>";
			print "<td>" . $objSetting->getTextField('value') . "</td>";
			print "<td>" . $objSetting->get('comment') . "</td>";
			print "<td>" . $objSetting->getSubmit('Save') . "</td>";
			print "</tr>";
			print "</form>";
			print "<tr><td>&nbsp;</td></tr>";
		}
		print "</table>";
	}
?>
