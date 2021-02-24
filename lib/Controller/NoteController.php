<?php

namespace OCA\NotesTutorial\Controller;

use OCA\NotesTutorial\AppInfo\Application;
use OCA\NotesTutorial\Service\NoteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class NoteController extends Controller {
	/** @var NoteService */
	private $service;

	/** @var string */
	private $userId;

	use Errors;

	public function __construct(IRequest $request,
								NoteService $service,
								$userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->service = $service;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(): DataResponse {
		return new DataResponse($this->service->findAll());
	}

	/**
	 * @NoAdminRequired
	 */
	public function show(int $id): DataResponse {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->find($id);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function create(array $list): DataResponse {
		return new DataResponse($this->service->create($list));
	}

	/**
	 * @NoAdminRequired
	 * 
	 * @param array $list
	 */
	public function update(array $list): DataResponse {
		return new DataResponse($this->service->update($list));
	}

	/**
	 * @NoAdminRequired
	 */
	public function destroy(string $id): DataResponse {
		return new DataResponse($this->service->delete($id));
	}
}
