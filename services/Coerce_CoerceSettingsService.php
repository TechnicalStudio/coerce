<?php

namespace Craft;

class Coerce_CoerceSettingsService extends BaseApplicationComponent {

	public function getSettings()
	{
	    $plugin = craft()->plugins->getPlugin('coerce');
		$settings = $plugin->getSettings();
    	return $settings;
	}
}