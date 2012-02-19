<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| FileMaker Validation
| -------------------------------------------------------------------------
| 
| Provides a single place to enter server and client-side validation
| settings for FileMaker fields.
|
*/
$config = array(
	'SPK' => array(
		'company' => array(
			'ci' => 'max_length[41]',
			'jquery' => 'maxlength: 41'
		),
		'nameFirst' => array(
			'ci' => 'required|max_length[25]',
			'jquery' => 'required: true, maxlength: 25'
		),
		'nameLast' => array(
			'ci' => 'required|max_length[25]',
			'jquery' => 'required: true, maxlength: 25'
		),
	),
);

/* End of file fmdb_validation.php */
/* Location: ./application/config/fmdb_validation.php */