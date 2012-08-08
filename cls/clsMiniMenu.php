<?php

	/** This simple class is loaded at the start, and breadcrumbs can be added by any scripts. They will 
	 * be displayed nicely at the top. */

	class clsMiniMenu
	{
		private $arrMiniMenu = array();

		public function add($name, $url)
		{
			$this->arrMiniMenu[] = "<a href='$url'>$name</a>";
		}

		public function get()
		{
			$strRet = '';
			foreach($this->arrMiniMenu as $strItem)
				$strRet .= "<li>$strItem</li>";

			return "<ul>$strRet</ul>";
		}
	}
