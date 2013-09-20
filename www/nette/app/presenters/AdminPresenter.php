<?php

/**
 * @TODO create ArticleImageFacade class
 */
class AdminPresenter extends BasePresenter {
	
	private $articles, $images;
	
	public function startup() {
		parent::startup();
		if (!$this->user->loggedIn) {
			$this->flashMessage('Je nutné přihlášení', 'error');
			$this->redirect('Sign:in');
		}
	}

	public function actionDefault() {
		// there is no default action, redirect to "articles"
		$this->redirect('articles');
	}

	public function injectArticles(Articles $articles) {
		$this->articles = $articles;
	}

	public function injectImages(PhotoStorage $images) {
		$this->images = $images;
	}

	public function renderArticle($id = NULL) {
		if ($id) {
			$this->template->article = $this->articles->getArticle($id);
			$this->template->imagesDir = $this->images->getImagesDir();
		}
	}

	public function renderArticles() {
		$this->template->articles = $this->articles->getAllArticles();
	}

	public function handleDeletePhoto($photoId) {
		$photo = $this->articles->getPhoto($this->params['id'], $photoId);
		if ($photo) {
			$this->images->delete($photo->filename_photo, $photo->filename_thumb);
			$this->articles->deletePhoto($this->params['id'], $photoId);
		} else {
			throw new InvalidStateException("No photo found for this article and id = $photoId");
		}
		$this->flashMessage("Fotka smazána");
		$this->redirect('this');
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
			$form->setValues($this->articles->getArticle($this->params['id']));
			$form->addSubmit('save', 'Uložit');
		} else {
			$form->addSubmit('add', 'Přidat');
		}

		$form->onSuccess[] = $this->articleFormSubmitted;
		return $form;
	}

	public function createComponentPhotoUploadForm() {
		$form = new AppForm;
		$form->addText('title', 'Titulek');
		$form->addUpload('photo', 'Soubor');
		$form->addSubmit('upload', 'Nahrát');
		$form->onSuccess[] = $this->photoUploadFormSubmitted;
		return $form;
	}

	public function articleFormSubmitted(AppForm $form) {
		$articleId = isset($this->params['id']) ? $this->params['id'] : NULL;
		if ($articleId) {
			$success = $this->articles->update($articleId, $form->values);
		} else {
			$success = $this->articles->insert($form->values);
		}
		if ($success) {
			$this->flashMessage($articleId ? 'Článek byl uložen' : 'Článek byl vložen');
		} else {
			$this->flashMessage('Při ukládání článku došlo k chybě');
		}
		$this->redirect('articles');
	}

	public function photoUploadFormSubmitted(AppForm $form) {
		$values = $form->values;
		$photoFile = $values->photo;
		$photos = $this->images;
		$photoParams = $this->context->params['photos'];
		$fileNamePhoto = $photos->save($photoFile, $photoParams['maxSizePx'], 'f_');
		$fileNameThumb = $photos->save($photoFile, $photoParams['maxSizeThumbPx'], 'n_');
		$this->articles->addPhoto($this->params['id'], $values->title, $fileNamePhoto, $fileNameThumb);
		$this->flashMessage('Fotka byla úspěšně nahrána a uložena');
		$this->redirect('this');
	}

}
