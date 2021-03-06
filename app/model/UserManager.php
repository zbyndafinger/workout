<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'users',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'email',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_ROLE = 'roles',
		COLUMN_FCB_ID = 'fcbId',
		COLUMN_CREATED = 'created',
		COLUMN_ACCESS_TOKEN = 'accessToken',
		COLUMN_LAST_LOGIN = 'lastLogin',
		COLUMN_FULLNAME = 'fullName',
		COLUMN_EMAIL = 'email';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();
		
		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		$this->updateLastLogin($row[self::COLUMN_ID]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password)
	{
		try {
			$this->database->table(self::TABLE_NAME)->insert(array(
				self::COLUMN_NAME => $username,
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}

	public function findByFacebookId($fcbId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_FCB_ID, $fcbId)->fetch();
	}

	public function registerFromFacebook($fcbId, $userInfo)
	{
		return $this->database->table(self::TABLE_NAME)->insert(array(
					self::COLUMN_ROLE => 'user',
					self::COLUMN_FCB_ID => $fcbId,
					self::COLUMN_CREATED => date('Y-m-d H:i:s'),
					self::COLUMN_FULLNAME => $userInfo->name,
					self::COLUMN_ID => $userInfo->email,
				));
	}

	public function updateFacebookAccessToken($fcbId, $accessToken)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_FCB_ID, $fcbId)->update(array(
				self::COLUMN_ACCESS_TOKEN => $accessToken
			));
	}

	public function updateLastLoginFcb($userId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $userId)->update(array(
				self::COLUMN_LAST_LOGIN => date('Y-m-d H:i:s')
			));
	}

	public function updateLastLogin($userId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $userId)->update(array(
				self::COLUMN_LAST_LOGIN => date('Y-m-d H:i:s')
			));
	}


}



class DuplicateNameException extends \Exception
{}


