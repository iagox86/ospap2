<?php

	$objTemplate->setText('PAGETITLE', '404 Page Not Found');
	$objBreadcrumbs->add('Error', null);

	echo "Sorry, the page you requested doesn't exist! This is likely because of a broken link (in which case, please report which link). It may also be because you're fiddling (in which case, keep up the good work!)";
?>
