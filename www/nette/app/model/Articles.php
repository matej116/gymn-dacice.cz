<?php

class Articles extends Object {
	
	protected $db;

	protected $paginator;

	public function __construct(Connection $db, $perPage = 10) {
		$this->db = $db;
		$this->paginator = new Paginator;
		$this->paginator->setItemsPerPage($perPage);
	}

	protected function table() {
		return $this->db->table('article');
	}

	/**
	 * @return array(Traversable of TableRow, bool - is there next page?)
	 */
	public function getArticles($menu = NULL, $page = 1) {
		$selection = $this->table();
		if ($menu !== NULL) {
			$selection->where('menu_id', $menu);
		}
		$paginator = $this->paginator;
		$paginator->setPage($page);
		$selection
			->limit($paginator->getLength() + 1, $paginator->getOffset())
			->order('date DESC');
		$rows = iterator_to_array($selection);
		if (count($rows) > $itemsPerPage = $paginator->getItemsPerPage()) {
			return array(array_slice($rows, 0, $itemsPerPage), TRUE);
		} else {
			return array($rows, FALSE);
		}
	}

	public function countArticles($menu = NULL) {
		$selection = $this->table();
		if ($menu !== NULL) {
			$selection->where('menu_id', $menu);
		}
		return $selection->count('*');
	}

	public function getArticle($id) {
		return $this->table()->wherePrimary($id)->fetch();
	}

}
