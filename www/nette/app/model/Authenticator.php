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

		if ($row->password !== $this->calculateHash($password, $row->password)) {
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
	public static function calculateHash($password, $salt = NULL)
	{
		if ($password === Strings::upper($password)) { // perhaps caps lock is on
			$password = Strings::lower($password);
		}
		return crypt($password, ($tmp=$salt) ? $tmp : '$2a$07$' . Strings::random(22));
	}

}
