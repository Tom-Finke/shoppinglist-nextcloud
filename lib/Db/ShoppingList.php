<?php

namespace OCA\ShoppingList\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class ShoppingList extends Entity implements JsonSerializable {
	protected $content;

	public function jsonSerialize(): array {
		return [
			'content' => $this
		];
	}
}
