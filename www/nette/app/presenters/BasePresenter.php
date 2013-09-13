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
		$this->texy = $texy;
	}

	/**
	 * this function occurs before all render<action>() method
	 * fill template with values common for all presenters
	 */
	public function beforeRender() {
		$template = $this->template;

		$template->banners = $this->menuManager->getBanners();
		$template->menu = $this->menuManager->getMainMenu();
		$template->events = $this->menuManager->getEvents();

		/** @TODO avoid $this->context */
		$menu = $this->context->params['menu'];
		foreach ($menu as $title => &$link) {
			if (Strings::startsWith($link, 'http://')) {
				// $link is absolute URL - do nothing with it
			} elseif ($link[0] === '/' && $link[1] !== '/') {
				// $link is relative URL - prepend with $basePath
				$link = $this->template->basePath . $link;
			} else {
				$link = $this->link($link);
			}
		}
		$template->specialPagesMenu = $menu;
		$template->foods = $this->foods->getFutureFoods();

		if ($this->isAjax()) {
		    $this->invalidateControl('title');
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
