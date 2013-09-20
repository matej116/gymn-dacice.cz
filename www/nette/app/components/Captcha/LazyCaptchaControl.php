<?php

class LazyCaptchaControl extends CaptchaControl {
	
	protected $imageUrl;

	public function __construct($errorMessage = NULL, $placeholder = NULL, $imageUrl = 'captcha/image') {
		if ($errorMessage) {
			parent::__construct($errorMessage);
		} else {
			parent::__construct();
		}
		if ($placeholder) {
			$this->controlPrototype->placeholder = $placeholder;
		}
		$this->imageUrl = $imageUrl;
	}

	public function createImageRoute() {
		// $this->imageUrl ---> $this->sendImage()
		return new Route($this->imageUrl, callback($this, 'sendImage'));
	}

	public function getImageUri() {
		if ($presenter = $this->getForm()->getPresenter(FALSE)) {
			// workaround for not accessible getHttpRequest \/
			$basePath = rtrim($presenter->getContext()->getByType('HttpRequest')->getUrl()->getBaseUrl(), '/');
			return $basePath . '/' . $this->imageUrl . '?' . time(); // unique URL to force no cache
		} else {
			// presenter (therefore basePath) is not availible, fallback do data URI
			return parent::getImageUri();
		}
	}

	public function sendImage() {
		$this->getImage()->send();
	}

}