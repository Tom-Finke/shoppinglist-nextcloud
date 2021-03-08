<?php

namespace OCA\ShoppingList\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;

use OCA\ShoppingList\Db\ListMapper;

class ListService {

	/** @var ListMapper */
	private $mapper;
	private $root;
	private $user_id;

	public function __construct(?string $UserId, IRootFolder $root, ListMapper $mapper) {
		$this->user_id = $UserId;
		$this->root = $root;
		$this->mapper = $mapper;
	}

	public function findAll(): array {
		$user_folder = $this->getFolderForUser();
		$lists = [];
		foreach ($user_folder->getDirectoryListing() as $node) {
			$fileinfo = pathinfo($node->getName());
			if ($fileinfo['extension'] == "json") {
				$lists[] = $this->loadList($node);
			}
		}
		return $lists;
	}

	private function handleException(Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new ListNotFound($e->getMessage());
		} else {
			throw $e;
		}
	}


	public function findFile($id) {
		$user_folder = $this->getFolderForUser();
		foreach ($user_folder->getDirectoryListing() as $node) {
			$list = $this->loadList($node);
			if ($list['id'] == $id) {
				return $node;
			}
		}
		return null;
	}
	public function find($id) {
		$lists = $this->findAll();
		return $lists;
		$ids = array_column($lists, 'id');
		$index = array_search($id, $ids);
		return $lists[$index];
	}

	public function create($list) {
		try {
			$folder = $this->getFolderForUser();
			$index = count($folder->getDirectoryListing()) + 1;
			$file = $folder->newFile("new_list_" . $index . ".json");
			$list = $this->verifyList($list);
			$file->putContent(json_encode($list));
			return ["status" => "success"];
		} catch (Exception $e) {
			$this->handleException($e);
			return ['status' => "fail"];
		}
	}

	public function update($list) {
		try {
			$file = $this->findFile($list["id"]);
			$file_content = $this->loadList($file);
			$newItemIds = array_column($list["items"], 'id');
			foreach ($file_content["items"] as &$item) {
				if (in_array($item["id"], $newItemIds)) {
					#If the Item exists both in the file and the new user data
					$newItemIndex = array_search($item["id"], $newItemIds);
					$filedate = new \DateTime($item["editedDate"]);
					$listdate = new \DateTime($list["items"][$newItemIndex]["editedDate"]);

					if (($filedate) > ($listdate)) {
						# If the item is newer in the file, the item in the json has to be overwritten

						$list["items"][$newItemIndex] = $item;
					}
				} else {
					#If the item from the file is not yet in the data sent by the user, it has to be added
					$list["items"][] = $item;
				}
			}
			$list = $this->verifyList($list);
			$file->putContent(json_encode($list));
			return ['status' => "success"];
		} catch (Exception $e) {
			$this->handleException($e);
			return ['status' => "fail"];
		}
	}

	public function delete($id) {
		try {
			$file = $this->findFile($id);
			$file->delete();
		} catch (Exception $e) {
			$this->handleException($e);
			return ['status' => "fail"];
		}
	}

	
	public function loadList($file) {
		$list = json_decode($file->getContent(), true);
		$list_copy = new \ArrayObject($list);
		$list = $this->verifyList($list);
		if (!$list === $list_copy) {
			$this->update($list);
		}
		return $list;
	}
	/**
	 * @return Folder
	 */
	public function getFolderForUser() {
		$path = '/' . $this->user_id . '/files/' . $this->getUserFolderPath();
		$path = str_replace('//', '/', $path);

		return $this->getOrCreateFolder($path);
	}
	/**
	 * @return string
	 */
	public function getUserFolderPath() {
		// $path = $this->config->getUserValue($this->user_id, 'shoppinglist', 'folder');
		$path = "Shoppinglists";
		if (!$path) {
			$path = '/' . $this->il10n->t('Lists');
		}

		return $path;
	}
	/**
	 * Finds a folder and creates it if non-existent
	 * @param string $path path to the folder
	 *
	 * @return Folder
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	private function getOrCreateFolder($path) {
		if ($this->root->nodeExists($path)) {
			$folder = $this->root->get($path);
		} else {
			$folder = $this->root->newFolder($path);
		}
		return $folder;
	}

	/**
	 * @param array $list the list to be checked
	 * Takes a list. Performs a syntax check and returns a "fixed" list
	 *
	 */
	public function verifyList($list) {
		$list = $this->validOrDefault($list, ["title" => "New List", "author" => 'unknown']);
		$list["editedDate"] = $this->validISODate($list["editedDate"]);
		$list["createdDate"] = $this->validISODate($list["createdDate"]);
		$list["id"] = $this->validUUID($list["id"]);

		foreach ($list["items"] as &$item) {
			if (!is_array($item)) {
				$item = [];
			}
			$item["editedDate"] = $this->validISODate($item["editedDate"]);
			$item["createdDate"] = $this->validISODate($item["createdDate"]);
			$item["id"] = $this->validUUID($item["id"]);
			$item = $this->validOrDefault($item, ["name" => "New Item", "author" => 'unknown']);
		}
		return $list;
	}


	/**
	 * @param string $value the iso string to be checked
	 * Tries to Create a Date from the given string and return it as an ISO String.
	 * If not possible, it will return the current Datetime as an iso string
	 *
	 */
	public function validISODate($value) {
		try {
			$dateTime = new \DateTime($value);
		} catch (Exception $e) {
			$dateTime = new \DateTime();
		}
		return $dateTime->format(\DateTime::ISO8601);
	}
	/**
	 * @param string $value the uuid string to be checked
	 * Returns the value if it is a valid uuid v4.
	 * Otherwise creates and returns a valid uuid v4
	 *
	 */
	public function validUUID($value) {
		try {
			if (!preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/', $value)) {
				return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
			} else {
				return $value;
			}
		} catch (Exception $e) {
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
		}
	}

	/**
	 * @param string $value the uuid string to be checked
	 * Returns the value if it is a valid hex color string.
	 * Otherwise returns the default color
	 *
	 */
	public function validHexColor($value) {
		$defaultColor = "#6ea68f";
		try {
			if (!preg_match('/^#[0-9a-f]{6}$/', $value)) {
				return $defaultColor;
			} else {
				return $value;
			}
		} catch (Exception $e) {
			return $defaultColor;
		}
	}

	/**
	 * @param array $array_to_check the uuid string to be checked
	 * @param array $keys			the array of key and default-value pairs to check
	 * For everry key in $keys checks wether the key already exists in the array_to_check
	 * If not, it will set the default value for that key
	 *
	 */
	public function validOrDefault(array $array_to_check, array $keys) {
		foreach ($keys as $key => $default) {
			if (!array_key_exists($key, $array_to_check)) {
				$array_to_check[$key] = $default;
			}
		}
		return $array_to_check;
	}
}
