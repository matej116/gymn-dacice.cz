<?php

class MenuManager extends Object {
	
	private $db;

	/**
	 * @var TableSelection
	 */
	private $mainMenu;

	public function __construct(Connection $db) {
		$this->db = $db;
	}

	public function getMainMenu() {
		if (!isset($this->mainMenu)) {
			$this->mainMenu = $this->db->table('menu')->order('order');
		}
		return $this->mainMenu;
	}

	public function getMenuTitle($id) {
		$menu = $this->getMainMenu();
		return $menu[$id]->title;
	}

	public function getBanners() {
		return $this->db->table('banner')->order('order');
	}

	public function getEvents($onlyFuture = TRUE) {
		$selection = $this->db->table('event');
		if ($onlyFuture) {
			$selection->where('date >= CURDATE()');
		}
		return $selection->order('date ASC');
	}

}