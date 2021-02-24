<?php

namespace OCA\NotesTutorial\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Note extends Entity implements JsonSerializable {
	protected $content;

	public function jsonSerialize(): array {
		return [
			'content' => $this
		];
	}
}
