<?php

namespace OCA\ShoppingList\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http;
use OCP\IRequest;

use OCA\ShoppingList\Service\ListNotFound;
use OCA\ShoppingList\Service\ListService;
use OCA\ShoppingList\Controller\ListController;

class ListControllerTest extends TestCase {
	protected $controller;
	protected $service;
	protected $userId = 'john';
	protected $request;

	public function setUp(): void {
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->service = $this->getMockBuilder(ListService::class)
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new ListController($this->request, $this->service, $this->userId);
	}

	public function testUpdate() {
		$list = ["id"=> 1, 
		"author" => "john", 
		"title" => "note", 
		"color" => "#000000", 
		"items" => []];
		$this->service->expects($this->once())
			->method('update')
			->with($this->equalTo($list))
			->will($this->returnValue(['status'=> "success"]));

		$result = $this->controller->update($list);

		$this->assertEquals(['status'=> "success"], $result);
	}


	public function testUpdateNotFound() {
		$list = ["id"=> 1, 
		"author" => "john", 
		"title" => "note", 
		"color" => "#000000", 
		"items" => []];
		// test the correct status code if no note is found
		$this->service->expects($this->once())
			->method('update')
			->will($this->throwException(new ListNotFound()));

		$result = $this->controller->update($list);

		$this->assertEquals(['status'=> "fail"], $result);
	}
}
