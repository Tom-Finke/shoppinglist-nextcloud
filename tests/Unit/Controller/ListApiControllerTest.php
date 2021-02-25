<?php

namespace OCA\ShoppingList\Tests\Unit\Controller;

use OCA\ShoppingList\Controller\ListApiController;

class ListApiControllerTest extends ListControllerTest {
	public function setUp(): void {
		parent::setUp();
		$this->controller = new ListApiController($this->request, $this->service, $this->userId);
	}
}
