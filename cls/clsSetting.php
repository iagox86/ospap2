<?php

require_once('cls/clsDB.php');


class clsSetting extends clsDB
{
    public function __construct()
    {
        parent::__construct('setting');
    }

	public static function load_settings()
	{
		$arrSettings = clsDB::getListStatic('setting');

		foreach($arrSettings as $objSetting)
		{
			define($objSetting->get('name'), $objSetting->get('value'));
		}
	}

	public static function get_by_name($strName)
	{
		if(!preg_match("/^[a-zA-Z0-9]*$/", $strName))
			throw new Exception(ERRORMSG_INVALID);

		$arrResults = clsDB::selectQueryObjects('setting', "
								SELECT `<<setting><value>>` 
								FROM `<<tbl><setting>>` 
								WHERE `<<setting><name>>`='$strName'
									AND `<<isdel><setting>>`='0' ");

		if(sizeof($arrResults) == 0)
			return null;

		$objSetting = $arrResults[0];
		return $objSetting->get('value');
	}

	public static function set_by_name($strName, $strValue)
	{
		if(!preg_match("/^[a-zA-Z0-9_.]*$/", $strName))
			throw new Exception(ERRORMSG_INVALID);

		$arrResults = clsDB::selectQueryObjects('setting', "
								SELECT *
								FROM `<<tbl><setting>>` 
								WHERE `<<setting><name>>`='$strName'
									AND `<<isdel><setting>>`='0' ");

		if(sizeof($arrResults) == 0)
		{
			$objSetting = new clsDB('setting');
			$objSetting->set('name', $strName);
			$objSetting->set('valud', $strValue);
			$objSetting->save();
		}
		else
		{
			$objSetting = $arrResults[0];
			$objSetting->set('value', $strValue);
			$objSetting->save();
		}
	}
}
