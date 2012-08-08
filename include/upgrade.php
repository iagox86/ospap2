<?php

require_once('cls/clsDB.php');
require_once('cls/clsSetting.php');
require_once('include/version.php');

$objChange = new clsChange();
$objChange->update(DB_BUILD, CODE_BUILD);

class clsChange extends clsDB
{
	private $arrChanges = array();

    public function __construct()
    {
		/* Build 1 to build 2. */
		$this->arrChanges[1] = array();
		$this->arrChanges[1][] = "ALTER TABLE `tbl_user` ADD `user_temp_password` VARCHAR(256) NOT NULL DEFAULT '' AFTER `user_password`";
		$this->arrChanges[1][] = "ALTER TABLE `tbl_user` ADD `user_temp_password_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `user_temp_password`";
		$this->arrChanges[1][] = "INSERT INTO `tbl_setting` (`setting_name`, `setting_value`, `setting_comment`) VALUES 
									('SMTP_ALLOW', '1', \"Set this to 0 to completely disable SMTP emails from going out.\"), 
									('SMTP_SERVER', 'localhost', \"The SMTP server.\"), 
									('SMTP_PORT', \"25\", \"The port to use for SMTP. This will almost certainly be 25.\"), 
									('SMTP_ADMIN_EMAIL', \"noreply@javaop.com\", \"The email address to use as the 'from' field.\"), 
									('SMTP_USERNAME', \"\", \"Set this if your SMTP server requires a username\"), 
									('SMTP_PASSWORD', \"\", \"Set this if your SMTP server requires a password.\")";

		/* Build 2 to build 3, no changes. */
    }

	public function update($intFrom, $intTo)
	{
		if($intFrom <= 0)
			die("Invalid version, couldn't upgrade.");

		if($intTo <= 0)
			die("Invalid version, couldn't upgrade.");

		if($intFrom > $intTo)
			die("Database version newer than code version. Upgrade the code.");

		for($i = $intFrom; $i < $intTo; $i++)
		{
			if(isset($this->arrChanges[$i]))
				foreach($this->arrChanges[$i] as $strQuery)
					clsDB::insertQuery($strQuery);

			clsSetting::set_by_name('DB_BUILD', $i + 1);
		}
	}
}

?>
