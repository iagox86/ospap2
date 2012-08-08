<?php

/** I keep finding myself in need of storing a list of parameters in name=value pairs and displaying
 * them later in an html tag, with the ability to merge them and such.  So here's a class that'll look
 * after any dirty work. */
class clsParameters
{
	private $arrData = array();
	private $arrOptions = array();

	/** The $name and $value parameters are optional.  If filled in, they add a first parameter set.  
	 * that's simply for convenience, if a single parameter pair is needed. */
	public function __construct($name = null, $value = null)
	{
		if($name)
			$this->add($name, $value);
	}

	/** Add a name='value' pair to this set of parameters.  If a parameter with the given $name already
	 * exists, they're joined with a "; ", so JavaScript commands can easily be built. */
	public function add($name, $value)
	{
		if(isset($this->arrData[$name]))
		{
			$this->arrData[$name] .= "; " . $value;
		}
		else
		{
			$this->arrData[$name] = $value;
		}
	}

	/** Retrieve the list of parameters.  
	 * 
	 * The second parameter no longer does anything.  It worked great in FireFox, but screwed up in Internet Explorer, 
	 * so I decided to trash it completely. 
	 * 
	 * $quote            The type of quote to put around the values, name=[quote]value[quote]
	 * $bnlReplaceHTML   When set, replaces HTML tags and ' and " with the equivalent (&lt;, &gt;, etc.)
	 */
	public function get($quote = '"', $blnReplaceHTML = true)
	{
		$strReturn = '';

		foreach($this->arrData as $key=>$value)
		{
			if($blnReplaceHTML)
			{
			}

			$strReturn .= "$key=$quote$value$quote ";
		}


		return $strReturn;
	}

	/** Sometimes, I need to pass options to an output function.  Piggy-backing them on here saves me from
	 * having to create and store another hashtable (err wait, this is PHP -- 'associative array'), and
	 * instead store them here.  Unlike the normal clsParameters::add() function, if two options with the
	 * same name are added, the second one over-writes the first.  */
	public function addOption($name, $value = true)
	{
		$this->arrOptions[$name] = $value;
	}

	/** Searches the parameters for a specific character, returning "true" if it is found.  The reason for
	 * this function is that certain characters are illegal to use in certain places, so it is necessary to
	 * fail if they are detected. */
	public function containsCharacter($chr)
	{
		foreach($this->arrData as $key=>$value)
		{
			if(strpos($value, $chr) !== false)
				return true;
		}
		return false;
	}

	/** Returns true iff the option has been added. */
	public function containsOption($strName)
	{
		return isset($this->arrOptions[$strName]);
	}

	/** Returns the value of the option. */
	public function getOption($strName)
	{
		return $this->arrOptions[$strName];
	}
}

?>
