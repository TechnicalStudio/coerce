<?php

namespace Craft;

class Coerce_PasswordsModel extends BaseModel {

	protected function defineAttributes() {
		return [
			'user' => AttributeType::Mixed,
			'passwords' => [AttributeType::String, 'maxLength' => 5000],
		];
	}
}