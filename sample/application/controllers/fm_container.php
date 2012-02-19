<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fm_container extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->library('filemaker_helper');
	}
	
	/**
	 * URI Segments:
	 * 3	url
	 */
	function file()
	{
		// VALIDATE
		if ($this->uri->segment(3)===FALSE)
		{
			return;
		}
		
		// GET DATA FROM FILEMAKER
		echo $this->filemaker_helper->get_container(
			base64_decode($this->uri->segment(3))
		);
	}
	
}
// END Fm_container Class

/* End of file fm_container.php */
/* Location: ./application/controllers/fm_container.php */