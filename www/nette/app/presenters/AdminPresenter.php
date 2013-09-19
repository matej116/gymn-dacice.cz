<?php

class AdminPresenter extends BasePresenter {
	
	private $articles;
	
	public function startup() {
		parent::startup();
		if (!$this->user->loggedIn) {
			$this->flashMessage('Je nutné přihlášení', 'error');
			$this->redirect('Sign:in');
		}
	}

	public function injectArticles(Articles $articles) {
		$this->articles = $articles;
	}

	public function renderArticle($id=NULL) {

	}

	public function createComponentArticleForm() {
		$form = new AppForm;

		$form->addText('title', 'Titulek');
		$form->addTextArea('text', 'Text článku (texy syntax)');

		$form->addText('date', 'Datum přidání')
			->setValue(DateTime53::from('now')->format('Y-m-d'));

		$form->addSelect('g_one_video_id', 'G-one video', $this->articles->getGOneVideos())
			->setPrompt('<Žádné G-one video>');
		$form->addSelect('menu_id', 'Kategorie', $this->menuManager->getMainMenu()->fetchPairs('id', 'title'));

		if (isset($this->params['id'])) {
			$form->addHidden('articleId', $this->params['id']);
			// @TODO set values
			$form->addSubmit('save', 'Uložit');
		} else {
			$form->addSubmit('add', 'Přidat');
		}

		$form->onSuccess[] = $this->articleFormSubmitted;
		return $form;
	}

	public function articleFormSubmitted(AppForm $form) {
		$inserted = $this->articles->insert($form->values);
		if ($inserted) {
			$this->flashMessage('Článek byl vložen');
		} else {
			$this->flashMessage('Při ukládání článku došlo k chybě');
		}
		$this->redirect('this');
	}

}