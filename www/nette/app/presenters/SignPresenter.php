<?php

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{


	/**
	 * Sign-in form factory.
	 * @return AppForm
	 */
	protected function createComponentSignInForm()
	{
		$form = new AppForm;
		$form->addText('username', 'Uživatelské jméno:')
			->setRequired('Zadejte uživatelské jméno');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Zadejte Vaše heslo.');

		$form->addCheckbox('remember', 'Neodhlašovat');

		$form->addSubmit('send', 'Přihlásit se');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}


	public function signInFormSucceeded($form)
	{
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Admin:');

		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Odhlášeno.');
		$this->redirect('in');
	}

}
