<?php

namespace OCA\ShoppingList\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'shoppinglist';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
