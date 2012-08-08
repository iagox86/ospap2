<?php
	/** clsDB is the core class of my Database Framework.
	 * This class controls the interactions with the database, allowing programmers to load sub-classes, 
	 * fetch values, set values, save the class, etc.  
	 * 
	 * To use this, several requirements exist:
	 *  - Every table has a name, which is prepended to each column, so each column is [name]_column
	 *  - The actual name of the table is tbl_[name]
	 *  - Every table has an id field, which is the primary key.  It's named [name]_id, where [name] is
	 *    the table's name. 
	 *  - Every table must have an is_deleted field, named [name]_is_deleted
	 *  - Every foreign key must be in the form [name]_[othertablename]_id
	 * 
	 * So, for example, a database may have a stucture like this:
	 *  mysql> describe tbl_test;
	 *  +-------------------+-------------+------+-----+---------+----------------+
	 *  | Field             | Type        | Null | Key | Default | Extra          |
	 *  +-------------------+-------------+------+-----+---------+----------------+
	 *  | test_id           | int(11)     | NO   | PRI | NULL    | auto_increment | 
	 *  | test_othertest_id | int(11)     | NO   |     | 0       |                | 
	 *  | test_is_deleted   | tinyint(2)  | NO   |     | 0       |                | 
	 *  | test_test1        | int(11)     | NO   |     | 0       |                | 
	 *  | test_test2        | int(11)     | NO   |     | 0       |                | 
	 *  +-------------------+-------------+------+-----+---------+----------------+
	 *  
	 * In the above example, the name is 'test', so the name of the table is 'tbl_test' and the id 
	 * is 'test_id'.  
	 * 
	 * Whenever a column is referenced (for example, in the 'get()' function), the table name is not
	 * included.  So, to retrieve the column test_test1, you'd use get('test1').  
	 *
	 * An instance of this class can be created by passing an ID as the third parameter to the constructor.  
	 * If the 'id' parameter is non-zero, an attempt is made to load the object from the table,
	 * based on the ID.  Alternatively, the 'row' parameter can be given  (which overrides the 'id'
	 * parameter), which is a name=>value set of data fields (basically, a row fetched with 
	 * mysql_fetch_assoc()).  Using this, the programmer can load custom columns, like counts and such.  
	 * 
	 * This class will automate some level of input protection:
	 *  - Field names may only contain letters, numbers, and the underscore.  An exception will be
	 *    thrown otherwise.  
	 *  - htmlentities() will be called on all values being inserted/updated, removing <, >, ', and ".  
	 *    code that allows users to use those characters will need to use html_entity_decode() to undo
	 *    those changes. 
	 *  - Backslashes ('\') will be escaped when being saved into the database, not before.  
	 *  - getFromRequest() will encode html characters, whereas getFromRow() will not.
	 *
	 * This class has the ability to delete objects logically.  As stated above, every table must have 
	 * the [name]_is_deleted row.  If the value of that row is set to '1', it will no longer be returned
	 * by any queries made by this class.  The delete() function will set test_is_deleted to 1. 
	 *  
	 * This class also supports foreign keys.  All foreign keys must be in the form of,
	 * [name]_[othertablename]_id.  For example, a foreign key in test that points to othertest will 
	 * be test_othertest_id.  It can be access with the getForeignObject() function, which will load
	 * the foreign object and return a new database object, for the foreign class.  
	 * 
	 * Another interesting feature: if a user uses the saveWithDate() function to save, in addition to 
	 * saving the row, it also saves the current timestamp in the last_modified field. 
	 * 
	 * You will likely have to make your own queries for certain things.  Since the query() functions are
	 * all protected, you must make a subclass in order to use them.  Subclasses may make queries as usual, 
	 * but it is best practice not to use table or field names directly.  That way, queries are portable to
	 * different database styles without significant changes.  The following can be put in any database 
	 * query:
	 *  <<tbl><tablename>>     
	 *    Change 'tablename' to the name of the table you're referencing. 
	 * 
	 *  <<isdel><tablename>>   
	 *    Becomes the is_deleted column for the table. 
	 *
	 *  <<tablename><fieldname>>
	 *    Becomes a field within a table.  
	 * 
	 *  <<foreign><tablename><foreigntablename>>
	 *    Becomes a foreign key, pointing from tablename to foreigntablename. 
	 * 
	 * Here is an example of an elaborate query which uses these constructs:
	 *		$SQL = "SELECT *, COUNT(*) AS `<<campaign><count>>` 
	 *					FROM `<<tbl><campaignuser>>` JOIN `<<tbl><campaign>>` ON `<<foreign><campaignuser><campaign>>`=`<<campaign><id>>` 
	 *					WHERE `<<foreign><campaignuser><campaign>>` IN 
	 *					(
	 *						SELECT DISTINCT `<<foreign><campaignuser><campaign>>` 
	 *							FROM `<<tbl><campaignuser>>` 
	 *							WHERE `<<isdel><campaignuser>>`='0' 
	 *								AND `<<isdel><campaign>>`='0' 
	 *								AND `<<foreign><campaignuser><user>>`='$intUserID'
	 *					) 
	 *				GROUP BY `<<foreign><campaignuser><campaign>>`";
	 *
	 */

require_once('include/settings.php');
require_once('cls/clsParameters.php');

class clsDB
{
	/** The pattern that a valid field name will match */
	private static $strValidFieldPattern = "[a-zA-Z0-9_]*";

	/** Checks if the JavaScript (for the submit button) has been initialized */
	private static $jsInitialized = false;

	/** The connection to the database */
	private static $conDB = null;

	/** The queries that have been made, for reporting reasons */	
	private static $arrQueries = array();

	/** The name of the database.  See the comment at the top for more information */
	private $name    = null;

	/** The actual data, loaded from the database and saved to the database.  Data stored in this array
	 * is NOT safe to put into the database, but is safe to display to the user.  To store data, use 
	 * mysql_real_escape_string() on the values */
	private $arrData = null;

	/** When loading sub-objects, they are stored here.  */
	private $arrForeignObjects = array();

	/** This function will initialize the connection to the database, setting $conDB, if it hasn't already
	 * been done.   This should be called in the regular constructor, and in every static function that requires
	 * the use of the $conDB variable. */
	private static function initialize()
	{
		/* Only bother with this function if the database connection hasn't already been made.  */
		if(!clsDB::$conDB) 
		{
			clsDB::$conDB = mysql_pconnect(DBHOST, DBUSERNAME, DBPASSWORD);

			if(!clsDB::$conDB) 
				throw new Exception("Error connecting to database server: " . mysql_error());

			if(!mysql_select_db(DBNAME, clsDB::$conDB))
				throw new Exception("Error selecting database: " . mysql_error());
		}
	}

	public static function initializeJS($blnAddScripts = false)
	{
		$ret = '';
		if($blnAddScripts)
			$ret .= "<SCRIPT TYPE='text/javascript'>";

		$ret .= "\n	var once = 0; \n";

		if($blnAddScripts)
			$ret .= "</SCRIPT>";

		clsDB::$jsInitialized = true;

		return $ret;
	}

	/** The constructor for the class.  Initializes the variables and, if the 'id' or 'row' parameters are
	 * given, loads the data.  
	 * $name    The name of the table, which is prepended to all columns in the database in the form, "$name_[column]", 
	 *          and also appended to the name of the table, in the form "tbl_$name"
	 * $id      The id for the row to load.  If set to '0', no row is loaded
	 * $row     Data from a loaded row.  Overrides the $id parameter.  If not set, no data is loaded
	 */
	public function __construct($name, $id = 0, $row = null)
	{
		clsDB::initialize();

		if(!clsDB::isValidFieldName($name))
			throw new Exception(ERRORMSG_INVALID);

		$this->name    = $name;
		$this->arrData = array();

		if($row)
		{
			$this->getFromRow($row);
		}
		elseif($id)
		{
			$this->setInteger('id', $id);
			$this->load();
		}
	}

	/** Not sure what good this is.  But eh? */
	public function __destruct()
	{
	}

	/** This function is a little dangerous and may lead to unpredictable results. However, if you have multiple
	 * objects of the same type that need to be submitted through the same form, unique names are required. Just
	 * don't forget to fix them before they're saved to the DB. */
	public function setName($name)
	{
		if(!clsDB::isValidFieldName($name))
			throw new Exception(ERRORMSG_INVALID);

		$this->name = $name;
	}

	/** Loads a list of objects of the type, $name.  
	 * $name      The name of the database, same as it would be passed in the contructor.  The table will be called
	 *            tbl_$name.  
	 * $start     The number of the first object that will be loaded.  Will be sanitized, so user input is allowed. 
	 * $count     The total number of objects to load (the loaded objects will be from $start to $start+$count-1).   
	 *            Will be sanitized, so user input is allowed. 
	 * $orderby   The order to retrieve the objects in.  By default, the 'id' is used, which probably isn't the best
	 *            way of doing it.   Will be sanitized, so user input is allowed. 
	 * $order     In which direction the objects are loaded.  Must be either ASC or DESC.  
	 * $where     The where clause.  This will NOT be sanitized in any way, so do NOT allow user input in!  Note that 
	 *            the fields in the where clause have to have the table name appended to them, like 'test_test1 = 4', 
	 *            since I don't control the conditions. 
	 *
	 * Returns	  The array of objects. 
	 * 
	 */
	public static function getListStatic($name, $where = '', $orderby = 'id', $order = 'ASC', $limit = null, $start = null)
	{
		/* Sanitize the variables */
		if(!clsDB::isValidFieldName($name))
			throw new Exception(ERRORMSG_INVALID);

		if(!clsDB::isValidFieldName($orderby))
			throw new Exception(ERRORMSG_INVALID);

		if($order != 'ASC' && $order != 'DESC')
			throw new Exception(ERRORMSG_INVALID);

		if($limit !== null && !is_numeric($limit))
			throw new Exception(ERRORMSG_INVALID);

		if($start !== null && !is_numeric($start))
			throw new Exception(ERRORMSG_INVALID);

		/* If no where clause is given, create ours.  Otherwise, append ours to it. */
		if($where == '')
			$where = "`<<isdel><$name>>`='0'";
		else
			$where .= " AND `<<isdel><$name>>`='0'";

		/* Build the query */
		$SQL = "SELECT * FROM `<<tbl><$name>>` WHERE $where ORDER BY `<<$name><$orderby>>` $order";
		if($limit !== null)
		{
			$SQL .= " LIMIT $limit";
			if($start !== null)
				$SQL .= ", $start";
		}

		/* Perform the query */
		return clsDB::selectQueryObjects($name, $SQL);
	}

	/** Same as getListStatic, except the name parameter is taken straight from the table */
	public function getList($where = '', $orderby = 'id', $order = 'ASC', $limit = null, $start = null)
	{
		return clsDB::getListStatic($this->getName(), $where, $orderby, $order, $limit, $start);
	}

	/** Retrieves the number of rows from the database that a query would return.  It supports many different 
	 * operations such as joins, and can do DISTINCT-style results using the $groupby parameter.  To actually 
	 * make the GROUP BY work properly with a count, a sub-query is required, which is why the code for this 
	 * function is a little odd looking. */
	public static function getCountStatic($name, $where = '', $groupby = '')
	{
		if(!clsDB::isValidFieldName($name))
			throw new Exception(ERRORMSG_INVALID);

		/* If no where clause is given, create ours.  Otherwise, append ours to it. */
		if($where == '')
			$where = "`<<isdel><$name>>`='0'";
		else
			$where .= " AND `<<isdel><$name>>`='0'";

		if($groupby != '')
			$groupby = " GROUP BY `$groupby` ";

		$SQL = "SELECT COUNT(*) AS `count` FROM (SELECT 'a' FROM `<<tbl><$name>>` WHERE $where $groupby) AS a";
		$arrResults = clsDB::selectQuery($SQL);

		return $arrResults[0]['count'];
	}

	/** Same as getCountStatic, except the name is taken from the table */
	public function getCount($where = '', $groupby = '')
	{
		return clsDB::getCountStatic($this->getName(), $where, $groupby);
	}

	/** Check how many times this object is referenced (by a foreign key, of course) by another table. */
	public function getReferenceCount($strForeignTable)
	{
		if(!clsDB::isValidFieldName($strForeignTable))
			throw new Exception(ERRORMSG_INVALID);

		$strName = $this->getName();

		$objForeign = $this->getForeignObject($strForeignTable);
		$id = $this->get('id');

		return $objForeign->getCount("`<<foreign><$strForeignTable><$strName>>`='$id'");
	}

	/** Retrieve the database name.  See the comment at the top for more information about how names work. */
	protected function getName()
	{
		return $this->name;
	}

	/** Retrieve the database connection. */
	protected function getConDB()
	{
		return $this->conDB;
	}

	/** Checks if this database object is new, or whether it has already been inserted into the database.  The
	 * result of this function is based on whether or not the 'id' has been set.  It is possible that the 'id'
	 * can be set without the object existing, in which case the insertions will be broken.  It is not a good 
	 * idea to manually change the 'id' of the class. */
	public function isNew()
	{
		return $this->get('id') == 0;
	}

	/** Retrieves the name of the field for the current table.  The field name returned is the one that should
	 * be used in URLs or input fields. */
	public function getFieldName($strFieldName)
	{
		return clsDB::getFieldNameStatic($this->getName(), $strFieldName);
	}

	/** Retrieves the name of the field for the given table name.  The field name returned is the one that should
	 * be used in URLs or input fields. */
	public function getFieldNameStatic($name, $strField)
	{
		$strField = strtolower($strField);
		return "{$name}_$strField";
	}

	/** Retrieves the link that would be used in the URL, a [field name]=[value].  Mostly useful for ID. */
	public function getValuePair($strFieldName)
	{
		return $this->getFieldName($strFieldName) . "=" . urlencode($this->get($strFieldName));
	}

	/** Returns all the value pairs, in querystring-style. */
	public function getQueryString()
	{
		$arrNames = array_keys($this->arrData);
		$strRet = '';
		foreach($arrNames as $strName)
			$strRet = $strRet . $this->getValuePair($strName) . "&";

		return $strRet;
	}

	/** Convenience, same as getValuePair('id') */
	public function getIDPair()
	{
		return $this->getValuePair('id');
	}

	/** Attempts to convert a field name to the simple name.  For example, 'test_test1' will be converted to 
	 * 'test1'.  If the databases name isn't prepended to the name, then the original string is returned. */
	protected function toSimpleName($name)
	{
		if($this->isMyField($name))
			return substr($name, strlen($this->getName()) + 1);

		return $name;
	}

	/** Converts the name of a foreign object (for example, "othertest") into the foreign key version (for 
	 * example, "othertest_id". */
	protected static function toForeignName($name, $extrainfo = '')
	{
		$name = strtolower($name);
		$extrainfo = strtolower($extrainfo);

		if(strlen($extrainfo))
			$extrainfo .= '_';

		return "{$name}_{$extrainfo}id";
	}

	/** Checks whether or not the database's name is prepended to the field.  For example, if the database is 
	 * called 'test', then 'test_test1' will return true, and 'test2' will return false.  */
	protected function isMyField($fieldName)
	{
		$strName = $this->getName();

		return (substr($fieldName, 0, strlen($strName) + 1) == ($strName . '_'));
	}

	/** Checks if the given name is a valid for a field.  Fields may only contain letters, numbers, and the 
	 * underscore. */
	protected static function isValidFieldName($fieldName)
	{
		$strValidFieldPattern = clsDB::$strValidFieldPattern;

		return preg_match("/^$strValidFieldPattern+$/", $fieldName);
	}

	/** Escapes a value for html characters, but not for backslashes.  This should be called exactly once for
	 * every user-controlled variable (GET, POST, COOKIE).  In addition to escapting html characters, this is
	 * where the 'magic quotes' functionality is removed. */
	protected static function escapeValue($strValue, $removeHTML = true)
	{
		if(is_object($strValue))
			throw new Exception('exception_internalerror');

		if(ini_get('magic_quotes_gpc'))
			$strValue = stripslashes($strValue);

		if($removeHTML)
			return htmlentities($strValue, ENT_QUOTES);
		else
			return str_replace("/'/", "&apos;", $strValue);
	}

	/** Loads and returns an object that this object is linked to. 
	 * $name       The name of the foreign object in the database.  For example, if the entry for the foreign 
	 *             object was 'test_otherobject_id', the $name would be 'otherobject'. 
	 * $extrainfo  If this is set, the key used will be test_otherobject_extrainfo_id. This is used when multiple
	 *             references to a foreign table are required. */
	public function getForeignObject($name, $extrainfo = '')
	{
		if(!clsDB::isValidFieldName($name))
			throw new Exception(ERRORMSG_INVALID);

		if(!clsDB::isValidFieldName($extrainfo))
			throw new Exception(ERRORMSG_INVALID);

		$strForeignName = clsDB::toForeignName($name, $extrainfo);

		if(isset($this->arrForeignObjects[$strForeignName]))
		{
			return $this->arrForeignObjects[$strForeignName];
		}
		else
		{
			$foreignID = $this->get($strForeignName);
			$newDB = new clsDB($name, $foreignID);
			$this->arrForeignObjects[$strForeignName] = $newDB;
			return $newDB;
		}
	}

	/** Loads and returns objects that are linked to this by their foreign keys. 
	 * $strOtherTable   The name of the table that links back to this table. 
	 */
	public function getForeignObjects($strOtherTable)
	{
		if(!clsDB::isValidFieldName($strOtherTable))
			throw new Exception(ERRORMSG_INVALID);

		$strName = $this->getName();
		$intID = $this->get('id');

		$SQL = "SELECT * FROM `<<tbl><$strOtherTable>>` WHERE `<<foreign><$strOtherTable><$strName>>`='$intID' AND `<<isdel><$strOtherTable>>`='0' ";

		$arrResults = clsDB::selectQuery($SQL);
		$arrObjects = array();
		foreach($arrResults as $arrResult)
		{
			$objNew = new clsDB($strOtherTable);
			$objNew->getFromRow($arrResult);
			$arrObjects[] = $objNew;
		}

		return $arrObjects;
	}

	/** loads and returns objects that are linked through a proxy table.  Typically, these are 
	 * tables of the M-N type, each object is linked to multiple objects in the other table, and
	 * each object in the other table is linked to multiple objects in the first table.
	 * They are linked with a middle table that contains both IDs, 
	 * tbl_linker:
	 *  linker_id
	 *  linker_test1_id
	 *  linker_test2_id
	 *  ....
	 */
	public function getLinkedObjects($strMiddleTable, $strOtherTable)
	{
		if(!clsDB::isValidFieldName($strMiddleTable))
			throw new Exception(ERRORMSG_INVALID);
		if(!clsDB::isValidFieldName($strOtherTable))
			throw new Exception(ERRORMSG_INVALID);

		$strName = $this->getName();
		$intID = $this->get('id');

		$SQL = "SELECT `<<tbl><$strOtherTable>>`.* 
					FROM `<<tbl><$strMiddleTable>>` INNER JOIN `<<tbl><$strOtherTable>>` ON `<<foreign><$strMiddleTable><$strOtherTable>>`=`<<$strOtherTable><id>>`
					WHERE `<<tbl><$strMiddleTable>>`.`<<foreign><$strMiddleTable><$strName>>` = '$intID' 
						AND `<<isdel><$strMiddleTable>>`='0' 
						AND `<<isdel><$strOtherTable>>`='0' ";
		
		$arrResults = clsDB::selectQuery($SQL);
		$arrObjects = array();
		foreach($arrResults as $arrResult)
		{
			$objNew = new clsDB($strOtherTable);
			$objNew->getFromRow($arrResult);
			$arrObjects[] = $objNew;
		}

		return $arrObjects;
	}

	/** Retrieve an element from a foreign object, loading that object if necessary. 
	 * $strForeignName    The name of the foreign object to get the data from, see clsDB::getForeignObject()
	 *                    for more information. 
	 * $strField          The name of the field, as it would be used in the get() command. 
	 */
	public function getFrom($strForeignName, $strField)
	{
		if(!clsDB::isValidFieldName($strForeignName))
			throw new Exception(ERRORMSG_INVALID);

		if(!clsDB::isValidFieldName($strField))
			throw new Exception(ERRORMSG_INVALID);

		$objForeign = $this->getForeignObject($strForeignName);
		return $objForeign->get($strField);
	}

	/** Retrieve an element from a series of foreign objects.  Same as clsDB::getFrom(), except that an array
	 * of names is passed instead of a single name. */
	public function getFromRecursive($arrForeignNames, $strField)
	{
		if(!clsDB::isValidFieldName($strField))
			throw new Exception(ERRORMSG_INVALID);

		$objDB = $this;
		foreach($arrForeignNames as $strForeignName)
		{
			if(!clsDB::isValidFieldName($strForeignName))
				throw new Exception(ERRORMSG_INVALID);
			
			$objDB = $objDB->getForeignObject($strForeignName);
		}

		return $objDB->get($strField);
	}

	/** Performs a query that returns results.  The return value is an array of associative arrays, each representing
	 * a row that matched the query. */
	protected static function selectQuery($SQL)
	{
		clsDB::initialize();
		$RST = clsDB::query($SQL);
		$arrResult = array();
		while($row = mysql_fetch_assoc($RST))
			$arrResult[] = $row;
		mysql_free_result($RST);

		return $arrResult;
	}

	/** Performs a select query, but returns an array of objects rather than an array of arrays. */
	protected static function selectQueryObjects($strTable, $SQL)
	{
		if(!clsDB::isValidFieldName($strTable))
			throw new Exception(ERRORMSG_INVALID);

		return clsDB::arrayToObjects($strTable, clsDB::selectQuery($SQL));
	}

	/** Converts an array of arrays to an array of objects. */
	public static function arrayToObjects($strTable, $arrResults)
	{
		if(!clsDB::isValidFieldName($strTable))
			throw new Exception(ERRORMSG_INVALID);

		/* Load the results into an array */
		$arrReturn = array();
		foreach($arrResults as $result)
			$arrReturn[] = new clsDB($strTable, -1, $result);

		return $arrReturn;
	}

	/** Performs an insert query, and returns the new row id.  */
	protected static function insertQuery($SQL)
	{
		clsDB::initialize();
		clsDB::query($SQL);

		return mysql_insert_id(clsDB::$conDB);
	}

	/** Performs a query, and returns the result set.  The result set should be freed when finished. */
	protected static function query($SQL)
	{
		clsDB::initialize();

		$SQL = clsDB::doFullReplace($SQL);

		clsDB::$arrQueries[] = $SQL;

		$RST = mysql_query($SQL, clsDB::$conDB);
		if(!$RST)
			throw new Exception( "MySQL Error: " . mysql_error(clsDB::$conDB) . ": $SQL");

		return $RST;
	}

	/** Prints out the query, for the purpose of debugging. */
	protected static function printQuery($SQL)
	{
		echo "<pre>" . clsDB::doFullReplace($SQL) . "</pre>";
	}

	private static function doFullReplace($SQL)
	{
		$strValidFieldPattern = clsDB::$strValidFieldPattern;

		/* This regex converts <<tbl><tablename>> to tbl_tablename */
		$SQL = preg_replace("/<<tbl><($strValidFieldPattern)>>/", 'tbl_$1', $SQL);

		/* This regex converts <<isdel><tablename>> to tablename_is_deleted */
		$SQL = preg_replace("/<<isdel><($strValidFieldPattern)>>/", "$1_is_deleted", $SQL);

		/* This regex converts <<foreign><tablename><foreigntablename>> to tablename_foreigntablename_id */
		$SQL = preg_replace("/<<foreign><($strValidFieldPattern)><($strValidFieldPattern)>>/", "$1_$2_id", $SQL);

		/* This regex converts <<tablename><fieldname>> to tablename_fieldname */
		$SQL = preg_replace("/<<($strValidFieldPattern)><($strValidFieldPattern)>>/", "$1_$2", $SQL);

		return $SQL;
	}

	/** Save the current set of keys and values to the database.  If this object is new (has no 'id' set), 
	 * an 'insert' query is done, and the resulting 'id' is set in the object.  If the object is not new, then
	 * an 'update' query is done. */
	public function save()
	{
		/* We need to prepend the name of the database to all entries */
		$name = $this->getName();

		if($this->isNew())
		{
			/* Grab the keys and values from the array of data entries */
			$arrKeys = array_keys($this->arrData);
			$arrValues = array_values($this->arrData);

			/* Format the keys properly */
			foreach($arrKeys as &$key)
			{
				if(!clsDB::isValidFieldName($key))
					throw new Exception(ERRORMSG_INVALID);

				$key = strtolower($key);
				$key = "`{$name}_{$key}`";
			}

			/* Format the values properly */
			foreach($arrValues as &$value)
			{
				$value = mysql_real_escape_string($value);
				$value = "'$value'";
			}

			$strKeys = implode(',', $arrKeys);
			$strValues = implode(',', $arrValues);

			$SQL = "INSERT INTO `<<tbl><$name>>` ($strKeys) VALUES ($strValues);";
			$strID = clsDB::insertQuery($SQL);
			$this->setInteger('id', $strID);
		}
		else
		{
			$arrSets = array();
			foreach($this->arrData as $key=>$value)
			{
				$key = strtolower($key);
				$value = mysql_real_escape_string($value);
				$arrSets[] = "`<<$name><$key>>`='$value'";
			}
			$strSets = implode(',', $arrSets);

			$strID = $this->get('id');
			$SQL = "UPDATE `<<tbl><$name>>` SET $strSets WHERE `<<$name><id>>`='$strID';";
			clsDB::query($SQL);
		}
	}

	/** Save the entry, setting the last_modified field to the current time. */
	public function saveWithDate($strDateField = 'last_modified')
	{
		/* Set the last_modified field */
		$this->set($strDateField, time());

		$this->save();
	}

	/** Clears all data in the array, preserving only the id.  This is used when loading the object 
	 * from the database to ensure that old data isn't kept. */
	protected function clearData()
	{
		$strID = $this->get('id');
		$this->arrData = array();
		$this->set("id", $strID);
	}

	/** Loads the object from the database, based on the value set in 'id'.  All data in the array 
	 * is removed.  Optionally, an array of which fields to load can be passed. */
	public function load($arrFields = null)
	{
		$strTable      = $this->getName();
		$intID         = $this->get('id');

		if($arrFields == null || sizeof($arrFields) == 0)
		{
			$strFields = '*';
		}
		else
		{
			foreach($arrFields as &$strField)
			{
				if(!clsDB::isValidFieldName($strField))
					throw new Exception(ERRORMSG_INVALID);

				$strField = "`<<$strTable><$strField>>`";
			}
			$strFields = implode($arrFields, ',');
		}

		$SQL = "SELECT $strFields FROM `<<tbl><$strTable>>` WHERE `<<$strTable><id>>`='$intID' AND `<<isdel><$strTable>>`='0' LIMIT 0,1;";
		$arrResult = clsDB::selectQuery($SQL);

		$this->clearData();
		if(sizeof($arrResult) > 0)
			$this->getFromRow($arrResult[0]);
	}

	/** Marks the record as deleted.  Doesn't actually delete it until save() is called. */
	public function delete()
	{
		$this->set("is_deleted", 1);
	}

	/** Load the object based on data retrieved from the database.  HTML loaded in this way is
	 * not escaped and, thus, creates a danger of XSS.  Data already in the database should be 
	 * escaped already. */
	public function getFromRow($row)
	{
		$name = $this->name;

		$this->clearData();

		foreach($row as $key=>$value)
		{
			if(!clsDB::isValidFieldName($key))
				throw new Exception(ERRORMSG_INVALID);

			$key = $this->toSimpleName($key);
			$key = strtolower($key);
			$this->arrData[$key] = $value;
		}
	}

	/** Load THE object based on data from the request.  HTML loaded this way is escaped, and is 
	 * safe to display to the user. */
	public function getFromRequest($arrAllowed = null)
	{
		$strName = $this->getName();

		foreach($_REQUEST as $key=>$value)
		{
			if(clsDB::isValidFieldName($key))
			{
				/* Note: I used to just die if this failed, but it turns out that a lot of stuff (proxies, 
				 * software, etc) adds extra cookies to the request which would break this. */
				if($this->isMyField($key))
				{
					if(!clsDB::isValidFieldName($key))
						throw new Exception(ERRORMSG_INVALID);
	
					$key = $this->toSimpleName($key);
	
					if($arrAllowed && array_search($key, $arrAllowed) === false)
						throw new Exception(ERRORMSG_INVALID . (DEBUG ? (": " . $key) : ""));
	
					$this->set($key, $value);
				}
			}
		}
	}

	/** Retrieve data from the database, or return the default value if the field is not found.  Passing 
	 * user-controlled data for the defaultValue is safe. */
	public function get($strName, $defaultValue = '', $setDefault = false)
	{
		if(!clsDB::isValidFieldName($strName))
			throw new Exception(ERRORMSG_INVALID);

		$strName = strtolower($strName);

		$arrData = $this->arrData;
		$value = '';

		if(isset($arrData[$strName]))
		{
			$value = $arrData[$strName];
		}
		else
		{
			if($setDefault)
				$this->set($strName, $defaultValue);

			$value = htmlentities($defaultValue);
		}

		return $value;
	}

	/** Checks if the key exists in this database object. */
	public function exists($strName)
	{
		if(!clsDB::isValidFieldName($strName))
			throw new Exception(ERRORMSG_INVALID);

		$strName = strtolower($strName);
		$arrData = $this->arrData;

		return isset($arrData[$strName]);
	}

	/** Retrieve a value from the database, returning the default value if the field isn't found. */
	public function getInteger($strName, $defaultValue = null, $setDefault = 'false')
	{
		if(!clsDB::isValidFieldName($strName))
			throw new Exception(ERRORMSG_INVALID);

		return (integer) $this->get($strName, $defaultValue, $setDefault);
	}

	/** Set a specific field to a specific value.  User-controlled data is safe to be sent as the value.  If
	 * a user is allowed to control the 'name', he can cause an Exception, but no damage. 
	 * Unless the third parameter is set to false, all HTML tags and special characters are removed.  Don't 
	 * set removeHTML to false if the user controls the value being set, unless you're sure you know what 
	 * you're doing! */
	public function set($strName, $strValue, $removeHTML = true)
	{
		if(!clsDB::isValidFieldName($strName))
			throw new Exception(ERRORMSG_INVALID);

		$strName = strtolower($strName);
		$strValue = clsDB::escapeValue($strValue, $removeHTML);
		
		$this->arrData[$strName] = $strValue;
	}

	/** Remove the specified key. */
	public function remove($strName)
	{
		if(!clsDB::isValidFieldName($strName))
			throw new Exception(ERRORMSG_INVALID);

		$strName = strtolower($strName);

		unset($this->arrData[$strName]);
	}

	/** Set a specific field to an integer value. */
	public function setInteger($strName, $strValue)
	{
		if(!clsDB::isValidFieldName($strName))
			throw new Exception(ERRORMSG_INVALID);


		$this->set($strName, (integer) $strValue);
	}

	/** Takes an array in the form of name=>value pairs, and converts them to the form:
	 * <OPTION VALUE='[value]'>[name]</OPTION>
	 * value may contain anything, but apostrophes will be converted to &apos;.  
	 * name may contain anything, but html tags (> and <) will be converted to &gt; and &lt;. 
	 */
	protected function getOptionsString($arrOptions, $strSelected = '')
	{
		$arrCompleteOptions = array();
		foreach($arrOptions as $value=>$name)
		{
			$selected = ($value == $strSelected) ? "SELECTED" : "";

			$name = str_replace("<", "&lt;", $name);
			$name = str_replace(">", "&gt;", $name);
			$value = str_replace("'", "&apos;", $value);

			$arrCompleteOptions[] = "<OPTION VALUE='$value' $selected>$name</OPTION>";
		}

		return implode(' ', $arrCompleteOptions);
	}

	/** Takes an array in the form of name=>value pairs, and converts them to the form:
	 * <INPUT TYPE='radio' NAME='[fieldname]' VALUE='[value]'>[name]
	 * They are returned as an array, which allows them to be formatted the way the programmer likes. 
	 */
	protected function getRadioOptions($strFieldName, $arrOptions, $strChecked = '')
	{
		$arrCompleteOptions = array();
		foreach($arrOptions as $value=>$name)
		{
			$checked = ($value == $strChecked) ? "CHECKED" : "";

//			$name = str_replace("<", "&lt;", $name);
//			$name = str_replace(">", "&gt;", $name);
//			$value = str_replace("'", "&apos;", $value);

			$arrCompleteOptions[] = "<INPUT TYPE='radio' NAME='$strFieldName' VALUE='$value' $checked>$name";
		}

		return $arrCompleteOptions;
	}

	/** Returns a string of the radio options, with newlines beteen them.  */
	protected function getRadioOptionsString($strFieldName, $arrOptions, $strSelected = '')
	{
		return implode('<BR>', $this->getRadioOptions($strFieldName, $arrOptions, $strSelected));
	}

	/** Retrieve a text field that, when submitted, will allow getFromRequest() to fill in the data. */
	public function getTextField($name, $objParameters = array())
	{
		if($objParameters == null)
			$objParameters = new clsParameters();

		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);
		$strParams = $objParameters->get();

		return "<INPUT TYPE=\"text\" NAME=\"$strFieldName\" ID=\"$strFieldName\" VALUE=\"$value\" $strParams>";
	}

	/** Retrieve a password field that, when submitted, will allow getFromRequest() to fill in the data. */
	public function getPasswordField($name, $objParameters = array())
	{
		if($objParameters == null)
			$objParameters = new clsParameters();

		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);
		$strParams = $objParameters->get();

		return "<INPUT TYPE=\"password\" NAME=\"$strFieldName\"  ID=\"$strFieldName\" VALUE=\"$value\" $strParams>";
	}

	/** Create a text field that only allows number.  The javascript to accomplish this is a little long, 
	 * but it works. */
	public function getNumericField($name, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();

		/* Because of browser diffrences, this is ugly.  IE uses window.event and everything else uses 
		 * event.which.  This code will cancel a keypress unless it's a 0 (which is a special key), 
		 * backspace, or a digit.  (8 = backspace, 0x30 = 0, and 0x39 = 9) */
		$onKeyPress = "javascript:if(window.event && window.event != 0 && window.event != 8 && (window.event < 0x30 || window.event > 0x39)) { return false; } else if(event.which && event.which != 0 && event.which != 8 && (event.which < 0x30 || event.which > 0x39)) { return false; };";
		$objParameters->add('onKeyPress', $onKeyPress);

		/** To stop people from copying/pasting text into the field, replace non-digits with nothing. */
		$change = "javascript:this.value = this.value.replace(/[^\d]/g, '');";
		$objParameters->add('onChange', $change);

		return $this->getTextField($name, $objParameters);
	}

	/* Retrieves a text-area field with the specified number of rows and columns, plus any other parameters. */
	public function getTextArea($name, $rows = '3', $cols = '25', $objParameters = null)
	{
		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);

		if($objParameters == null)
			$objParameters = new clsParameters();

		$objParameters->add('ROWS', $rows);
		$objParameters->add('COLS', $cols);

		$strParams = $objParameters->get();

		return "<TEXTAREA NAME=\"$strFieldName\"  ID=\"$strFieldName\" $strParams>$value</TEXTAREA>";

	}

	/** Retrieve a hidden field that, when submitted, will allow getFromRequest() to fill in the data. */
	public function getHiddenField($name, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();

		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);
		$strParams = $objParameters->get();

		return "<INPUT TYPE='hidden' NAME='$strFieldName'  ID=\"$strFieldName\" VALUE='$value' $strParams>";
	}

	/** Retrieve a listbox.  This is actually the exact same as a combobox with the 'SIZE' parameter set, 
	 * so this function simply adds the SIZE parameter and calls the getCombo() function. */
	public function getListBox($name, $arrOptions, $size = 4, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();
		
		$objParameters->add("SIZE", $size);
		return $this->getCombo($name, $arrOptions, $objParameters);
	}

	/** Retrieve a combobox that, when submitted, will allow getFromRequest() to fill in the data. */
	public function getCombo($name, $arrOptions, $objParameters = null, $blnRawName = false)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();

		$strFieldName = $blnRawName ? $name : $this->getFieldName($name);
		$value = $this->get($name);
		$strParams = $objParameters->get();
		$strOptions = $this->getOptionsString($arrOptions, $this->get($name));

		return "<SELECT NAME='$strFieldName'  ID=\"$strFieldName\" $strParams>$strOptions</SELECT>";
	}

	public function getRadioString($name, $arrOptions, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();
		
		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);
		$strParams = $objParameters->get();

		return $this->getRadioOptionsString($strFieldName, $arrOptions, $value);
	}

	public function getRadioArray($name, $arrOptions, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();
		
		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);
		$strParams = $objParameters->get();

		return $this->getRadioOptions($strFieldName, $arrOptions, $value);
	}

	/** Because of how checks are sent by the browser, making them work is impossible without a little bit
	 * of JavaScript magic.  So the real field that is being sent is hidden, and the value is being set in
	 * the hidden when the user clicks the checkbox.  
	 * 
	 * The other option is to use a No/Yes combobox.. see clsDB::getCheckNoJavascript() for that method. */
	public function getCheckJavascript($name, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();
		
		$strFieldName = $this->getFieldName($name);
		$value = $this->get($name);

		$strHidden = $this->getHiddenField($name, new clsParameters('id', $strFieldName));

		$objParameters->add('id', "fake-$strFieldName");
		$objParameters->add('onChange', "document.getElementById('$strFieldName').value = (document.getElementById('fake-$strFieldName').checked) ? '1' : '0';");
		$strParams = $objParameters->get();

		$value = $value ? 'CHECKED' : '';
		$strCheck = "<INPUT TYPE='checkbox'  ID=\"$strFieldName\" $value $strParams>";

		return "$strHidden $strCheck";
	}

	/** See the getCheckJavascript(0 function for a little more explanation, but this has the same
	 * functionality as a checkbox, except that it uses a combo with the options No and Yes. */
	public function getCheckNoJavascript($name, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();

		$arrOptions = array(0=>'No', 1=>'Yes');

		return $this->getCombo($name, $arrOptions, $objParameters);
	}

	public function getCheck($name, $objParameters = null)
	{
		return $this->getCheckJavascript($name, $objParameters);
	}

	/** For getSubmit() to work properly, a JavaScript variable called "once" must be prepared in the <HEAD> of the 
	 * script.  This is done by calling clsDB::initializeJS(). 
	 * $value           The value that will be shown to the user on the button, and also the value that will be put
	 *                  in the $_REQUEST sent to the other side.  It is the value to check for.  It is probably a 
	 *                  good idea to use constants. 
	 * $blnSubmitOnce   When set to true, which is the default, the Submit button may only be clicked once, further
	 *                  clicks will not work.  In order to use SubmitOnce, clsDB::initializeJS() has to be called in 
	 *                  between <script> tags in the <head> of the document.  */
	public static function getSubmit($value, $objParameters = null, $blnSubmitOnce = true)
	{
		if($blnSubmitOnce && !clsDB::$jsInitialized)
			throw new Exception('clsDB::initializeJS() must be called within <script> tags, preferably in <head>, to use 
                                the blnSubmitOnce functionality in getSubmit() -- either set $blnSubmitOnce to false or
                                call clsDB::initializeJS().');

		if($objParameters == null)
			$objParameters = new clsParameters();

		if($blnSubmitOnce)
			$objParameters->add("onClick", "if(once) { return false; } else { once = 1; return true; }");

		$strParameters = $objParameters->get();
		
		return "<INPUT TYPE='submit' NAME='submit' VALUE='$value' $strParameters>";
	}

	public static function getButton($value, $objParameters = null)
	{
		if($objParameters == null)
			$objParameters = new clsParameters();
		$strParmeters = $objParameters->get();
		
		return "<INPUT TYPE='button' VALUE='$value' $strParameters>";
	}

	/** Builds an options array from an array of database objects.  The most common use for this is when 
	 * a table holds a list of options, such as a category table, a sports category, etc. 
	 * $arrDB            An array of database objects, each will be a row in the options array returned.
	 * $strValueElement  The name of the element to display as the choice.  With generally be a 'name'-style 
	 *                   element.  
	 * $strKeyElement    The name of the element to use for the key, will probably always be 'id'.
	 * $strZeroCaption   If a "Please make a selection" field is required, this would usually be it.  For no 
	 *                   zero-caption, set to null. 
	 */
	public static function getOptionsFromList($arrDB, $strValueElement = 'name', $strKeyElement = 'id', $strZeroCaption = 'Please make a selection')
	{
		if(!is_array($arrDB))
			throw new Exception("Invalid array passed to getOptionsFromList()");

		$arrOptions = array();
		if($strZeroCaption != null)
			$arrOptions[0] = $strZeroCaption;

		foreach($arrDB as $objDB)
		{
			if(!is_object($objDB))
				throw new Exception('Invalid element passed to getOptionsFromList()');
			$arrOptions[$objDB->get($strKeyElement)] = $objDB->get($strValueElement);
		}

		return $arrOptions;
	}

	/** This is a quick wrapper around getOptionsFromList that pulls an entire table into a list.  
	 * I found myself using, getOptionsFromList(getListStatic('table')) way too many times, so this
	 * will shorten that code.  */
	public static function getOptionsFromTable($strTableName, $strValueElement = 'name', $strKeyElement = 'id', $strZeroCaption = 'Please make a selection')
	{
		return clsDB::getOptionsFromList(clsDB::getListStatic($strTableName), $strValueElement, $strKeyElement, $strZeroCaption);
	}

	/** Same as getOptionsFromList, except it uses an array of arrays (such as is returned by selectQuery()).  */
	public static function getOptionsFromArray($strTableName, $arrDB, $strValueElement = 'name', $strKeyElement = 'id', $strZeroCaption = 'Please make a selection')
	{
		return clsDB::getOptionsFromList(clsDB::arrayToObjects($strTableName, $arrDB), $strValueElement, $strKeyElement, $strZeroCaption);
	}

	public function toString()
	{
		$str = 'Name: ' . $this->getName() . '<br>';
		foreach($this->arrData as $key=>$value)
			$str .= "$key = $value<br>";

		return $str;
	}
}

?>
