<?php

/**
 * Users authenticator.
 */
class Authenticator extends Object implements IAuthenticator
{
	/** @var Connection */
	private $database;


	public function __construct(Connection $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Identity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->database->table('user')->where('nickname', $username)->fetch();

		if (!$row) {
			throw new AuthenticationException('Uživatelské jméno nenalezeno.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new AuthenticationException('Špatné heslo.', self::INVALID_CREDENTIAL);
		}

		$arr = $row->toArray();
		unset($arr['password']);
		return new Identity($row->id, $row->role, $arr);
	}


	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public static function calculateHash($password)
	{
		if ($password === Strings::upper($password)) { // perhaps caps lock is on
			$password = Strings::lower($password);
		}
		static $salt = 'omesillystringfore2uDLvp1Ii2e./U9C8';
		return md5($salt . str_repeat($password, 5));
	}

}
