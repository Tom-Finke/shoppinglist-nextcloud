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
			$file->putContent(json_encode($list));
			return ["status" => "success"];
		} catch (Exception $e) {
			$this->handleException($e);
			return ['status' => "fail"];
		}
	}

	public function update($json) {
		
		try {

			$file = $this->findFile($json["id"]);
			$file_content = $this->loadList($file);
			$newItemIds = array_column($json["items"], 'id');
			foreach($file_content["items"] as &$item){

				if(in_array($item["id"], $newItemIds)){
					#If the Item exists both in the file and the new user data
					$newItemIndex = array_search($item["id"], $newItemIds);
					$filedate = new \DateTime($item["editedDate"]);
					$jsondate = new \DateTime($json["items"][$newItemIndex]["editedDate"]);

					if(($filedate)>($jsondate)){
						# If the item is newer in the file, the item in the json has to be overwritten

						$json["items"][$newItemIndex] = $item;
					}
				}
				else{
					#If the item from the file is not yet in the data sent by the user, it has to be added
					$json["items"][] = $item;
				}
			}
			$file->putContent(json_encode($json));
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
		$json = json_decode($file->getContent(), true);
		$update = false;
		foreach ($json["items"] as &$item) {
			if (!isset($item["id"])) {
				$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
				$item["id"] = $uuid;
				$update = true;
			}
		}
		if ($update) {
			$this->update($json);
		}

		return $json;
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
}
