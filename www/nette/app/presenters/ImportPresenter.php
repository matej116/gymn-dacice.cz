<?php

class ImportPresenter extends BasePresenter {
	
	private $import;

	public function injectImport(GymnDaciceImport $import) {
		$this->import = $import;
	}


	public function actionImport() {
		$this->setLayout(NULL);
		set_time_limit(0);
		$this->import->import();
		echo "imported";
		$this->terminate();
	}
	
}