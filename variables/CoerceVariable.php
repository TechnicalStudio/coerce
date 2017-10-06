<?php

namespace Craft;

Class CoerceVariable {
	public function getCoerceSettings() {
		return implode(', ', craft()->coerce_coerceSettings->getSettings()->invalidWords);
	}
}