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
		return isset($menu[$id]) ? $menu[$id]->title : NULL;
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

	public function getAlerts() {
		return $this->db->table('alert');
	}

	public function getLastGOneVideos() {
		return $this->db->table('g_one_video')
			->order('time DESC, id DESC')
			->limit(3);
	}

}
