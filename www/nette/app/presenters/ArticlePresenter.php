<?php

/**
 * Article presenter.
 */
class ArticlePresenter extends BasePresenter
{

	/** @var Articles */
	private $articles;

	/**
	 * @var PhotoStorage
	 * @todo don't load $photos when it is not needed
	 */
	private $photos;

	public function startup() {
		parent::startup();
		$this->photos = $this->context->photos;
	}
	
	public function injectArticles(Articles $articles) {
		$this->articles = $articles;
	}

	public function renderList($menu = NULL, $page = 1) {
		$template = $this->template;
		if ($menu !== NULL) {
			if ($template->menuTitle = $this->menuManager->getMenuTitle($menu) === NULL) {
				throw new BadRequestException('Tato kategorie neexistuje');
			}
		}
		$template->page = $page;
		$template->isPrevious = $page > 1;
		list($template->articles, $template->isNext) = $this->articles->getArticles($menu, $page);
		$template->imagesDir = $this->photos->getDir();
	}

	public function renderShow($id) {
		$template = $this->template;
		$template->article = $article = $this->articles->getArticle($id);
		if (!$article) {
			throw new BadRequestException("Článek nenalazen");
		}
		$template->imagesDir = $this->photos->getDir();
	}

}
