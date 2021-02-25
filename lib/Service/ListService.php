<?php

namespace OCA\ShoppingList\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Files\IRootFolder;
use OCP\Files\FileInfo;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Files\File;
use OCP\Files\Folder;

use OCA\ShoppingList\Db\ShoppinhL;
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
		$recipes = array(); 
		foreach ($user_folder->getDirectoryListing() as $node) {
			$fileinfo = pathinfo($node->getName());
			if($fileinfo['extension'] == "json")
			{	
				$recipes[] =  $this->loadRecipe($node);
			} 
		}
		return $recipes;
	}

	private function handleException(Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new ListNotFound($e->getMessage());
		} else {
			throw $e;
		}
	}


	public function findFile($id){
		$user_folder = $this->getFolderForUser();
		foreach ($user_folder->getDirectoryListing() as $node) {
			$recipe = $this->loadRecipe($node);
			if($recipe['id'] == $id)
			{
				return $node;
			} 
		}
		return null;
	}
	public function find($id) {
		$recipes = $this->findAll();
		return $recipes;
		$ids = array_column($recipes, 'id');
		$index = array_search($id, $ids);
		return $recipes[$index];
	}

	public function create($list) {
		try{
			$folder = $this->getFolderForUser();
			$index = count($folder->getDirectoryListing()) + 1;
			$file = $folder->newFile("new_recipe_" . $index . ".json");
			$file->putContent(json_encode($list));
			return ["status"=> "success"];
		}
		catch (Exception $e) {
			$this->handleException($e);
			return ['status'=> "fail"];
		}
	}

	public function update($json) {
		try {
			$file = $this->findFile($json["id"]);
			$file->putContent(json_encode($json));
		} catch (Exception $e) {
			$this->handleException($e);
			return ['status'=> "fail"];
		}
	}

	public function delete($id) {
		$file = $this->findFile($id);
		$file->delete();
	}

	
	public function loadRecipe($file){
		$json = json_decode($file->getContent(), true);
		$update = false;
		foreach ($json["items"] as &$item){
			if(!isset($item["id"])){
				$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
				$item["id"] = $uuid;
				$update = true;
			}
		}
		if($update){
			$this->update($json);
		}

		return $json;
	}
	/**
     * @return Folder
     */
	public function getFolderForUser()
    {
        $path = '/' . $this->user_id . '/files/' . $this->getUserFolderPath();
        $path = str_replace('//', '/', $path);

        return $this->getOrCreateFolder($path);
    }
	/**
     * @return string
     */
    public function getUserFolderPath()
    {
        // $path = $this->config->getUserValue($this->user_id, 'shoppinglist', 'folder');
		$path = "Shoppinglists";
        if (!$path) {
            $path = '/' . $this->il10n->t('Recipes');
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
    private function getOrCreateFolder($path)
    {
        if ($this->root->nodeExists($path)) {
            $folder = $this->root->get($path);
        } else {
            $folder = $this->root->newFolder($path);
        }
        return $folder;
    }
}
