<?php

/**
 * @TODO create ArticleImageFacade class
 */
class AdminPresenter extends BasePresenter {
	
	private $articles, $photos, $db;

	private $jokeImages;

	/**
	 * @var string
	 * @persistent
	 */
	public $type = NULL;

	protected $pageTitles = array(
		'article' => 'Články',
		'event' => 'Akce',
		'joke' => 'Vtipy',
		'banner' => 'Bannery',
		'download' => 'Ke stažení',
		'document' => 'Úřední deska',
	);
	
	public function startup() {
		parent::startup();
		if (!$this->user->loggedIn) {
			$this->flashMessage('Je nutné přihlášení', 'error');
			$this->redirect('Sign:in');
		}
		// these services cannot be injected since they are instances of one class
		$this->photos = $this->context->photos;
		$this->jokeImages = $this->context->jokeImages;
	}

	public function injectArticles(Articles $articles) {
		$this->articles = $articles;
	}

	public function injectDb(Connection $db) {
		$this->db = $db;
	}

	public function beforeRender() {
		if ($this->type && !$this->user->isAllowed($this->type)) {
			$this->flashMessage("Nemáte oprávnění k: '$this->type'", 'error');
			$this->redirect('default', array('type' => NULL));
		}
		parent::beforeRender();
		$this->template->authorizator = $this->context->getByType('IAuthorizator');
		$this->template->title = @$this->pageTitles[$this->type];
	}

	public function renderDefault() {
		$pages = array();
		foreach ($this->pageTitles as $page => $title) {
			if ($this->user->isAllowed($page)) {
				$pages[$page] = $title;
			}
		}
		$this->template->pages = $pages;
	}

	public function renderItem($id = NULL) {
		if ($this->type == 'article' && $id) {
			$this->template->subTemplate = 'article';
			$this->template->article = $this->articles->getArticle($id, FALSE);
			$this->template->imagesDir = $this->photos->getDir();
		}
	}

	public function renderItems() {
		switch ($this->type) {
			case 'article':
				$items = $this->articles->getAllArticles();
				break;
			
			case 'event':
				$items = $this->db->table('event')->where('date >= CURDATE()')->order('date ASC');
				break;

			case 'joke':
				$items = $this->db->table('joke')->order('date_from DESC');
				break;

			case 'banner':
				$items = $this->db->table('banner')->order('order');
				break;

			case 'download':
				$items = $this->db->table('download');
				break;

			case 'document':
				$items = $this->db->table('document');
				break;

			default: // NULL or anything other
				throw new InvalidArgumentException("Type '$this->type' is not implemented");
				break;
		}
		$this->template->items = $items;
	}

	public function createComponentEditForm() {
		if ($this->type == 'edit') {
			// avoid recursion
			throw new InvalidStateException("'$this->type' is not allowed page name, factory recursion would occured");
		}
		return $this->createComponent($this->type . 'Form');
	}

	public function handleDeletePhoto($photoId) {
		$photo = $this->articles->getPhoto($this->params['id'], $photoId);
		if ($photo) {
			$this->photos->delete($photo->filename_photo, $photo->filename_thumb);
			$this->articles->deletePhoto($this->params['id'], $photoId);
		} else {
			throw new InvalidStateException("No photo found for this article and id = $photoId");
		}
		$this->flashMessage("Fotka smazána");
		$this->redirect('this');
	}

	public function handleDeleteAttachment($fileId) {
		$this->db->beginTransaction();
		$this->articles->deleteAttachment($this->params['id'], $fileId);
		$this->context->files->deleteById($fileId);
		$this->db->commit();
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

		$form->addCheckBox('visible', 'Zobrazit?')
			->setValue(TRUE);

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
		dump($form->values);
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
		$this->redirect('items');
	}

	public function photoUploadFormSubmitted(AppForm $form) {
		$values = $form->values;
		$photoFile = $values->photo;
		$photos = $this->photos;
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
		$this->db->beginTransaction();
		$fileId = $this->context->files->save($values->file, '', $values->title);
		$this->articles->pairAttachment($this->params['id'], $fileId);
		$this->db->commit();
		$this->flashMessage('Příloha uložena');
		$this->redirect('this');
	}

	public function createComponentEventForm() {
		$form = new AppForm;
		$form->addText('date', 'Datum')
			->setValue(new DateTime53);
		$form->addText('date_to', 'Datum do')
			->setValue('');
		$form->addText('title', 'Titulek');
		$form->addTextArea('text', 'Text');
		$form->addUpload('file', 'Přiložený soubor');
		if (isset($this->params['id'])) {
			$event = $this->db->table('event')->wherePrimary($this->params['id'])->fetch()->toArray();
			$form->addSubmit('change', 'Upravit');
			if ($event['file_id']) {
				$fileName = $this->context->files->getFilename($event['file_id']);
				$form->addSubmit('deleteFile', "Upravit a smazat přiložený soubor ($fileName)");
			}
			$form->setValues($event);
		} else {
			$form->addSubmit('add', 'Přidat');
		}
		$form->onSuccess[] = $this->eventFormSubmitted;
		return $form;
	}


	public function createComponentDownloadForm() {
		$form = new AppForm;
		$form->addText('title', 'Titulek');
		$form->addUpload('file', 'Přiložený soubor');
		if (isset($this->params['id'])) {
			$download = $this->db->table('download')->wherePrimary($this->params['id'])->fetch();
			$form->addSubmit('change', 'Upravit');
			$form->addSubmit('delete', 'Smazat')
				->getControlPrototype()->onclick = 'return confirm("Opravdu?");';
			$form['title']->setValue($download->file->title);
		} else {
			$form->addSubmit('add', 'Přidat');
		}
		$form->onSuccess[] = $this->downloadFormSubmitted;
		return $form;
	}

	public function createComponentDocumentForm() {		
		$form = new AppForm;
		$form->addText('title', 'Titulek');
		$form->addText('date_from', 'Datum')
			->setValue(new DateTime53);
		$form->addText('date_to', 'Datum do')
			->setValue('');
		$form->addTextArea('description', 'Popis');
		$form->addUpload('file', 'Přiložený soubor');
		if (isset($this->params['id'])) {
			$document = $this->db->table('document')->wherePrimary($this->params['id'])->fetch();
			$form->addSubmit('change', 'Upravit');
			$form->addSubmit('delete', 'Smazat')
				->getControlPrototype()->onclick = 'return confirm("Opravdu?");';
			$form->setValues($document->toArray());
			$form['title']->setValue($document->file->title);
		} else {
			$form->addSubmit('add', 'Přidat');
		}
		$form->onSuccess[] = $this->documentFormSubmitted;
		return $form;
	}


	public function createComponentBannerForm() {
		$form = new AppForm;

		$form->addText('title', 'Titulek');
		$form->addText('link', 'Odkaz');
		$form->addText('order', 'Pořadí')
			->addRule(Form::INTEGER, 'Musíte zadat celé číslo');
		$form->addUpload('imagefile', 'Obrázek');
		if (isset($this->params['id'])) {
			$banner = $this->db->table('banner')->wherePrimary($this->params['id'])->fetch()->toArray();
			$form->addSubmit('change', 'Upravit');
			$form->addSubmit('delete', 'Smazat')
				->getControlPrototype()->onclick = 'return confirm("Opravdu?");';
			$form->setValues($banner);
		} else {
			$form->addSubmit('add', 'Přidat');
		}
		$form->onSuccess[] = $this->bannerFormSubmitted;
		return $form;
	}

	public function createComponentJokeForm() {
		$form = new AppForm;
		$form->addText('date_from', 'Zobrazit od data:')
    		->addRule(Form::PATTERN, 'Zadejte ve formátu YYYY-MM-DD', '\d{4}-\d\d-\d\d')
    		->setValue(DateTime53::from('now')->format('Y-m-d'));
    	$form->addTextArea('text', 'Text vtipu', 30, 2);
    	$form->addUpload('file', 'Obrázek');
    	if (isset($this->params['id'])) {
    		$row = $this->db->table('joke')->where('id', $this->params['id'])->fetch();
			$form->setValues(array(
				'date_from' => $row->date_from->format('Y-m-d'),
				'text' => $row->text,
			));
			$form->addSubmit('save', 'Uložit');
		} else {
			$form->addSubmit('add', 'Přidat');
		}
		$form->onSuccess[] = $this->jokeFormSubmitted;
		return $form;
	}


	public function downloadFormSubmitted(AppForm $form) {
		$values = (array) $form->values;
		$file = $values['file'];
		unset($values['file']);
		$this->db->beginTransaction();
		if ($file->isOk()) {
			$fileId = $this->context->files->save($file, '', $values['title']);
			$this->flashMessage('Novy soubor byl pridan');
			$values['file_id'] = $fileId;
			$fileWillBeDeleted = TRUE; // delete previous file
		}
		if (isset($this->params['id'])) {
			$row = $this->db->table('download')->wherePrimary($this->params['id'])->fetch();
			$fileId = $row->file_id;
			$title = $values['title'];
			unset($values['title']);
			if (!empty($row))
				$row->update($values);
			if ($file->isOk()) {
				$this->context->files->deleteById($fileId);
				$this->flashMessage('Stary soubor byl odebran a smazan');
			}
			if ($form->submitted->name == 'delete') {
				$row->delete();
				@$this->context->files->delete($row->file->filename);
				$this->flashMessage('Soubor ke stazeni byl smazan');
			} else {
				$this->context->files->updateTitle($row['file_id'], $title);
				$this->flashMessage('Soubor ke stažení upraven');
			}
		} else {
			if (!$file->isOk()) {
				$this->db->rollback();
				$form->addError('neni pridan soubor');
				return;
			}
			unset($values['title']);
			$this->db->table('download')->insert($values);
			$this->flashMessage('Soubor ke stažení přidán');
		}
		$this->db->commit();
		$this->redirect('items');
	}

	public function documentFormSubmitted(AppForm $form) {
		$values = (array) $form->values;
		$file = $values['file'];
		unset($values['file']);
		$this->db->beginTransaction();
		if ($file->isOk()) {
			$fileId = $this->context->files->save($file, '', $values['title']);
			$this->flashMessage('Novy soubor byl pridan');
			$values['file_id'] = $fileId;
			$fileWillBeDeleted = TRUE; // delete previous file
		}
		if (isset($this->params['id'])) {
			$row = $this->db->table('document')->wherePrimary($this->params['id'])->fetch();
			$fileId = $row->file_id;
			$title = $values['title'];
			unset($values['title']);
			if (!empty($row))
				$row->update($values);
			if ($file->isOk()) {
				$this->context->files->deleteById($fileId);
				$this->flashMessage('Stary soubor byl odebran a smazan');
			}
			if ($form->submitted->name == 'delete') {
				$row->delete();
				@$this->context->files->delete($row->file->filename);
				$this->flashMessage('Soubor byl smazan z urdni desky');
			} else {
				$this->context->files->updateTitle($row['file_id'], $title);
				$this->flashMessage('Soubor na uredni desce upraven');
			}
		} else {
			if (!$file->isOk()) {
				$this->db->rollback();
				$form->addError('neni pridan soubor');
				return;
			}
			unset($values['title']);
			$this->db->table('document')->insert($values);
			$this->flashMessage('Soubor přidán na uredni desku');
		}
		$this->db->commit();
		$this->redirect('items');
	}

	public function eventFormSubmitted(AppForm $form) {
		$values = (array) $form->values;
		if ($values['date_to'] === '') {
			$values['date_to'] = NULL;
		}
		$file = $values['file'];
		unset($values['file']);
		$this->db->beginTransaction();
		$fileWillBeDeleted = isset($this->params['id']) && $form->submitted->name === 'deleteFile';
		$values['file_id'] = NULL;
		if (!$fileWillBeDeleted && $file->isOk()) {
			$fileId = $this->context->files->save($file, '', 'Příloha k akci');
			$this->flashMessage('Novy soubor byl pridan k akci');
			$values['file_id'] = $fileId;
			$fileWillBeDeleted = TRUE; // delete previous file
		}
		if (isset($this->params['id'])) {
			$eventSelection = $this->db->table('event')->wherePrimary($this->params['id']);
			if ($fileWillBeDeleted) {
				$fileId = $eventSelection->fetch()->file_id;
			}
			$eventSelection->update($values);
			if ($fileWillBeDeleted && $fileId) {
				$this->context->files->deleteById($fileId);
				$this->flashMessage('Stary soubor byl odebran od akce a smazan');
			}
			$this->flashMessage('Akce upravena');
		} else {
			$this->db->table('event')->insert($values);
			$this->flashMessage('Akce přidána');
		}
		$this->db->commit();
		$this->redirect('items');
	}

	public function bannerFormSubmitted(AppForm $form) {
		$values = (array) $form->values;
		$image = $values['imagefile'];
		unset($values['imagefile']);

		$bannerSelection = isset($this->params['id']) ? 
			$this->db->table('banner')->wherePrimary($this->params['id']) :
			FALSE;
		// @TODO make service
		$files = new UploadFileStorage($this->context->params['wwwDir'], 'images');

		if ($form->submitted->name == 'delete') {
			@$files->delete($bannerSelection->fetch()->imagefile);
			$bannerSelection->delete();
			$this->flashMessage('Banner byl smazan');
		} else {
			if ($image->isOk()) {
				// @TODO make service
				$values['imagefile'] = $files->save($image);
				if ($bannerSelection) {
					$oldFile = $bannerSelection->fetch()->imagefile;
					if (strlen($oldFile)) {
						@$files->delete($oldFile);
					}
				}
				$this->flashMessage('Nový obrázek byl uložen');
			} else {
				$this->flashMessage('Nový obrázek nebyl uložen (chyba nebo nebyl nahrán)');
			}
			if ($bannerSelection) {
				$bannerSelection->update($values);
				$this->flashMessage('Banner upraven');
			} else {
				$this->db->table('banner')->insert($values);
				$this->flashMessage('Banner přidán');
			}
		}
		$this->redirect('items');
	}

	public function jokeFormSubmitted(AppForm $form) {		
		$values = $form->values;
		$images = $this->jokeImages;
		$imageParams = $this->context->params['jokes'];
		$imageFile = $values->file;
		$values = array(
			'date_from' => $values->date_from,
			'text' => $values->text,
		);
		if ($imageFile->isOk()) {
			$values += array(
				'filename_image' => $images->save($imageFile, $imageParams['maxSizePx'], '_'),
				'filename_thumb' => $images->save($imageFile, $imageParams['maxSizeThumbPx'], 'n_'),
			);
		}
		if (isset($this->params['id'])) {
			$this->jokes->update($this->params['id'], $values);
			$this->flashMessage('Nový vtip byl nahrán a uložen');
		} else {
			$this->jokes->insert($values);
			$this->flashMessage('Vtip byl uložen');
		}
		$this->redirect('items');	
	}

}
