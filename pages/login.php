<?php

	$objUser = new clsUser();
	$objUser->getFromRequest();

	if($objUser->exists('password'))
	{
		if($objUser->verify())
		{
			$_SESSION['objUser'] = $objUser;
			$objUser->setCookie();
			header("Location: index.php?message=login_successful");
		}
		else if($objUser->verifyTemp())
		{
			$_SESSION['objUser'] = $objUser;
			$objUser->setCookie();
			header("Location: index.php?action=members&subaction=view&" . $objUser->getIDPair() . "&message=login_successfultemp");
		}
		else
		{
			header("Location: index.php?action=login&error=login_failed&" . $objUser->getValuePair('username'));
		}

		exit;
	}

	if($objUser->isNew())
	{
		$objTemplate->setText('PAGETITLE', "Logging in");
		$objBreadcrumbs->add('Login', 'index.php?action=login');
		$objMiniMenu->add('Register', 'index.php?action=members&subaction=view');

		if($objUser->exists('username'))
			$objTemplate->setText('ONLOAD', "$('" . $objUser->getFieldName('password') . "').focus();");
		else
			$objTemplate->setText('ONLOAD', "$('" . $objUser->getFieldName('username') . "').focus();");

?>
		<form action='<?=$_SERVER['PHP_SELF']?>' method='POST'>
			<input type='hidden' name='action' value='login'>
	
			<table>
				<tr>
					<td>Username</td>
					<td><?=$objUser->getTextField('username')?></td>
				</tr>
				<tr>
					<td>Password</td>
					<td><?=$objUser->getPasswordField('password')?></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><?=$objUser->getSubmit('Login')?></td>
				</tr>
				<tr>
					<td colspan='2'><a href='index.php?action=forgotpassword'>Forgot Password</a></td>
				</tr>
			</table>
		</form>
<?php
	}

	unset($objUser);

?>
