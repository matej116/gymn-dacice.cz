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

	
	public function injectArticles(Articles $articles) {
		$this->articles = $articles;
	}

	public function injectPhotos(PhotoStorage $photos) {
		$this->photos = $photos;
	}

	public function renderList($menu = NULL, $page = 1) {
		$template = $this->template;
		if ($menu !== NULL) {
			$template->menuTitle = $this->menuManager->getMenuTitle($menu);
		}
		$template->page = $page;
		$template->isPrevious = $page > 1;
		list($template->articles, $template->isNext) = $this->articles->getArticles($menu, $page);
		$template->imagesDir = $this->photos->getDir();
	}

	public function renderShow($id) {
		$template = $this->template;
		$template->article = $this->articles->getArticle($id);
		$template->imagesDir = $this->photos->getDir();
	}

}
