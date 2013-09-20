<?php

class ImportPresenter extends BasePresenter {
	
	public function actionImport() {
		$this->setLayout(NULL);
		set_time_limit(0);
		$this->context->getByType('GymnDaciceImport')->import();
		echo "imported";
		$this->terminate();
	}

	public function actionCopyFiles() {
		$this->setLayout(NULL);
		set_time_limit(0);
		$db = $this->context->getByType('Connection');

		$fromDir = $this->context->params['wwwDir'] . '/../www/foto';
		$toDir = $this->context->params['wwwDir'] . '/' . $this->context->params['photos']['dir'];
		foreach ($db->table('photo') as $row) {
			foreach (array('filename_photo', 'filename_thumb') as $key) {
				$fileName = $row->$key;
				$from = "$fromDir/$fileName";
				$to = "$toDir/$fileName";
				if (file_exists($to)) {
					continue;
				}
				echo "Copying $from to $to <br>";
				if (@copy($from, $to) === FALSE) {
					echo "----------------FAILED-----------<br>";
				}
			}
		}

		echo "----------------------------------------------------------------- FILES<br>";

		$fromDirs = array(
			$this->context->params['wwwDir'] . '/../www/files',
			$this->context->params['wwwDir'] . '/../www/files/deska',
			$this->context->params['wwwDir'] . '/../www/files/ke-stazeni',
		);
		$toDir = $this->context->params['wwwDir'] . '/files';
		foreach ($db->table('file') as $row) {
			$fileName = $row->filename;
			$to = "$toDir/$fileName";
			if (file_exists($to)) {
				continue;
			}
			foreach ($fromDirs as $dir) {
				if (file_exists($from = "$dir/$fileName")) {
					break;
				}
			}
			echo "Copying $from to $to <br>";
			if (@copy($from, $to) === FALSE) {
				echo "----------------FAILED-----------<br>";
			}
		}


		$this->terminate();
	}
	
}