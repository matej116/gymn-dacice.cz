<?php


/**
 * 
 * class to wrap fulltext searching in databases
 * requires setting FULLTEXT keys to table $table on fields mentioned in $fieldWeight
 */
class FulltextSearcher extends Object {	

	/**
	 * @var Nette\Database\Connection
	 */
	private $db;

	private $minWordLength;

	private $table;

	private $fieldsWeights = array(
		'title' => 5,
		'text' => 1
	);

	private $where = '';

	/**
	 * @param $db \Nette\Database\Connection
	 * @param $table string table name
	 * @param $fieldsWeights array of field => weight as int
	 * @param $replaceDefaults boolean TRUE = replace defaults, FALSE = merge defaults
	 */
	public function __construct(Connection $db, $table='article', array $fieldsWeights=array()) {
		$this->db = $db;
		$this->table = $table;
		$this->fieldsWeights = array_merge($this->fieldsWeights, $fieldsWeights);
	}

	public function addWhere($condition) {
		if (!isset($this->where)) {
			$this->where = "$condition";
		} else {
			$this->where .= " AND $condition";
		}
	}

	public function getMinWordLength() {
		if (!isset($this->minWordLength)) {
			$row = $this->db->query("SHOW VARIABLES LIKE 'ft_min_word_len'")->fetch();
			$this->minWordLength = $row->Value;
		}
		return $this->minWordLength;
	}

	public function getTable() {
		return $this->table;
	}

	private static function escapeField($fieldName) {
		return str_replace('`', '``',$fieldName);   
	}

	public function getResults($q, $limitStart=0, $limitEnd=FALSE) {

		$table = '`'.self::escapeField($this->table).'`';

		$where = $this->where ? "$this->where AND " : '';
		$countQ = 0;
		if (strlen($q) >= $this->getMinWordLength()) {
			//use fulltext  

			$fieldNames = array_keys($this->fieldsWeights);
			$fieldNamesEscaped = array();
			foreach($fieldNames as $fieldName) {
				//escape field name
				$fieldNamesEscaped[] = self::escapeField($fieldName);
			}
			$fieldNames = '`'.join('`,`',$fieldNamesEscaped).'`';


			$sql = "SELECT * FROM $table WHERE $where MATCH($fieldNames) AGAINST (? IN BOOLEAN MODE) ORDER BY ";
			$countQ++;
			$sqls = array();
			foreach ($this->fieldsWeights as $field => $weight) {
				$weight = intval($weight);
				$escapedFieldName = self::escapeField($field);
				$sqls[] = "$weight * MATCH(`$escapedFieldName`) AGAINST (?)";
				$countQ++;
			}
			$sqls = join(' + ',$sqls);

			$sql .= "$sqls DESC";

		} elseif (strlen($q) > 0) {
			//use LIKE '% $q %', fallback when fulltext is not availible

			$sql = "SELECT * FROM $table WHERE $where (";

			// search words (these weirds character groups are word boundaries)
			$regexp = "REGEXP CONCAT('[[:<:]]', ?, '[[:>:]]')";
			$sqls = array();

			foreach($this->fieldsWeights as $field => $weight) {
				$sqls[] = '`'.self::escapeField($field)."` $regexp";
				$countQ++;
			}

			$sql .= join(' OR ',$sqls);
			$sql .= ')';

		} else {
			// for empty query return no results
			return array();
		}

		$limitStart = intval($limitStart);
		if ($limitStart) {
			$limitEnd = intval($limitEnd);
			if (!$limitEnd) {
				$limitEnd = PHP_INT_MAX;
			}
			$sql .= " LIMIT $limitStart, $limitEnd";
		}

		return call_user_func_array(array($this->db,'query'), array_merge(array($sql), array_fill(1, $countQ, $q)));
	}

}