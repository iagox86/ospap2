<?php

require_once('cls/clsDB.php');

class clsUser extends clsDB
{
	public function __construct($id = 0, $row = null)
	{
		parent::__construct('user', $id, $row);
	}

	/** Checks if the data loaded in this object matches anything from 
	 *  the database. */
	public function verify()
	{
		$username = $this->get('username');
		$password = $this->get('password');

		$objUser = clsUser::getByName($username);
		if(!$objUser)
			return false; // Username wasn't found 

		$rightPassword = $objUser->get('password');

		if(md5($password . $username) == $rightPassword)
		{
			$this->set('id', $objUser->get('id'));
			$this->load();

			return true;
		}

		return false;
	}

	public function verifyTemp()
	{
		$username = $this->get('username');
		$password = $this->get('password');

		$objUser = clsUser::getByName($username);
		if(!$objUser)
			return false; // Username wasn't found 

		$tempPassword = $objUser->get('temp_password');
		$tempExpire = strtotime($objUser->get('temp_password_date')) + (60 * 60 * 12);

		if($tempExpire < time()) /* Temp password is expired. */
			return false;

		if(md5($password) == $tempPassword)
		{
			$this->set('id', $objUser->get('id'));
			$this->load();

			return true;
		}

		return false;
	}

	/** Returns the new user object on success, or an error message (string) otherwise.  To check the
	 * return value properly, use is_string or is_object.  */
	public function attemptCreate()
	{
		$username = $this->get('username');
		$password1 = $this->get('password1');
		$password2 = $this->get('password2');

		/* Prevent the password from being displayed back to the user if it fails. */
		$this->remove('password1');
		$this->remove('password2');

		if($password1 != $password2)
			return "register_dontmatch";

		if($password1 == '')
			return "register_blank";

		$arrDuplicates = $this->getList("`<<user><username>>`='$username'");
		if(sizeof($arrDuplicates) > 0)
			return "register_inuse";

		$this->remove('password1');
		$this->remove('password2');
		$this->set('password', md5($password1 . $username));
		$this->save();

		return $this;
	}

	public function changePassword()
	{
		$password1 = $this->get('password1');
		$password2 = $this->get('password2');

		$this->remove('password1');
		$this->remove('password2');

		if($password1 != $password2)
			return "register_dontmatch";

		$this->set('password', md5($password1 . $this->get('username')));
	}

	/* Has to be static to take into account guests. */
	public static function getAdvancedStyle($objUser)
	{
		if(!$objUser || !$objUser->get('is_advanced'))
		{
			return <<<EOT
				<style>
					.advanced
					{
						display: none;
					}
				</style>
EOT;
		}
		else
		{
			return <<<EOT
				<style>
					.simple
					{
						display: none;
					}
				</style>
EOT;
		}
	}

	public static function getUserList()
	{
		$arrUsers = clsDB::selectQueryObjects('user', 
							"SELECT `<<user><id>>` 
								FROM `<<tbl><user>>` 
								WHERE `<<isdel><user>>`='0' 
								ORDER BY `<<user><username>>`");
		$arrRet = array();
		foreach($arrUsers as $objUser)
			$arrRet[] = new clsUser($objUser->get('id'));

		return $arrRet;
	}

	public static function canEdit($objMember, $objUser)
	{
		if($objUser && $objUser->get('is_admin'))
			return true;

		if($objUser && ($objUser->get('id') == $objMember->get('id')))
			return true;

		if($objMember->isNew())
			return true;

		return false;
	}

	public function setCookie()
	{
		$intExpire = time() + (60*60*24*30);

		setcookie("ospap2_id", $this->get('id'), $intExpire);
		setcookie("ospap2_passhash", sha1($this->get('password')), $intExpire);
	}

	public static function getCookie()
	{
		if(!isset($_COOKIE['ospap2_id']))
			return null;

		if(!isset($_COOKIE['ospap2_passhash']))
			return null;

		$objUser = new clsUser($_COOKIE['ospap2_id']);
		if($objUser->isNew())
			return null;

		if(sha1($objUser->get('password')) == $_COOKIE['ospap2_passhash'])
		{
			/* Rejuvinate the cookie. */
			$objUser->setCookie();
			return $objUser;
		}

		return null;
	}

	public static function clearCookie()
	{
		setcookie('ospap2_id', null);
		setcookie('ospap2_passhash', null);
	}

	public static function getByName($strName)
	{
		/* By putting the username into an object, it is sanitized. */
		$objUser = new clsUser();
		$objUser->set('username', $strName);

		$arrResults = clsDB::getListStatic('user', "`<<user><username>>`='" . $objUser->get('username') . "'");
		if(sizeof($arrResults) == 0)
			return null; // Username wasn't found 

		if(sizeof($arrResults) > 1)
			throw new Exception("exception_multiplenames"); /* should never happen, but who knows? */

		return new clsUser($arrResults[0]->get('id'));
	}

	/* Note: the user object passed only has the name set, not the ID */
	public static function attemptRecover($strName)
	{
		$objUser = clsUser::getByName($strName);

		if($objUser == null)
			return 'forgot_unknown';

		if($objUser->get('email') == '')
			return 'forgot_noemail';

		$strNewPassword = '';
		for ($i = 0; $i < 20; $i++)
			$strNewPassword .= substr('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', rand(0,61), 1);

		$objUser->set('temp_password', md5($strNewPassword));
		$objUser->set('temp_password_date', date('Y-m-d H:i:s', time()));
		$objUser->save();

		$strMessage = "Your password for " . SITE_NAME . " has been reset to:\r\n\r\n";
		$strMessage .= $strNewPassword . "\r\n\r\n";
		$strMessage .= "This password will expire soon, so be sure to change it.\r\n";

		return smtp_send(array($objUser->get('email')), SITE_NAME, SITE_NAME . ": Forgot Password", $strMessage);
	}
}



?>
