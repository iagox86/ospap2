<?php

	/** This simple class is loaded at the start, and breadcrumbs can be added by any scripts. They will 
	 * be displayed nicely at the top. */

	class clsBreadcrumbs
	{
		private $arrBreadcrumbs = array();

		public function add($name, $url)
		{
			if($url)
				$this->arrBreadcrumbs[] = "<a href='$url'>$name</a>";
			else
				$this->arrBreadcrumbs[] = "$name";
				
		}

		public function get()
		{
			return implode(" &raquo; ", $this->arrBreadcrumbs);
		}
	}
