<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{

	protected $menuManager;

	private $foods;

	/** @var DateFormatter */
	private $dateFormatter;

	/** @var Texy */
	private $texy;

	protected $jokes;


	public function injectMenuManager(MenuManager $menuManager) {
		$this->menuManager = $menuManager;
	}

	public function injectFoods(Foods $foods) {
		$this->foods = $foods;
	}

	public function injectDateFormatter(DateFormatter $dateFormatter) {
		$this->dateFormatter = $dateFormatter;
	}

	public function injectTexy(Texy $texy) {
		// @TODO move config to config.neon
		$texy->headingModule->top = 3; // set top heading to <h3>
		$this->texy = $texy;
	}

	public function injectJokes(Jokes $jokes) {
		$this->jokes = $jokes;
	}

	/**
	 * this function occurs before all render<action>() method
	 * fill template with values common for all presenters
	 */
	public function beforeRender() {
		$template = $this->template;

		$menuManager = $this->menuManager;
		$template->alerts = $menuManager->getAlerts();
		$template->banners = $menuManager->getBanners();
		$template->menu = $menuManager->getMainMenu();
		$template->events = $menuManager->getEvents();
		$template->videos = $menuManager->getLastGOneVideos();

		/** @TODO avoid $this->context */
		$menu = $this->context->params['menu'];
		$current = NULL;
		foreach ($menu as $title => &$link) {
			if (Strings::startsWith($link, 'http://')) {
				// $link is absolute URL - do nothing with it
			} elseif ($link[0] === '/' && $link[1] !== '/') {
				// $link is relative URL - prepend with $basePath
				$link = $this->getHttpRequest()->url->baseUrl . ltrim($link, '/');
			} else {
				$origLink = $link;
				$link = $this->link($origLink);
				if ($current === NULL && $this->isLinkCurrent($origLink)) {
					$current = $link;
				}
			}
		}
		$template->specialPagesMenu = $menu;
		$template->specialPagesCurrent = $current;
		$template->foods = $this->foods->getFutureFoods();

		$template->latestJoke = $this->jokes->getLatest();
		$template->jokeImagesDir = $this->context->params['jokes']['dir'];

		if ($this->isAjax()) {
		    $this->invalidateControl('title');
		    $this->invalidateControl('specialPages');
		    $this->invalidateControl('flashes');
		    $this->invalidateControl('content');
		}
	}

	protected function createTemplate($class = NULL) {
		$template = parent::createTemplate($class);
		$template->registerHelper('texy', callback($this->texy, 'process'));
		$template->registerHelper('czechDate', callback($this->dateFormatter, 'formatDate'));
		return $template;
	}


}
