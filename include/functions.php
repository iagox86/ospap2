<?php

function nl2brStrict($text, $replacement = '<br />')
{
	$text = preg_replace("((\r\n)+)", trim($replacement), $text);
	$text = preg_replace("((\n)+)",   trim($replacement), $text);
	$text = preg_replace("((\r)+)",   trim($replacement), $text);

	return $text;
}

/** This will take the query string from the server and replace a parameter in it with another
 * value.  This is useful for changing which column is being searched or which page is being 
 * displayed or something like that. */
function replaceParameterURL($strFieldName, $strNewValue, $strQueryString = null)
{
	return $_SERVER['PHP_SELF'] . '?' . replaceParameterQuery($strFieldName, $strNewValue, $strQueryString);
}

function replaceParameterQuery($strFieldName, $strNewValue, $strQueryString = null)
{
	if(!$strQueryString)
		$strQueryString = $_SERVER['QUERY_STRING'];

	$strQueryString = preg_replace("/$strFieldName=.*?&/", "&", $strQueryString);
	$strQueryString = preg_replace("/$strFieldName=.*?&/", "&", $strQueryString);
	$strQueryString = preg_replace("/$strFieldName=.*?$/", "&", $strQueryString);
	$strQueryString = preg_replace("/[&]+/",               "&", $strQueryString);

	return "$strQueryString&$strFieldName=$strNewValue";
}

function array_merge_keep_keys()
{
	$args = func_get_args();
	$result = array();
	foreach($args as &$array)
	{
		if(!is_array($array))
			throw new Exception('exception_internalerror');

		foreach($array as $key=>&$value)
		{
			$result[$key] = $value;
		}
	}
	return $result;
}

/** Cuts text off a certain point, attempting to put the cut after any html tags. */
function cut_text($strText, $intLength, $strMore = null)
{
	if(strlen($strText) <= $intLength)
		return $strText;

	$blnInHTML = false;
	$strRet = '';
	$i = 0;
	do
	{
		$c = substr($strText, $i, 1);
		$strRet = $strRet . $c;
		$i++;

		if($c == '<')
			$blnInHTML = true;
		else if($c == '>')
			$blnInHTML = false;
	}
	while($i < $intLength || $c != ' ' || $blnInHTML);

	return trim($strRet) . '...' . ($strMore ? $strMore : '');
}

/** Slightly modified from php.net. */
function time_to_text($timestamp,$detailed=false, $max_detail_levels=8, $precision_level='second')
{
	if($timestamp == 0)
		return "never";

	$now = time();

	#If the difference is positive "ago" - negative "away"
	($timestamp > $now) ? $action = 'away' : $action = 'ago';
  
	# Set the periods of time
	$periods = array("second", "minute", "hour", "day", "week", "month", "year",   "decade");
	$lengths = array(1,        60,       3600,   86400, 604800, 2630880, 31570560, 315705600);

	$diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);

	if($diff > ($lengths[5] * 6)) /* Longer than 6 months ago. */
		return "on " . date('Y-m-d', $timestamp);
  
	$prec_key = array_search($precision_level,$periods);
  
	# round diff to the precision_level
	$diff = round(($diff/$lengths[$prec_key]))*$lengths[$prec_key];
  
	# if the diff is very small, display for ex "just seconds ago"
	if ($diff <= 10) 
	{
		$periodago = max(0,$prec_key-1);
		$agotxt = $periods[$periodago].'s';
		return "just $agotxt $action";
	}
  
	# Go from decades backwards to seconds
	$time = "";
	for ($i = (sizeof($lengths) - 1); $i>0; $i--) 
	{
		if($diff > $lengths[$i-1] && ($max_detail_levels > 0)) 		# if the difference is greater than the length we are checking... continue
		{
			$val = floor($diff / $lengths[$i-1]);	# 65 / 60 = 1.  That means one minute.  130 / 60 = 2. Two minutes.. etc
			$time .= $val ." ". $periods[$i-1].($val > 1 ? 's ' : ' ');  # The value, then the name associated, then add 's' if plural
			$diff -= ($val * $lengths[$i-1]);	# subtract the values we just used from the overall diff so we can find the rest of the information
			if(!$detailed) { $i = 0; }	# if detailed is turn off (default) only show the first set found, else show all information
				$max_detail_levels--;
		}
	}
 
	# Basic error checking.
	if($time == "") {
		return "Error-- Unable to calculate time.";
	} else {
		return $time.$action;
	}
}

function imgToString($img, $mime_type)
{
	ob_start(); /* Start output buffering (so we can capture the file). */

	switch($mime_type)
	{
		case 'image/jpeg':
			if(!@imagejpeg($img))
				return "tn_nosave";
			break;

		case 'image/png':
			if(!@imagepng($img))
				return "tn_nosave";
			break;

		case 'image/gif':
			if(!@imagegif($img))
				return "tn_nosave";
			break;

		default:
			return 'tn_filetype';
	}

	return ob_get_clean();
}

function imagepalettetotruecolor(&$img)
{
	if (!imageistruecolor($img))
	{
		$w = imagesx($img);
		$h = imagesy($img);
		$img1 = imagecreatetruecolor($w,$h);
		imagecopy($img1,$img,0,0,0,0,$w,$h);
		imagedestroy($img);
		$img = $img1;
	}

	return $img;
}

// based on http://www.phpit.net/article/create-bbcode-php/  
// modified by www.vision.to  
// please keep credits, thank you :-)  
// document your changes.  
  
function bbcode_format ($str) {  
  
    $simple_search = array(  
                //added line break  
                '/\[br\]/is',  
                '/\[b\](.*?)\[\/b\]/is',  
                '/\[i\](.*?)\[\/i\]/is',  
                '/\[u\](.*?)\[\/u\]/is',  
                '/\[url\=(http:\/\/.*?)\](.*?)\[\/url\]/is',  
                '/\[url\](http:\/\/.*?)\[\/url\]/is',  
                '/\[align\=(left|center|right)\](.*?)\[\/align\]/is',  
                '/\[img\](http:\/\/.*?)\[\/img\]/is',  
                '/\[mail\=(.*?)\](.*?)\[\/mail\]/is',  
                '/\[mail\](.*?)\[\/mail\]/is',  
                '/\[font\=(.*?)\](.*?)\[\/font\]/is',  
                '/\[size\=(.*?)\](.*?)\[\/size\]/is',  
                '/\[color\=(.*?)\](.*?)\[\/color\]/is',  
                  //added textarea for code presentation  
               '/\[codearea\](.*?)\[\/codearea\]/is',  
                 //added pre class for code presentation  
              '/\[code\](.*?)\[\/code\]/is',  
                //added paragraph  
              '/\[p\](.*?)\[\/p\]/is',  
                );  
  
    $simple_replace = array(  
				//added line break  
               '<br />',  
                '<strong>$1</strong>',  
                '<em>$1</em>',  
                '<u>$1</u>',  
				// added nofollow to prevent spam  
                '<a href="$1" rel="nofollow" title="$2 - $1">$2</a>',  
                '<a href="$1" rel="nofollow" title="$1">$1</a>',  
                '<div style="text-align: $1;">$2</div>',  
				//added alt attribute for validation  
                '<img src="$1" alt="" />',  
                '<a href="mailto:$1">$2</a>',  
                '<a href="mailto:$1">$1</a>',  
                '<span style="font-family: $1;">$2</span>',  
                '<span style="font-size: $1;">$2</span>',  
                '<span style="color: $1;">$2</span>',  
				//added textarea for code presentation  
				'<textarea class="code_container" rows="30" cols="70">$1</textarea>',  
				//added pre class for code presentation  
				'<pre class="code">$1</pre>',  
				//added paragraph  
				'<p>$1</p>',  
                );  
 
	// Convert newlines to breaks. 
	$str = nl2br($str);
 
    // Do simple BBCode's  
    $str = preg_replace ($simple_search, $simple_replace, $str);  
  
    // Do <blockquote> BBCode  
    $str = bbcode_quote ($str);  
  
    return $str;  
}  

  
function bbcode_quote ($str) {  
    //added div and class for quotes  
    $open = '<blockquote><div class="quote">';  
    $close = '</div></blockquote>';  
  
    // How often is the open tag?  
    preg_match_all ('/\[quote\]/i', $str, $matches);  
    $opentags = count($matches['0']);  
  
    // How often is the close tag?  
    preg_match_all ('/\[\/quote\]/i', $str, $matches);  
    $closetags = count($matches['0']);  
  
    // Check how many tags have been unclosed  
    // And add the unclosing tag at the end of the message  
    $unclosed = $opentags - $closetags;  
    for ($i = 0; $i < $unclosed; $i++) {  
        $str .= '</div></blockquote>';  
    }  
  
    // Do replacement  
    $str = str_replace ('[' . 'quote]', $open, $str);  
    $str = str_replace ('[/' . 'quote]', $close, $str);  
  
    return $str;  
}  

# Taken from SMF source and modified to suit my needs
# Sends an email via the SMTP server
function smtp_send($mail_to_array, $from_name, $subject, $message)
{
	# If they didn't want smtp being used, just die
	if(SMTP_ALLOW != 1)
		return 'smtp_disabled';

	# Try connecting to the SMTP server
	if (!($socket = fsockopen(SMTP_SERVER, SMTP_PORT, $errno, $errstr, 5)))
	{
		# Unable to connect!
		return 'smtp_connect_failed';
	}

	// Construct the mail headers...
	$headers = 'From: "' . addcslashes($from_name, '<>[]()\'\\"') . '" <' . SMTP_ADMIN_EMAIL . ">\r\n";
	$headers .= 'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000' . "\r\n";

	// Wait for a response of 220, without "-" continuer.
	if (!server_parse(null, $socket, '220'))
		return 'smtp_invalid_response';

	if (SMTP_USERNAME != '' && SMTP_PASSWORD != '')
	{
		// EHLO could be understood to mean encrypted hello...
		if (!server_parse('EHLO ' . SMTP_SERVER, $socket, '250'))
			return 'smtp_invalid_response';
		if (!server_parse('AUTH LOGIN', $socket, '334'))
			return 'smtp_invalid_response';
		// Send the username ans password, encoded.
		if (!server_parse(base64_encode(SMTP_USERNAME), $socket, '334'))
			return 'smtp_invalid_response';
		if (!server_parse(base64_encode(SMTP_PASSWORD), $socket, '235'))
			return 'smtp_invalid_response';
	}
	else
	{
			// Just say "helo".
		if (!server_parse('HELO ' . SMTP_SERVER, $socket, '250'))
			return 'smtp_invalid_response';
	}

	foreach ($mail_to_array as $mail_to)
	{
		// From, to, and then start the data...
		if (!server_parse('MAIL FROM: <' . SMTP_ADMIN_EMAIL . '>', $socket, '250'))
			return 'smtp_error';
		if (!server_parse('RCPT TO: <' . $mail_to . '>', $socket, '250'))
			return 'smtp_error';
		if (!server_parse('DATA', $socket, '354'))
			return 'smtp_error';
		fputs($socket, 'Subject: ' . $subject . "\r\n");
		if (strlen($mail_to) > 0)
			fputs($socket, 'To: <' . $mail_to . ">\r\n");
		fputs($socket, $headers . "\r\n\r\n");
		fputs($socket, $message . "\r\n");

		// Send a ., or in other words "end of data".
		if (!server_parse('.', $socket, '250'))
			return 'smtp_error';
		// Reset the connection to send another email.
		if (!server_parse('RSET', $socket, '250'))
			return 'smtp_error';
	}
	fputs($socket, "QUIT\r\n");
	fclose($socket);

	return 'smtp_success';
}

# Parse a message to the SMTP server.
function server_parse($message, $socket, $response)
{
	global $txt;

	if ($message !== null)
		fputs($socket, $message . "\r\n");

	// No response yet.
	$server_response = '';

	while (substr($server_response, 3, 1) != ' ')
	{
		if (!($server_response = fgets($socket, 256)))
		{
			print("Error in SMTP server: invalid response<BR>");
			return false;
		}
	}

	if (substr($server_response, 0, 3) != $response)
	{
		print("SMTP error: unexpected response: $server_response<BR>");
		return false;
	}

	return true;
}


?>
