<?php
namespace Craft;

class CoercePlugin extends BasePlugin
{

	public function getName()
	{
		return Craft::t('Coerce');
	}

	public function getVersion()
	{
		return '1.0.0';
	}

	public function getSchemaVersion()
	{
		return '1.0.0';
	}

	public function getDeveloper()
	{
		return 'Technical Studio';
	}

	public function getDeveloperUrl()
	{
		return 'https://technicalstudio.co.uk';
	}
	
	public function init() {

    	craft()->on('users.beforeSetPassword', [$this, 'onBeforeSetPassword']);
    	craft()->on('users.onSetPassword', [$this, 'onSetPassword']);

    	craft()->on('userSession.onBeforeLogin', [$this, 'onLogin']);
	}

	protected function defineSettings() {
		return [
			'invalidWords' => [AttributeType::Mixed, 'default' => ['123456', 'password', '12345', '12345678', 'football', 'qwerty', '1234567890', '1234567', 'princess', '1234', 'login', 'welcome', 'solo', 'abc1234', 'login', 'welcome', 'solo', 'abc123', 'admin', 'admin', '121212', 'flower', 'passw0rd', 'dragon', 'sunshine', 'master', 'hottie', 'loveme', 'zaq12aq1', 'password1']]
		];
	}

	public function getSettingsHtml() {
    	return craft()->templates->render('coerce/settings', array(
        	'settings' => $this->getSettings()
    	));
	}

	public function onBeforeSetPassword(Event $event) {
		//perform validation on the password.
		if(mb_strlen($event->params['password']) <= 10) {
			$user->addErrors([
				'newPassword' => Craft::t('This password must be greater than 10 characters long'),
			]);
			craft()->userSession->setFlash('error', "This password must be greater than 10 characters long");
		}

		$passwordIsValid = array_reduce($this->getSettings()->invalidWords, function($valid, $password) use($event){
			if(!$valid) {
				return $valid = false;
			}
			$submittedPassword = $event->params['password'];
			if(!preg_match("/^((?!$password).)*$/", $submittedPassword)) {
				return $used = false;

			}
			return true;

		}, true);

		

		$user = $event->params['user'];
			$usersPreviousPasswords = craft()->db->createCommand()
				->select('*')
				->from('coerce_passwords')
				->where('user = :userid', [':userid' => $user->id])
				->queryRow();

		if(!$usersPreviousPasswords) {
			return;
		}


		//We have previously set passwords, lets ensure they are not changing it to one of the previous ones which exists in our database table

                                         
		$passwords = json_decode($usersPreviousPasswords['passwords'])->passwords;

		$passwordHasBeenUsed = array_reduce($passwords, function($used, $password) use($event){
			if($used) {
				return $used;
			}

			return $used = craft()->security->checkPassword($event->params['password'], $password);
		}, false);
		


		if($passwordHasBeenUsed) {
			$event->performAction = false;
			$user->addErrors(array(
				'newPassword' => Craft::t('This password has been used in the previous 13 passwords. Try a unique one.'),
			));
			craft()->userSession->setFlash('error', "This password has been used in the previous 13 passwords. Try a unique one.");
			return;
		}
		
    	return;
   
	}

	public function onLogin(Event $event) {
		//Fire the event to ensure we don't need to update a password

		$user = craft()->db->createCommand()
			->select('*')
			->from('users')
			->where('username = :username', [':username' => $event->params['username']])
			->queryRow();

		$previousDate = (new DateTime)->sub(date_interval_create_from_date_string('30 days'));

		if($previousDate >= $user['lastPasswordChangeDate']) {
			$event->performAction = false;
			craft()->db->createCommand()->update('users', [
    			'passwordResetRequired' => 1,
			], 'username = :username', [':username' => $user['username']]);
		}



	}

	public function onSetPassword(Event $event) {

		$user = $event->params['user'];
	
		// Add the hash to the password array on the user, create the record if it doesn't exist

		$passwordModel = new Coerce_PasswordsModel();
		$usersPreviousPasswords = craft()->db->createCommand()
		->select('*')
		->from('coerce_passwords')
		->where('user = :userid', [':userid' => $user->id])
		->queryRow();

		if(!$usersPreviousPasswords) {
			//create an entry in the passwords table.
			return craft()->db->createCommand()
			->insert('coerce_passwords', [
				'user' => $user->id,
				'passwords' => json_encode(['passwords' => [$user->password]], JSON_UNESCAPED_SLASHES),
			]);

		}

		//Pull out the password set and push our new password into the array, if we are 13 or more entries in – let's add our password to the start of the array
		$passwords = json_decode($usersPreviousPasswords['passwords'])->passwords;

		if(count($passwords) >= 13) {
			array_shift($passwords);		
		}
		
		array_unshift($passwords, $user->password);

		//reinsert our json encoded password set

		craft()->db->createCommand()->update('coerce_passwords', [
    		'passwords' => json_encode(['passwords' => $passwords], JSON_UNESCAPED_SLASHES),
		], 'user = :userid', [':userid' => $user->id]);

	}

	public function prepSettings($settings) {
		if(array_key_exists('invalidWords', $settings)) {
			$settings['invalidWords'] = explode(', ', $settings['invalidWords']);
		}

		return $settings;
	}

}