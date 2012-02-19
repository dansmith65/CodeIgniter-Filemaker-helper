<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Filemaker_helper
 * 
 * @author    Daniel Smith dansmith65@gmail.com
 * @link      https://github.com/dansmith65/CodeIgniter-Filemaker-helper
 * 
 * Create a FileMaker Database object and 
 * provide auxillary functions to work with the object.
*/

class Filemaker_helper {
	/**
	 * FileMaker PHP API Object
	 */
	public $db;
	
	/**
	 * Reference to CodeIgniter
	 */
	private $CI;
	
	/**
	 * Config filename that holds validation rules
	 *
	 * will be used to auto-load the config values only when they are needed
	 */
	private $validation_config_file;
	
	/**
	 * Validation rules
	 *
	 * will be auto-load from config file only when needed
	 * 
	 * NULL = have not been loaded
	 * FALSE = failed to load
	 * is_array() = loaded
	 */
	private $validation_rules = NULL;
	
	/*
	 * map Table Occurrences in FileMaker to Table Key's
	 * 
	 * $tables format:
	 *	array(
	 *		'tableKey1' => array('TableOccurence1', etc...),
	 *		'tableKey2' => array('TableOccurence2', 'TableOccurence3', etc...),
	 *		etc...
	 *	)
	 */
	private $tables = array();
	
	/**
	 * map Fields in FileMaker to Table Key's, and Field Key's
	 * 
	 * $tables format:
	 *	array(
	 *		'tableKey1' => array(
	 *			'fieldKey1' => array(
	 *				'name' => 'fieldName1',
	 *				'label' => 'Field Label 1',
	 *			),
	 *			all fields...
	 *		),
	 *		all table keys...
	 *	)
	 */
	private $fields = array();
	
	/*********************************************************************/
	
	/**
	 * @param	string	name of config file to retrieve connection settings from
	 */
    function __construct($dbconfig='fmdb')
	{
		// provide access to CodeIgniter Resources
		$this->CI =& get_instance();
		
		// create FM Database object
		require_once 'FileMaker.php';
		$this->CI->config->load($dbconfig, TRUE);
		$this->db = new FileMaker(
			$this->CI->config->item('database', $dbconfig),
			$this->CI->config->item('hostname', $dbconfig),
			$this->CI->config->item('username', $dbconfig),
			$this->CI->config->item('password', $dbconfig)
		);
		$this->validation_config_file = $this->CI->config->item('validation_config_file', $dbconfig);
		$this->tables = $this->CI->config->item('tables', $dbconfig);
		$this->fields = $this->CI->config->item('fields', $dbconfig);
    }
	
	/*********************************************************************/
	
	/**
	 * Script errors that should not be shown to user.
	 * Log, then display a default "something went wrong" to user
	 * 
	 * Increases portability of this class by limiting it's references
	 * to external objects.
	 *
	 * @param	string	error message
	 * @return	void
	 */
	private function error($message)
	{
		@$temp = debug_backtrace();
		@$message =
			$temp[1]['class']
			.'::'.$temp[1]['function']
			.' line:'.$temp[0]['line']
			.' --> '.$message
			;
		$this->log('error', $message);
		show_error('Sorry, something went wrong, this error was logged.');
	}
	
	/*********************************************************************/
	
	/**
	 * Show this error to the user
	 * 
	 * Increases portability of this class by limiting it's references
	 * to external objects.
	 * 
	 * @param	string	message to log and show to user
	 * @param	string	additional info to log (but NOT show to user)
	 */
	private function show_error($message, $title=NULL, $log_info=NULL)
	{
		@$temp = debug_backtrace();
		@$log_info =
			$temp[1]['class']
			.'::'.$temp[1]['function']
			.' line:'.$temp[0]['line']
			.($log_info!=NULL ? ' --> '.$log_info : '')
			;
		
		$this->log('error', $log_info);
		show_error($message);
	}
	
	/*********************************************************************/
	
	/**
	 * Log message
	 * 
	 * Increases portability of this class by limiting it's references
	 * to external objects.
	 */
	private function log($level, $message)
	{
		log_message($level, $message);
	}
	
	/*********************************************************************/
	
	/**
	 * get corresponding field key for a table occurence and field name
	 * mostly used by retrieve_data() method to get field keys for all fields processed
	 *
	 * @param	array	indexed array of string values (output of $recordObject->getFields())
	 * 					array values should be formated as either 'table::field' or 'field'
	 *					if any field does NOT have a table name before it, 2nd parameter
							MUST be provided
						value can contain an array of values (a portal)
	 * @param	string	default tableKey (for fields on current layout)
	 * @return	array	indexed array of associative array values
	 *						return[0] = array('table1Key'=>'field1Key')
	 *						return[1] = array('table1Key'=>'field2Key')
	 *						return[2] = array(
	 *							array('portalTable1Key'=>'field3Key'),
	 *							array('portalTable1Key'=>'field4Key'),
	 *						)
	 */
	public function get_field_keys($fields, $defTableKey=NULL)
	{
		$this->log('debug', __Method__.' called');
		$result = array_walk_recursive($fields, array($this, 'get_field_keys_walk'), $defTableKey) ;
		if( ! $result ) $this->error('array_walk_recursive failed');
		return $fields;
	}
	
	/*********************************************************************/
	
	/**
	 * helper method for get_field_keys
	 * modify fields array with result of get_field_key method
	 * to be called by array_walk_recursive
	 * 
	 * @param	array	to be modified
	 * @param	string	not used (only use is to allow the 3rd parameter)
	 * @param	string	value to be passed to get_field_key method
	 * @return	void
	 */
	private function get_field_keys_walk(&$value, $key, $defTableKey)
	{
		$value = $this->get_field_key($value, $defTableKey);
	}
	
	/*********************************************************************/
	
	/**
	 * helper method for get_field_keys
	 * 
	 * @param	string	field name with or without tablename in it
	 * @param	string	default tableKey (for fields on current layout)
	 * @return	array	associative array of tableKey=>fieldKey
	 */
	private function get_field_key($field, $defTableKey=NULL)
	{
		$split = $this->split_field($field);
		// set tableKey
		if( $split[0]==NULL ) {
			if( $defTableKey==NULL ) $this->error('defTableKey parameter was not provided, but it was needed for field: '.$field);
			$tableKey = $defTableKey;
		} else {
			$tableKey = $this->get_table_key($split[0]);
		}
		if( ! isset($this->fields[$tableKey] ) ) $this->error('table key does not exist in fields array: '.$tableKey);
		// set fieldKey
		$fieldKey = NULL;
		foreach( $this->fields[$tableKey] as $k => $v ) {
			if( $v['name']==$split[1] ) {
				$fieldKey = $k;
				break;
			}
		}
		if( $fieldKey==NULL ) $this->error('field name: '.$split[1].' does not exist in fields array for tableKey: '.$tableKey);
		return array($tableKey=>$fieldKey);
	}
	
	/*********************************************************************/
	
	/**
	 * separate relationship and field from a fully qualified field name
	 * if no relationship present, value is null.
	 * return value as array: index 0 is relationship, index 1 is field
	 */
	private function split_field($field)
	{
		$e = explode('::', $field);
		if( count($e)==1 ) {
			$r = NULL;
			$f = $e[0];
		} elseif( count($e)==2 ) {
			$r = $e[0];
			$f = $e[1];
		} else {
			$this->error('parameter was not a valid value: ' . $field);
		}
		return array($r,$f);
	}
	
	/*********************************************************************/
	
	/**
	 * get field labels in fields array (using fieldKeys)
	 * 
	 * @param	array	output of get_field_keys method
	 * @return	array	table key
	 */
	public function get_field_labels($fieldKeys)
	{
		$this->log('debug', __Method__.' called');
		$result = array_walk($fieldKeys, array($this, 'get_field_labels_walk')) ;
		if( ! $result ) $this->error('array_walk_recursive failed');
		return $fieldKeys;
	}
	
	/*********************************************************************/
	
	/**
	 * helper method for get_field_labels
	 * modify fields array, replacing fieldKey with the label for the field
	 * to be called by array_walk
	 * 
	 * @param	array	to be modified
	 * @return	void
	 */
	private function get_field_labels_walk(&$value, $key)
	{
		if( $key==='portals' ) {
			// contains an array of portals, each containing an array of field keys
			foreach( $value as &$portal ) {
				$result = array_walk($portal, array($this, __FUNCTION__));
				if( ! $result ) $this->error('array_walk failed');
			}
		} else {
			$table = key($value);
			$field = current($value);
			if( ! isset($this->fields[$table][$field]['label']) ) {
				$this->error('label not found for field key: ' . $table . '=>' . $field);
			} else {
				$value = $this->fields[$table][$field]['label'];
			}
		}
	}
	
	/*********************************************************************/
	
	/**
	 * get corresponding table key for a table occurence name
	 *
	 * @param	string	table occurrence name from FileMaker
	 * @return	string	table key
	 */
	private function get_table_key($table)
	{
		// local cache of table key's
		// (speeds up processing of multiple related fields from the same table/portal)
		// table names will be etered into this array when they are looked up, so future calls to
		//   the same table will not have to loop through the tables array
		static $cache;
		
		if( isset( $cache[$table] ) ) {
			return $cache[$table];
		} else {
			foreach( $this->tables as $k => $v ) {
				if( in_array($table, $v) ){
					$cache[$table] = $k;
					return $k;
					break;
				}
			}
		}
		$this->error( 'Table Key not found for: '.$table );
	}
	
	/*********************************************************************/
	
	/**
	 * Get field name for provided table/field key
	 *
	 * @param	string	
	 * @param	string	
	 * @return	string	field name
	 */
	public function get_field_name($tableKey, $fieldKey)
	{
		if( ! isset($this->fields[$tableKey]) )
			$this->error('tableKey: '.$tableKey.' not found in fields array');
		if( ! isset($this->fields[$tableKey][$fieldKey]) )
			$this->error('fieldKey: '.$tableKey.'=>'.$fieldKey.' not found in fields array');
		if( ! isset($this->fields[$tableKey][$fieldKey]['name']) )
			$this->error('name not found in fields array for: '.$tableKey.'=>'.$fieldKey);
		return $this->fields[$tableKey][$fieldKey]['name'];
	}
	
	/*********************************************************************/
	
	/**
	 * Get field name for provided field key(s)
	 *
	 * @param	array	in this format: array('TKY'=>'FieldKey')
	 * @return	array	field name
	 */
	public function get_field_names($fieldKeyArray)
	{
		// validate parameter
		if( ! is_array($fieldKeyArray) ) $this->error('parameter was not an array');
	}
	
	/*********************************************************************/
	
	/**
		INTERACT WITH FILEMAKER API
		This function simplifies retrieving all database data needed for a page.
		$fieldKeyArray is an array of field key's used to retrieve/set data in the $field array
		
		related fields should be in the format: 'tableName::fieldKey'
		all other field key's should match the value in the $field array
		
		REVISED PURPOSE:
			- return data in an array
			- related values should be nested arrays
		
		RETURN VALUE:
			- if NOT halted by error
			- array of data
				- key = 0, 1, 2, etc. each key represents a record
				- value = array of field names/values
					- key = field name
						can also be an array of related values, similar to the top level of this array
					- value = fild value
	*/
	public function retrieve_data($resultObject, $defTableKey=NULL, $retrieveRecId=FALSE)
	{
		$this->log('debug', __Method__.' called');
		$data = array(	// variable to hold values returned by this method
			'records' => array(),
			'fieldKeys' => array(),
			'fieldLabels' => array(),
			'foundSetCount' => 0,
		);
		$retRecords = array();
		$retRecord;
		$retPortalRecords = array();
		$retPortalRecord;
		
		// VALIDATE PARAMETERS
		if( $this->exitOnError($resultObject)==401 ){
			// return empty array if no records found
			return $data;
		}
		if( ! is_object($resultObject) ) {
			$this->error( 'parameter was not an object' );
			return;
		}
		if( get_class($resultObject)!='FileMaker_Result' ) {
			$this->error( 'object parameter was not FileMaker_Result, was: ' . get_class($resultObject) );
			return;
		}
		
		// GATHER ARRAY OF FIELDS
		$fields = $resultObject->getFields();
		// get field names from portals
		// if portals exist, they will be stored by the key 'portals' in the $fields array
		// there is an array of records for each portal
		if( count($resultObject->getRelatedSets()) > 0 ) {
			foreach( $resultObject->getLayout()->getRelatedSets() as $relatedTable => $relatedSet ) {
				$portals[$relatedTable] = $relatedSet->listFields();
			}
			$fields['portals'] = $portals;
		}

		// EXTRACT RECORD DATA
		$records=$resultObject->getRecords();
		foreach( $records as $record ){
			foreach( $fields as $field ) {
				if( is_array($field) ) {
				// PORTAL RECORDS
					foreach( $field as $portal => $portalFields ) {
						//initialize variable
						$retPortalRecords = array();
						// get records from portal
						$relatedRecords=$record->getRelatedSet($portal);
						if( ! FileMaker::isError($relatedRecords) ){
							foreach( (array)$relatedRecords as $relatedRecord ) {
								//initialize variable
								$retPortalRecord = array();
								foreach( $portalFields as $portalField ) {
									@$fieldObject = $relatedRecord->getLayout()->getField( $portalField );
									if( FileMaker::isError($fieldObject) ) {
										$this->error( 'field does not exist in record object (not on layout): ' . $portalField );
									}
									$retPortalRecord[] = $this->retrieveValue( $fieldObject, $relatedRecord );
								}
								if ($retrieveRecId) $retPortalRecord['recid'] = $relatedRecord->getRecordId();
								$retPortalRecords[] = $retPortalRecord;
							}
						}
						$retRecord['portals'][$portal] = $retPortalRecords;
					}
				} else {
				//STANDARD and RELATED FIELD
					$fieldObject = $record->getLayout()->getField( $field );
					$retRecord[] = $this->retrieveValue( $fieldObject, $record );
				}
			} // END foreach fields
			if ($retrieveRecId) $retRecord['recid'] = $record->getRecordId();
			$retRecords[] = $retRecord;
			$retRecord=NULL;
		} // END foreach records
		
		$data['records'] = $retRecords ;
		$data['fieldNames'] = $fields;
		$data['fieldKeys'] = $this->get_field_keys($fields, $defTableKey);
		$data['fieldLabels'] = $this->get_field_labels( $data['fieldKeys'] );
		$data['foundSetCount'] = (int)$resultObject->getFoundSetCount();
		
		return $data;
	}
	//END method retrieve_data
	
	/*********************************************************************/
	
	/**
	 * will return Field Value or NULL
	 * Only used by retrieve_data method.
	 */
	private function retrieveValue( $fieldObject, $recordObject )
	{
		// VALIDATE PARAMETERS
		if( ! is_object($fieldObject) ) {
			$this->error( 'parameter was not an object' );
		}
		if( get_class($fieldObject)!='FileMaker_Field' ) {
			$this->error( 'object parameter was not FileMaker_Field, was: ' . get_class($fieldObject) );
		}
		if( ! is_object($recordObject) ) {
			$this->error( 'parameter was not an object' );
		}
		if( get_class($recordObject)!='FileMaker_Record' ) {
			$this->error( 'object parameter was not FileMaker_Record, was: ' . get_class($recordObject) );
		}
	
		$fieldType = $fieldObject->getResult();
		$fieldName = $fieldObject->getName();
		
		// set $value depending on field type
		if( $fieldType == 'text' OR $fieldType == 'number' OR $fieldType == 'container' ) {
			$value = $recordObject->getField( $fieldName );
		} elseif( $fieldType == 'date' OR $fieldType == 'time' OR $fieldType == 'timestamp' ) {
			$value = $recordObject->getFieldAsTimestamp( $fieldName );
		} else {
			$this->error('invalid fieldType: ' . $fieldType);
		}
		
		// return NULL if field value is FileMaker error object
		if( FileMaker::isError( $value ) ) return NULL;
		
		if( $fieldType == 'time' ) {
			// convert timestamp format to # of seconds (adjust for timezone)
			$value += date( 'Z', $value );
		} elseif( $fieldType == 'timestamp' ) {
			// format for display
			$value = date( $this->CI->config->item('formatTimestamp') , $value );
		} elseif( $fieldType == 'date' ) {
			// format for display
			$value = date( $this->CI->config->item('formatDate') , $value );
		}
		
		return $value;
	}
	//END method retrieveValue
	
	/*********************************************************************/
	
	/**
	 * Handle FileMaker Error
	 * 
	 * If no records found:
	 *		- return 401
	 * ElseIf result object is an error:
	 *		- show error to user
	 *		- log error
	 *		- halt script
	 * Else
	 *		- return FALSE (no error)
	 *
	 * @return	mixed	FALSE=no error, or numeric error code
	 */
	public function exitOnError($result)
	{
		// test for error result
		if( FileMaker::isError($result) )
		{
			if( $result->getCode()==401 )
			{
				// ignore "no records found" error
				// let each view test for this by counting the found records (which will be 0, in this case)
				return 401;
			}
			elseif ($result->isValidationError())
			{
				foreach ($result->getErrors() as $error)
				{
					$errors[] =
						'field: '.$error[0]->getName()
						.'<br />'
						.$this->getPreValidationErrStr($error[1])	//message
						;
				}
				$errors = implode('<br /><br />', $errors);
				$this->show_error(
					$errors,						// show to user
					'Validation Error(s)'			// title
				);
			}
			else
			{
				$this->show_error(
					$result->getMessage(),			// show to user
					NULL,							// title
					'code:'.$result->getCode()		// log additional info
				);
			}
		// test for empty result
		}
		else if ($result === NULL)
		{
			$this->error('Result is NULL!');
		}
		return FALSE;
	}
	
	/*********************************************************************/
	
	/**
	 * Return description of Pre Validation error
	 * 
	 * Only used by exitOnError method
	 * 
	 */
	private function getPreValidationErrStr($code)
	{
		$lang = $this->db->getProperty('locale');
		
		if (! $lang) {
			$lang = 'en';
		}
		
		static $strings = array();
		if (empty($strings[$lang])) {
			if (! @include_once 'FileMaker/Error/' . $lang . '.php') {
				include_once 'FileMaker/Error/en.php';
			}
			$strings[$lang] = $__FM_ERRORS;
		}
		
		$errorCode = $code;
		
		switch ($code)
		{
			case 1:
				$errorCode = 509;
				break;
			case 2:
				$errorCode = 502;
				break;
			case 3:
				$errorCode = 511;
				break;
			case 4:
			case 6:
			case 7:
				$errorCode = 500;
				break;
			case 5:
			case 8:
				$errorCode = 501;
				break;
			default:
				break;		
		}
		
		if (isset($strings[$lang][$errorCode])) {
			$errorString = $strings[$lang][$errorCode];
		}
		else {
			$errorString = $strings[$lang][-1];
		}
		
		return $errorCode . " - " . $errorString;
	}
	
	/*********************************************************************/
	
	/**
	 * Load validation rules to property from config file
	 * 
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	private function load_validation_rules()
	{
		if (is_array($this->validation_rules))
		{
			return TRUE;
		}
		elseif ($this->validation_rules===NULL)
		{
			// validation_rules have not been loaded yet, attempt to load
			if ($this->validation_config_file===FALSE)
			{
				$this->log('error', __METHOD__ .' --> validation_config_file does not exist'
				);
				$this->validation_rules = FALSE;
				return FALSE;
			}
			$this->CI->config->load($this->validation_config_file, TRUE);
			$this->validation_rules = $this->CI->config->item($this->validation_config_file);
			if ($this->validation_rules===FALSE)
			{
				return FALSE;
			}
			return TRUE;
		}
		elseif ($this->validation_rules===FALSE)
		{
			return FALSE;
		}
		else
		{
			$this->log('error', __METHOD__ .' --> validation_rules value was unexpected'
			);
			$this->validation_rules = FALSE;
			return FALSE;
		}
	}
	
	/*********************************************************************/
	
	/**
	 * Provide access to validation rules
	 * 
	 * @return	string	validation rule if it exists
	 *					empty string if it doesnt
	 */
	public function get_validation_rule($fieldKey, $category)
	{
		if ($this->load_validation_rules()===TRUE)
		{
			$table = key($fieldKey);
			$field = current($fieldKey);
			if (isset($this->validation_rules[$table][$field][$category]))
			{
				return $this->validation_rules[$table][$field][$category];
			}
		}
		return '';
	}
	
	/*********************************************************************/
	
	/**
	 * Use to display FileMaker container data.
	 * 
	 */
	public function get_container($url)
	{
		// don't parse empty value
		if ($url==FALSE)
		{
			return;
		}
		
		// determine file type
		$type = substr($url, 0, strpos($url, "?"));
		$type = substr($url, strrpos($url, ".") + 1);
		
		// set header
		if($type == "jpg"){
			header('Content-type: image/jpeg');
		}
		else if($type == "gif"){
			header('Content-type: image/gif');
		}
		else{
			header('Content-type: application/octet-stream');
		}
		
		// return container data
		return $this->db->getContainerData($url);
	}
	
}
// END Filemaker_helper Class

/* End of file FileMaker_helper.php */
/* Location: ./application/libraries/FileMaker_helper.php */ 