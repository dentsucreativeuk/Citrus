<?php
namespace whitespace\citrus\helpers;

use whitespace\citrus\Citrus;

use Craft;

class UriHelper
{
	public $path;
	public $locale;

	public function __construct($path, $locale) {
		$this->path = $path;
		$this->locale = $locale;
	}
}
