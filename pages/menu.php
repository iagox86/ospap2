<?php
	$blnAdmin = false;
	$blnLoggedIn = false;

	if($objUser && $objUser->get('is_admin'))
		$blnAdmin = $blnLoggedIn = true;
	else if($objUser)
		$blnLoggedIn = true;

	print "<ul id='menu'>";
	print "	<li><a href='index.php'>Home</a></li>";
	print "	<li><a href='index.php?action=albums'>Albums</a></li>";
	print "	<li><a href='index.php?action=members'>Members</a></li>";
	print "	<li><a href='index.php?action=groups'>Groups</a></li>";
	print "	<li><a href='index.php?action=upload'>Post Picture</a></li>";

	if($blnLoggedIn)
	{
		print "	<li><a href='index.php?action=logout'>Logout</a></li>";
	}
	else
	{
		print "<li class='menu'><a href='index.php?action=login'>Log in</a></li>";
		print "<li class='menu'><a href='index.php?action=members&subaction=view'>Register</a></li>";
	}

	if($blnAdmin)
		print "<li><a href='index.php?action=admin'>Admin</a></li>";

	print "</ul>";
?>
