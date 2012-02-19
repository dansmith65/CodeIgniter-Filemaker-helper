<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Speaker extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->helper('url');
		$this->load->library('filemaker_helper');
	}
	
	function index()
	{
		// define default method
		$this->speaker_list();
	}
	
	/**
	 * URI Segments:
	 * 3	starting record number ('cur_page' in pagination class)
	 */
	function speaker_list()
	{
		// references to the pagination class have been commented because I 
		// customized the class to work how it is shown here
		
		// LOAD RESOURCES
		/*$this->load->library('pagination');*/
		$this->load->library('table');
		$this->load->model('speaker_model');
		
		// INITIALIZE RESOURCES
		/*$pagination = $this->config->item('pagination');
		$pagination['base_url'] = site_url(
			str_replace('::', '/', strtolower(__METHOD__))
		);
		$this->pagination->initialize($pagination);*/
		
		// GET DATA FROM FILEMAKER
		$data = $this->speaker_model->speaker_list(
			/*$this->pagination->cur_page,
			$this->pagination->per_page*/
		);
		
		// FORMAT/MODIFY DATA
		// get field location in array
		$key_id = array_search( array('SPK'=>'id'), $data['fieldKeys'] );
		// modify data
		foreach ($data['records'] as &$record)
		{
			// make id field a link to detail page
			if ($key_id!==FALSE)
			{
				$record[$key_id] = anchor(
					'speaker/speaker_detail/'.$record[$key_id],
					$record[$key_id]
				);
			}
		}
		
		// ADD PAGINATION
		/*$this->pagination->set_total_rows((int)$data['foundSetCount']);
		$data['pagination'] = $this->pagination->create_links();*/
		
		$data['content_view'] = 'speaker_list';
		$this->load->view('template/default', $data);
	}
	
	
	
	/**
	 * URI Segments:
	 * 3	recordId to display
	 */
	function speaker_detail()
	{
		// VALIDATE
		if ($this->uri->segment(3)===FALSE)
		{
			show_404(current_url());
		}
		
		// LOAD RESOURCES
		$this->load->library('table');
		$this->load->model('speaker_model');
		
		// GET DATA FROM FILEMAKER
		$data = $this->speaker_model->speaker_detail(
			$this->uri->segment(3)
		);
		
		// VALIDATE FOUND SET
		if (count($data['records'])==0)
		{
			show_404(current_url());
		}
		
		// THIS PAGE ONLY DISPLAYS A SINGLE RECORD
		$data['record'] = $data['records'][0];
		unset($data['records']);
		
		// FORMAT/MODIFY DATA
		// get field location in array
		$photo_id = array_search( array('SPK'=>'photo'), $data['fieldKeys'] );
		// modify data
		// base64_encode the url, to prevent CodeIgniter from clipping it after the ?
		$data['record'][$photo_id] = site_url(array(
			'fm_container',
			'file',
			base64_encode($data['record'][$photo_id])
		));
		$data['record'][$photo_id] = '<img src="'.$data['record'][$photo_id].'" />';
		
		// convert ALL new lines to html break
		foreach( $data['record'] as &$field ){
			$field = nl2br(trim($field));
		}
		
		$data['content_view'] = 'speaker_detail';
		$this->load->view('template/default', $data);
	}
	
	
}
// END Speaker Class

/* End of file speaker.php */
/* Location: ./application/controllers/speaker.php */