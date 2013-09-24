<?php 

class Jokes extends Object {
	
	private $db;

	public function __construct(Connection $db) {
		$this->db = $db;
	}

	protected function getLimitedTable() {
		return $this->db->table('joke')->where('date_from <= CURDATE()');
	}

	public function insert($data) {
		return $this->getLimitedTable()->insert($data);
	}

	public function update($id, $data) {
		return $this->getJoke($id)->update($data);
	}

	public function getJoke($id) {
		return $this->getLimitedTable()->where('id', $id)->fetch();
	}

	public function getLatest() {
		return $this->getLimitedTable()
			->order('date_from DESC')
			->limit(1)
			->fetch();
	}

	public function getPrevious($date) {
		return $this->getLimitedTable()
			->where('date_from < ', $date)
			->order('date_from DESC')
			->limit(1)
			->fetch();
	}

	public function getNext($date) {
		return $this->getLimitedTable()
			->where('date_from > ', $date)
			->order('date_from ASC')
			->limit(1)
			->fetch();
	}

}