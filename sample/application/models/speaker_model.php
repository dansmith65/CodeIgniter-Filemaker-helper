<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Speaker_model extends CI_Model {

	function __construct()
	{
		log_message('debug', __Class__.' called');
		parent::__construct();
	}
	
	
	function speaker_list($skip=0, $max=null) {
		$findCommand = $this->filemaker_helper->db->newFindAllCommand('speaker_find');
		$findCommand->setRange($skip, $max);
		$findCommand->addSortRule(
			$this->filemaker_helper->get_field_name('SPK','sortKey'),
			1,
			FILEMAKER_SORT_ASCEND
		);
		$findCommand->setResultLayout('speaker_list');
		$result = $findCommand->execute();
		$data = $this->filemaker_helper->retrieve_data($result, 'SPK');
		return $data;
	}
	
	
	function speaker_detail($recordId) {
		$findCommand = $this->filemaker_helper->db->newFindCommand('speaker_find');
		$findCommand->addFindCriterion(
			$this->filemaker_helper->get_field_name('SPK','id'),
			'=='.$recordId
		);
		$findCommand->setResultLayout('speaker_detail');
		
		$result = $findCommand->execute();
		$data = $this->filemaker_helper->retrieve_data($result, 'SPK');
		return $data;
	}
	
}
// END Speaker_model class

/* End of file speaker_model.php */
/* Location: ./application/models/speaker_model.php */