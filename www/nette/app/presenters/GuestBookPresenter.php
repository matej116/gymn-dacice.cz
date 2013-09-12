<?php

class GuestBookPresenter extends BasePresenter {
	
	/**
	 * @var CaptchaControl
	 */
	private $captchaControl;

	private $guestBook;

	public function injectCaptcha(CaptchaControl $captcha) {
		$this->captchaControl = $captcha;
	}

	public function injectGuestBook(GuestBook $book) {
		$this->guestBook = $book;
	}
	
	public function renderDefault() {
		$items = $this->guestBook->getItems();
		$template = $this->template;
		$template->items = $items;
	}

	public function renderAdd($replyTo = NULL) {
		if ($replyTo) {
			$this->template->replyToItem = $this->guestBook->getItem($replyTo);
		}
	}

	public function createComponentGuestBookForm() {
		$form = new AppForm;

		$form->elementPrototype->addClass('ajax');

		$form->addText('author', 'Jméno:')
			->addRule(Form::FILLED, 'Vyplňte své jméno')
			->controlPrototype->placeholder = 'Sem napište své jméno...';
		$form->addTextArea('text', 'Text:', NULL, 5)
			->addRule(Form::FILLED, 'Vyplňte text')
			->controlPrototype->placeholder = 'Sem napište dotaz...';
		$captcha = $this->captchaControl;
		if ($parent = $captcha->getParent()) {
			// @TODO on AJAX request it failed here (CaptchaControl has already a parent), this fixed it, but what is
			// happening on AJAX request causing this?
			$parent->removeComponent($captcha);
		}
		$form['captcha'] = $captcha;
		$form->addProtection("Chyba při odeslání formuláře (CSRF ochrana)");
		$form->addSubmit('submit', 'Odeslat')
			->controlPrototype->addClass('link-block with-icon icon-send');

		$form->onSuccess[] = $this->guestBookFormSubmitted;

		return $form;
	}

	public function guestBookFormSubmitted(Form $form) {
		$formValues = $form->values;
		$inserted = $this->guestBook->addItem(
			$formValues->author, 
			$formValues->text, 
			@$this->params['answerTo']
		);
		if ($inserted) {
			$this->flashMessage('Váš zápis byl přidán do návštěvní knihy');
		} else {
			$this->flashMessage('Nepodařilo se přidat zápis do návštěvní knihy. Můžete to zkusit později.', 'error');
		}
		$this->redirect('default'); // avoid form resubmission and redirect back
	}

}
