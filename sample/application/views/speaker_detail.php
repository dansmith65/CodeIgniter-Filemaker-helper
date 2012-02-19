<h1>Speaker Detail</h1>

<?php
	foreach( $record as $key => $value ){
		$this->table->add_row(
			array(
				'data' => $fieldLabels[$key],
				'style' => 'font-weight:bold',
			),
			$value
		);
	}
	echo $this->table->generate();
?>
