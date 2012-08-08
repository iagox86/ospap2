<?php

	if(isset($_SESSION['e']))
	{
		$objTemplate->setText('PAGETITLE', 'Unhandled Error');
		$objBreadcrumbs->add('Error', null);
	
		echo "Sorry, there was an error that couldn't be handled. Please try again later! <br><br>";
		if(DEBUG)
		{
			print "Please report this error along with the following text to <a href='mailto:ronospap@skullsecurity.org'>Ron</a>:";
			print "<pre>";
			print $_SESSION['e'] . '';
			print "</pre>";
		}

		unset($_SESSION['e']);
	}
	else
	{
		header("Location: index.php");
	}
?>
