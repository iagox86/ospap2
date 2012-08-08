<?php

if($strSubAction == '')
{
	$objTemplate->setText('PAGETITLE', "Password Recovery");
	$objBreadcrumbs->add('Password Recovery', 'index.php?action=forgotpassword');

	$objRecoverUser = new clsUser();
	print "<form action='index.php' method='get'>";
	print "<input type='hidden' name='action' value='forgotpassword'>";
	print "<input type='hidden' name='subaction' value='go'>";
	print "Your account name: " . $objRecoverUser->getTextField('username') . "<br>";
	print $objRecoverUser->getSubmit('Recover');
	print "</form>";
}


if($strSubAction == 'go')
{
	$objRecoverUser = new clsUser();
	$objRecoverUser->getFromRequest();

	$strResult = clsUser::attemptRecover($objRecoverUser->get('username'));

	header("Location: index.php?message=$strResult");
}

?>
