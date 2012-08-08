<?php

	require_once('cls/clsUser.php');

	unset($_SESSION['objUser']);
	clsUser::clearCookie();

	header("Location: index.php?message=logout_successful");

?>
