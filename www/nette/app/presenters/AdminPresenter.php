<?php

/**
 * @TODO create ArticleImageFacade class
 */
class AdminPresenter extends BasePresenter {
	
	private $articles, $images, $db;
	
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

	public function injectImages(PhotoStorage $images) {
		$this->images = $images;
	}

	public function injectDb(Connection $db) {
		$this->db = $db;
	}

	public function renderArticle($id = NULL) {
		if ($id) {
			$this->template->article = $this->articles->getArticle($id);
			$this->template->imagesDir = $this->images->getDir();
		}
	}

	public function renderEvent($id = NULL) {
		// empty function, but it must exists, otherwise parameter $id is not added to subrequests
	}

	public function renderArticles() {
		$this->template->articles = $this->articles->getAllArticles();
	}

	public function renderEvents() {
		$this->template->eventList = $this->db->table('event')->where('date >= CURDATE()')->order('date ASC');
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

	public function handleDeleteAttachment($fileId) {
		$file = $this->articles->getAttachmentFile($fileId);
		$this->articles->deleteAttachment($this->params['id'], $fileId);
		$this->context->attachments->delete($file->filename);
		$this->flashMessage("Přiloha smazána");
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

	public function createComponentChangePhotoTitleForms() {
		return new Multiplier(callback($this, 'createChangePhotoTitleForm'));
	}

	public function createChangePhotoTitleForm($id) {
		$form = new AppForm;
		$form->addText('title', 'Titulek');
		$form->addSubmit('change', 'Změnit');
		$form->addHidden('photoId', $id);
		$form->onSuccess[] = $this->changePhotoTitleFormSubmitted;
		return $form;
	}

	public function changePhotoTitleFormSubmitted($form) {
		$values = $form->values;
		$this->articles->changePhotoTitle($this->params['id'], $values->photoId, $values->title);
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

	public function createComponentAttachmentForm() {
		$form = new AppForm;
		$form->addText('title', 'Titulek');
		$form->addUpload('file', 'Soubor');
		$form->addSubmit('upload', 'Nahrát');
		$form->onSuccess[] = $this->attachmentFormSubmitted;
		return $form;
	}

	public function attachmentFormSubmitted(AppForm $form) {
		$values = $form->values;
		$file = $values->file;
		$filename = $this->context->attachments->save($file);
		$this->articles->addAttachment($this->params['id'], $values->title, $filename);
		$this->flashMessage('Příloha uložena');
		$this->redirect('this');
	}

	public function createComponentEventForm() {
		$form = new AppForm;
		$form->addText('date', 'Datum')
			->setValue(new DateTime53);
		$form->addText('title', 'Titulek');
		$form->addTextArea('text', 'Text');
		if (isset($this->params['id'])) {
			$form->setValues($this->db->table('event')->wherePrimary($this->params['id'])->fetch()->toArray());
			$form->addSubmit('change', 'Upravit');
		} else {
			$form->addSubmit('add', 'Přidat');
		}
		$form->onSuccess[] = $this->eventFormSubmitted;
		return $form;
	}

	public function eventFormSubmitted(AppForm $form) {
		$values = (array) $form->values;
		if (isset($this->params['id'])) {
			$this->db->table('event')->wherePrimary($this->params['id'])->update($values);
			$this->flashMessage('Akce upravena');
		} else {
			$this->db->table('event')->insert($values);
			$this->flashMessage('Akce přidána');
		}
		$this->redirect('events');
	}

}
