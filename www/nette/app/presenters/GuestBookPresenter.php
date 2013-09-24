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

		$input = $form->addText('author', 'Jméno:')
			->addRule(Form::FILLED, 'Vyplňte své jméno')
			->getControlPrototype();
		$input->placeholder = 'Sem napište své jméno...';
		$input->addClass('big');

		$input = $form->addTextArea('text', 'Text:', NULL, 5)
			->addRule(Form::FILLED, 'Vyplňte text')
			->getControlPrototype();
		$input->placeholder = 'Sem napište dotaz...';
		$input->addClass('big');

		$captcha = $this->captchaControl;
		if ($parent = $captcha->getParent()) {
			// @TODO on AJAX request it failed here (CaptchaControl has already a parent), this fixed it, but what is
			// happening on AJAX request causing this?
			$parent->removeComponent($captcha);
		}
		$form['captcha'] = $captcha;
		$captcha->controlPrototype->addClass('big');

		$form->addSubmit('submit', 'Odeslat')
			->controlPrototype->addClass('link-block with-icon icon-send');

		$form->addProtection("Chyba při odeslání formuláře (CSRF ochrana)");

		$form->onSuccess[] = $this->guestBookFormSubmitted;

		return $form;
	}

	public function guestBookFormSubmitted(Form $form) {
		$formValues = $form->values;
		$inserted = $this->guestBook->addItem(
			$formValues->author, 
			$formValues->text, 
			isset($this->params['replyTo']) ? $this->params['replyTo'] : NULL
		);
		if ($inserted) {
			$this->flashMessage('Váš zápis byl přidán do návštěvní knihy');
		} else {
			$this->flashMessage('Nepodařilo se přidat zápis do návštěvní knihy. Můžete to zkusit později.', 'error');
		}
		$this->redirect('default'); // avoid form resubmission and redirect back
	}

}
