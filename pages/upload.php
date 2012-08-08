<?php

	require_once('cls/clsAlbum.php');
	require_once('cls/clsPicture.php');
	require_once('cls/clsTemplate.php');

	$strMiniMenu = "<li><a href='index.php?action=upload&subaction=preview'>Pending Uploads</a></li>";
	$objTemplate->setText('MINIMENU', "<ul>$strMiniMenu</ul>");

	$objTemplate->setText('SCRIPT', <<<EOF
		function addRow(tbody_id)
		{
			var tbody = $(tbody_id);

			var newRow = $('firstrow').cloneNode(true);
			newRow.cells[0].innerHTML = 'Image ' + (tbody.rows.length + 1);
			newRow.cells[1].firstChild.name = 'file' + (tbody.rows.length + 1);
			newRow.cells[1].firstChild.value = '';
			tbody.appendChild(newRow);
		}

		function addRows(tbody_id, num)
		{
			num = num * 1; /* JS Kludge! */
			for(i = 0; i < num; i++)
				addRow(tbody_id);
		}
EOF
);

	if($strSubAction == '')
	{
		$objAlbum = new clsAlbum();
		$objAlbum->getFromRequest(array('id'));
		$objAlbum->load();

		if(!$objAlbum->canPostPicture($objUser))
			throw new Exception('exception_accessdenied');

		if($objAlbum->isNew())
			$objTemplate->setText('PAGETITLE', "Uploading images");
		else
			$objTemplate->setText('PAGETITLE', "Uploading images to '" . $objAlbum->get('name') . "'");

		$objMiniMenu->add('Pending Uploads', 'index.php?action=upload&subaction=preview');
		$objBreadcrumbs->add('Albums', 'index.php?action=albums');
		$objAlbum->addBreadcrumbs($objBreadcrumbs);
		$objBreadcrumbs->add('Upload', 'index.php?action=upload');

?>
		<form action='<?=$_SERVER['PHP_SELF']?>' method='POST' enctype='multipart/form-data'>
			<table width='100%'>
				<tr>
					<td align='right' width='100%'>
						Add <input type='text' size='3' id='numtoadd' value='5'> files...  <input type='button' onClick='addRows("files", $("numtoadd").value); $("addrows2").style.display="table-row";' value='Go!'>
					</td>
				</tr>
<?php
				if(!$objUser)
				{
					print "<tr><td width='100%'>Your name: <input type='text' name='username'></td></tr>";
				}

				print "<tr><td>";
				if(class_exists("ZipArchive"))
					print "In addition to regular images, you may upload a .zip file.";
				else
					print "Server doesn't support .zip files.";

				print "</td></tr>";
?>
				<tr>
					<td width='100%'>
<?php					print $objAlbum->getHiddenField('id'); ?>
						<input type='hidden' name='action' value='upload'>
						<input type='hidden' name='subaction' value='save'>
				
						<table>
							<tbody id='files'>
								<tr id='firstrow'>
									<td>Image 1</td>
									<td><input type='file' name='file1' id='file1'></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr id='addrows2' style='display: none;'>
					<td align='right' width='100%'>
						Add <input type='text' size='3' id='numtoadd2' value='5'> files...  <input type='button' onClick='javascript:addRows("files", $("numtoadd2").value);' value='Go!'>
					</td>
				</tr>
				<tr>
					<td align='right' width='100%'>
						<span id='notification' class='message'></span><br>
						<input type='submit' value='Save' onClick="$('notification').innerHTML = 'Image(s) uploading, please be patient...';"><br>
					</td>
				</tr>
			</table>
		</form>
<?php
	}

	if($strSubAction == 'save')
	{
		$objAlbum = new clsAlbum();
		$objAlbum->getFromRequest(array('id'));
		$objAlbum->load();
		if(!$objAlbum->canPostPicture($objUser))
			throw new Exception('exception_accessdenied');

		foreach($_FILES as $strName=>$arrFile)
		{
			$tmp_name = $arrFile['tmp_name'];
			$name     = $arrFile['name'];
			$type     = $arrFile['type'];
			$size     = $arrFile['size'];

			$error    = $arrFile['error'];

			if($error)
			{
				switch($error)
				{
					case UPLOAD_ERR_INI_SIZE:
						print "<span class='error'>The uploaded file is bigger than the allowed size. Please choose a smaller file or ask the server admin to change upload_max_filesize.</span><br>";
						break;

					case UPLOAD_ERR_FORM_SIZE:
						print "<span class='error'>The uploaded file is bigger than the form-specified size. Please choose a small file or ask the server admin to change the form.</span><br>";
						break;

					case UPLOAD_ERR_PARTIAL:
						print "<span class='error'>The file was only partially uploaded.</span><br>";
						break;

					case UPLOAD_ERR_NO_FILE:
						print "<span class='error'>No file was given.</span><br>";
						break;

					case UPLOAD_ERR_NO_TMP_DIR:
						print "<span class='error'>The server's temp file couldn't be located. Please contact the server administrator.</span><br>";
					break;

					case UPLOAD_ERR_CANT_WRITE:
						print "<span class='error'>File couldn't be written to the server. Drive may be full or read-only. Please contact the server administrator</span><br>";
						break;

					case UPLOAD_ERR_EXTENSION:
						print "<span class='error'>The file type you tried uploading is blocked.</span><br>";
						break;

					default:
						print "<span class='error'>" . htmlentities($name) . " failed to upload for an unknown reason. </span><br>";
				}
				continue;
			}


			if(!file_exists($tmp_name))
			{
				print "<span class='error'>" . htmlentities($name) . " failed to upload for an unknown reason. </span><br>";
				continue;
			}

			if($type == 'application/zip' || $type == 'application/octet-stream')
			{
				$arrPictures = clsPicture::createFromArchive($tmp_name, $objAlbum);

				if(is_string($arrPictures))
				{
					print "<span class='error'>'" . htmlentities($name) . "' failed to upload: '<strong>" . $arrMessages[$arrPictures] . "</strong>'</span><br>";
				}
				else
				{
					foreach($arrPictures as $objPicture)
					{
						if($objPicture->exists('error'))
						{
							print "<span class='error'>'" . $objPicture->get('name') . "' failed to upload: '<strong>" . $arrMessages[$objPicture->get('error')] . "</strong>'</span><br>";
						}
						else
						{
							$objPicture->set('album_id', $objAlbum->get('id'));
							
							if($objUser)
							{
								$objPicture->set('user_id', $objUser->get('id'));
								$objPicture->set('username', $objUser->get('username'));
							}
							else
							{
								$objPicture->set('username', $_REQUEST['username']);
							}

							$objPicture->set('confirmed', 0);
							$objPicture->save();
							print "<span class='message'>'" . $objPicture->get('original_name') . "' was saved successfully.</span><br>";
						}
					}

					$strSubAction = 'preview';
				}
			}
			else
			{
				$objPicture = clsPicture::createFromFile($tmp_name, $type, $objAlbum);

				if(is_string($objPicture))
				{
					print "<span class='error'>'" . htmlentities($name) . "' failed to upload: '<strong>" . $arrMessages[$objPicture] . "</strong>'</span><br>";
				}
				else
				{
					$objPicture->set('album_id', $objAlbum->get('id'));
					if($objUser)
					{
						$objPicture->set('user_id', $objUser->get('id'));
						$objPicture->set('username', $objUser->get('username'));
					}
					else
					{
						$objPicture->set('username', $_REQUEST['username']);
					}

					$objPicture->set('original_name', $name);
					$objPicture->set('original_mime', $type);
					$objPicture->set('original_size', $size);
					$objPicture->set('confirmed', 0);
					$objPicture->save();

					print "<span class='message'>'" . $objPicture->get('original_name') . "' was saved successfully.</span><br>";
					$strSubAction = 'preview';
				}
			}
		}
	}

	if($strSubAction == 'confirm')
	{
		$user_id = $objUser ? $objUser->get('id') : 0;

		$objPicture = new clsPicture();
		$objPicture->getFromRequest(array('id', 'album_id', 'title', 'caption'));
		$objPicture->load();
		$objPicture->getFromRequest(array('id', 'album_id', 'title', 'caption'));

		if($objPicture->get('confirmed')) /* If the picture is already confirmed, just skip this. */
			$strSubAction = 'preview';
		else
		{ 
			if($objPicture->get('user_id') != $user_id)
				throw new Exception('exception_accessdenied'); /* Make sure that users can only edit their own pictures. */
	
			$objAlbum = new clsAlbum($objPicture->get('album_id'));
	
			if($objAlbum->isNew())
			{
				$objTemplate->setText('ERROR', "Please select an album for the picture.");
			}
			elseif($objAlbum->canPostPicture($objUser))
			{
				$objPicture->set('confirmed', 1);
				$objPicture->set('date', date('Y-m-d H:i:s'));
				$objPicture->save();
				$objTemplate->setText('MESSAGE', "Picture has been saved [<a href='index.php?action=albums&".$objAlbum->getIDPair()."'>Go to album</a>].");
			}
			else
			{
				$objTemplate->setText('ERROR', "You are not allowed to post pictures in that category.");
			}
			$strSubAction = 'preview';
		}
	}

	if($strSubAction == 'delete')
	{
		$user_id = $objUser ? $objUser->get('id') : 0;

		$objPicture = new clsPicture();
		$objPicture->getFromRequest();
		$objPicture->load();

		if($objPicture->get('user_id') != $user_id)
			throw new Exception('exception_accessdenied'); /* Make sure that users can only edit their own pictures. */

		$objPicture->delete();
		$objPicture->save();

		header("Location: index.php?action=upload&subaction=preview");
	}

	if($strSubAction == 'preview')
	{
		$objTemplate->setText('PAGETITLE', "Pending Pictures");
		$objBreadcrumbs->add('Upload', 'index.php?action=upload');
		$objBreadcrumbs->add('Pending', 'index.php?action=upload&subaction=preview');

		$arrPictures = clsPicture::getPending($objUser);

		print "You have <strong>" . sizeof($arrPictures) . "</strong> pictures waiting for attention". ($objUser ? "" : " (note: unsaved images from all guests will appear here)") . ":<br><br>";
		foreach($arrPictures as $objPicture)
		{
			$objPicture = new clsPicture($objPicture->get('id'));
			$objAlbum = new clsAlbum($objPicture->get('album_id'));

			$objTemplate = new clsTemplate('preview');
			$objTemplate->setText('HIDDEN', $objPicture->getHiddenField('id'));

			$objTemplate->setText('ALBUM', $objPicture->getCombo('album_id', clsDB::getOptionsFromList($objAlbum->getPostableAlbums($objUser), 'name', 'id', "Select an album")));


			$objTemplate->setText('ID', $objPicture->get('id'));
			$objTemplate->setText('IMAGE', $objPicture->getHtmlThumbnail(250, 250)); /* TODO: Customizable? */
			$objTemplate->setText('NAME', $objPicture->get('original_name'));
			$objTemplate->setText('WIDTH', $objPicture->get('width'));
			$objTemplate->setText('HEIGHT', $objPicture->get('height'));
			$objTemplate->setText('SAVEDELETE', $objPicture->getCombo('subaction', array('confirm'=>'Keep', 'delete'=>'Don\'t keep'), null, true));

			$objTemplate->setText('TITLE', $objPicture->getTextField('title'));
			$objTemplate->setText('CAPTION', $objPicture->getTextArea('caption'));

			$objTemplate->setText('SUBMIT', $objPicture->getSubmit('Save'));

			print $objTemplate->get();
		}
	}

?>
