<?php
	$arrMessages = array();

	$arrMessages['test']                      = "Testing!";

	$arrMessages['login_failed']              = "Sorry, either your username or password was incorrect.";
	$arrMessages['login_successful']          = "Successfully logged in.";
	$arrMessages['login_successfultemp']      = "Successfully logged in with your temporary password. Please change your pass!";
	$arrMessages['logout_failed']             = "Sorry, your logout failed. This isn't actually possible, and this error isn't used anywhere, so congratulations on finding it!";
	$arrMessages['logout_successful']         = "Successfully logged out.";
	$arrMessages['register_inuse']            = "Sorry, that username is already in use. Please pick another.";
	$arrMessages['register_dontmatch']        = "Your passwords don't match, please try again.";
	$arrMessages['register_blank']            = "Please don't use a blank password.";
	$arrMessages['register_successful']       = "Your account is now registered. Enjoy!";

	$arrMessages['edit_successful']           = "Changes have been saved.";

	$arrMessages['exception_multiplenames']   = "Multiple users are using the same name. This shouldn't be possible; please report a bug!";
	$arrMessages['exception_templatemissing'] = "Template not found!";
	$arrMessages['exception_accessdenied']    = "Access Denied.";
	$arrMessages['exception_notloggedin']     = "You have to be logged in to perform that action. If you got here by clicking a link, please report a bug!";
	$arrMessages['exception_invalidrequest']  = "An invalid request was made. Please try again and/or report a bug!";
	$arrMessages['exception_internalerror']   = "An internal error has occurred. Please report a bug!";

	$arrMessages['image_nosave']              = "Couldn't save the image.";
	$arrMessages['image_noresize']            = "Couldn't resize the image.";
	$arrMessages['image_filetype']            = "Unknown filetype.";
	$arrMessages['image_nozip']               = "Sorry, this server doesn't support .zip file uploads.";
	$arrMessages['image_nofiles']             = "Couldn't find any image files in the .zip.";

	$arrMessages['tn_nosave']                 = "Couldn't save thumbnail.";
	$arrMessages['tn_noresize']               = "Couldn't resize thumbnail.";
	$arrMessages['tn_filetype']               = "Unknown filetype.";

	$arrMessages['group_saved']               = "Group has been saved.";
	$arrMessages['groupjoin_success']         = "Successfully joined the group.";
	$arrMessages['groupjoin_pending']         = "Successfully applied for approval.";
	$arrMessages['groupjoin_error']           = "Join failed.";

	$arrMessages['groupinvite_successful']    = "User successfully invited.";
	$arrMessages['groupinvite_alreadyin']     = "User was already a group member.";
	$arrMessages['groupinvite_notmember']     = "Only members can make invitations.";
	$arrMessages['groupinvite_error']         = "User invite failed.";

	$arrMessages['groupapprove_success']      = "User successfully approved.";
	$arrMessages['groupapprove_error']        = "Error approving user.";

	$arrMessages['groupaccept_success']       = "Invitation successfully accepted.";
	$arrMessages['groupaccept_error']         = "Error accepting invitation.";

	$arrMessages['groupdecline_success']      = "Invitation successfully declined.";
	$arrMessages['groupdecline_error']        = "Error declining invitation.";

	$arrMessages['groupcancel_success']       = "Request cancelled.";
	$arrMessages['groupcancel_error']         = "Error cancelling request.";

	$arrMessages['groupleave_success']        = "Successfully left group.";
	$arrMessages['groupleave_error']          = "Error leaving group.";

	$arrMessages['forgot_unknown']            = "No such username.";
	$arrMessages['forgot_noemail']            = "There is no email on file for that user. You'll have to ask an admin to reset your password.";
	$arrMessages['forgot_success']            = "A temporary password has been emailed to the address on file.";

	$arrMessages['smtp_disabled']             = "The admin has disabled email functionality on this server.";
	$arrMessages['smtp_connect_failed']       = "Couldn't connect to the SMTP server.";
	$arrMessages['smtp_invalid_response']     = "The SMTP server responded in a way that couldn't be understood.";
	$arrMessages['smtp_error']                = "An error occurred when contacting the SMTP server.";
	$arrMessages['smtp_success']              = "Email sent successfully.";








?>
