<?php

namespace Craft;

class Coerce_PasswordsRecord extends BaseRecord {

	public function getTableName() {
		return 'coerce_passwords';
	}

	protected function defineAttributes() {
		return [
			'user' => AttributeType::Mixed,
			'passwords' => [AttributeType::String, 'maxLength' => 5000],
		];
	}

}