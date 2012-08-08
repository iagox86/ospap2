<?php

class clsTemplate
{
	private $strBaseTemplateFile;

	private $arrTextReplacements = array();

	private $strTemplateFolder;
	private $strTemplateSuffix = '.html';
//	private $arrTemplateReplacements = array();

	private $strScriptFolder;
	private $strScriptSuffix = '.php';
	private $arrScriptReplacements = array();

	public function __construct($strBaseTemplateName, $strTemplateFolder = 'templates/', $strScriptFolder = 'pages/')
	{
		if(!preg_match("/^[a-zA-Z0-9.]+$/", $strBaseTemplateName))
			throw new Exception("Invalid template name: '$strTemplateName'");

		$strBaseTemplateFile = $strTemplateFolder . str_replace('.', '/', $strBaseTemplateName) . $this->strTemplateSuffix;

		if(!file_exists($strBaseTemplateFile))
			throw new Exception("No such template: $strBaseTemplateFile");

		$this->strBaseTemplateFile = $strBaseTemplateFile;
//		$this->strTemplateFolder = $strTemplateFolder;
		$this->strScriptFolder = $strScriptFolder;
	}

	public function setText($strName, $strData)
	{
		if(isset($this->arrTextReplacements[$strName]))
			$this->arrTextReplacements[$strName] .= "\n$strData";
		else
			$this->arrTextReplacements[$strName] = $strData;
	}

	public function setScript($strName, $strFilename)
	{
		if(isset($this->arrScriptReplacements[$strName]))
			$this->arrScriptReplacements[$strName] .= "\n$strFilename";
		else
			$this->arrScriptReplacements[$strName] = $strFilename;
	}

	public function get()
	{
		global $arrMessages; /* So these can be used by scripts. */
		global $objBreadcrumbs;
		global $objMiniMenu;
		global $objUser;
		global $objTemplate;
		global $strAction;
		global $strSubAction;

		$strFile = file_get_contents($this->strBaseTemplateFile);

		if(!$strFile)
			throw new Exception("Unable to open template file: " . $this->strFileName);

		/* Since scripts are allowed to modify the template, this should be before the "text replacements" loop. */
		foreach($this->arrScriptReplacements as $key=>$value)
		{
			$strToReplace = '%%%' . $key . '%%%';

			$value = $this->strScriptFolder . $value . $this->strScriptSuffix;

			ob_start();
			require($value);
			$strFile = str_replace($strToReplace, ob_get_clean(), $strFile);
		}

		foreach($this->arrTextReplacements as $key=>$value)
		{
			$strToReplace = '%%%' . $key . '%%%';
			$strFile = str_replace($strToReplace, $value, $strFile);
		}

		/* Replace any left-over tags that they didn't fill out */
		$strFile = preg_replace('/%%%[a-zA-Z0-9]*?%%%/', '', $strFile);

		return $strFile;
	}
}

?>
