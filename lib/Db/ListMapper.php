<?php

namespace OCA\ShoppingList\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ListMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'shoppinglist', ShoppingList::class);
	}

	/**
	 * @param int $id
	 * @param string $userId
	 * @return Entity|ShoppingList
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $userId): ShoppingList {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shoppinglist')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntity($qb);
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function findAll(string $userId): array {
		/* @var $qb IQueryBuilder */
		$user_folder = $this->getFolderForUser();
		return $user_folder->getDirectoryListing();
		// $qb = $this->db->getQueryBuilder();
		// $qb->select('*')
		// 	->from('shoppinglist')
		// 	->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		// return $this->findEntities($qb);
	}
}
