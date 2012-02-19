<h1>Speaker List</h1>

<?php
	if (count($records)==0)
	{
		echo '<p class="dbError">no records found</p>';
	}
	else
	{
		$this->table->set_heading($fieldLabels);
		echo $this->table->generate($records);
		/*echo $pagination;*/
	}
?>
