<?php

class Foods extends Object {

	protected $db;

	protected $sjdaciceDbProvider;

	public function __construct(Connection $db, ServiceProvider $sjdaciceDbProvider) {
		$this->db = $db;
		$this->sjdaciceDbProvider = $sjdaciceDbProvider;
	}

	protected function getFoodsSelection($futureDays) {
		return $this->db->table('food')
			->where('date >= CURDATE()')
			->order('date ASC')
			->limit($futureDays);
	}

	protected function refreshFromSjDacice() {
		$sjDaciceDb = $this->sjdaciceDbProvider->getService();
		if ($sjDaciceDb === NULL) {
			// connection to sjdacice database cannot be established
			return FALSE;
		} 
		$foods = $sjDaciceDb
			->query("SELECT datum AS  `date`, polevka.nazev as soup, jidlo1.nazev AS main1, jidlo2.nazev AS main2
					FROM jidelnicek
					INNER JOIN polevka ON polevka.id = jidelnicek.idpolevka
					INNER JOIN jidlo AS jidlo1 ON jidlo1.id = jidelnicek.idjidlo1
					INNER JOIN jidlo AS jidlo2 ON jidlo2.id = jidelnicek.idjidlo2
					WHERE datum >= NOW()
					ORDER BY `date` ASC");
		$db = $this->db;
		$db->beginTransaction();
		$foodTable = $db->table('food');
		$foodTable->where('date < CURDATE()')->delete();
		foreach ($foods as $food) {
			$foodTable->insert((array) $food);
		}
		$db->commit();
		return TRUE;
	}

	public function getFutureFoods($futureDays = 10) {
		$selection = $this->getFoodsSelection($futureDays);
		if (count($selection) != $futureDays) {
			if ($this->refreshFromSjDacice()) {
				// foods refreshed, try again
				$selection = $this->getFoodsSelection($futureDays);
			}
		}
		return $selection;
	}

}
