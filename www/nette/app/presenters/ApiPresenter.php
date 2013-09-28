<?php

class ApiPresenter extends Presenter {
	
	
	CONST API_KEY = 'p8wjqp/7bys@57*a4y19q';

	private $db;

	public function injectDb(Connection $db) {
		$this->db = $db;
	}

	public function startup() {
		parent::startup();
		$this->checkValidApiKey();
	}
	
	/**
	 * don't search for template, since there isn't any, just send payload to output
	 */
	public function beforeRender() {
		$this->sendPayload();
	}

	public function actionUpdateGOneVideo() {
		$data = $this->getHttpRequest()->post;
		$videoRow = array(
			'id' => $data['id'],
			'url' => $data['url'],
			'title' => $data['title'],
			'time' => $data['time'],
		);
		$this->db->query('INSERT INTO `g_one_video` ? ON DUPLICATE KEY UPDATE ?', $videoRow, $videoRow);
	}

	protected function checkValidApiKey($apiKey = NULL) {
		if ($apiKey === NULL) {
			$apiKey = $this->params['apiKey'];
		}
		if (self::API_KEY !== $apiKey) {
			throw new BadRequestException("Provided apiKey is not valid");
		}
	}

}