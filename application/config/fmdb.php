<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| FileMaker Database
| -------------------------------------------------------------------------
| 
| This file lets you define a database to connect to, and all the necessary
| information needed to find/access/use/display the data.
| For any field that will be accessed by PHP, you need to define custom
| table/field name, which provides a single place to modify if these
| elements were to be re-named in the FileMaker database. It also allows
| for a single place to enter a custom field lable for fields.
|
*/
$config = array(
	'database' => 'DevCon2Go11_web',
	'hostname' => '127.0.0.1',
	'username' => 'web',
	'password' => 'devcon',
	
	// file that holds validation rules
	// DO NOT put file extension (.php, etc.)
	'validation_config_file' => 'fmdb_validation',
	
	// map Table Occurrences in FileMaker to Table Key's
	'tables' => array(
		'SPK' => array('Speaker',),
	),
	
	// map Fields in FileMaker to Table Key's, and Field Key's
	'fields' => array(
		'SPK' => array(
			'id' => array(
				'name' => '__kp_SPK_ID',
				'label' => 'Speaker ID'
			),
			'nameFirst' => array(
				'name' => 'Speaker_Name_First',
				'label' => 'First Name'
			),
			'nameLast' => array(
				'name' => 'Speaker_Name_Last',
				'label' => 'Last Name'
			),
			'nameLastFirst' => array(
				'name' => '_Speaker_LastFirst',
				'label' => 'Name'
			),
			'nameFirstLast' => array(
				'name' => '_Speaker_FirstLast',
				'label' => 'Name'
			),
			'nameCompany' => array(
				'name' => 'Speaker_Company',
				'label' => 'Company'
			),
			'bio' => array(
				'name' => 'Speaker_Bio',
				'label' => 'Bio'
			),
			'photo' => array(
				'name' => 'Speaker_Photo_FullSize',
				'label' => 'Photo'
			),
			// utility fields
			'sortKey' => array(
				'name' => 'zz_speaker_sort_key',
				'label' => 'Sort Key' // this will probably never be used
			),
		),
	),
);

/* End of file fmdb.php */
/* Location: ./application/config/fmdb.php */