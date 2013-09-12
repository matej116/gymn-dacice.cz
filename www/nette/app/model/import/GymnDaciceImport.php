<?php

function l($s) {
	echo "$s\n";
}

class GymnDaciceImport extends Object {
	
	/** @var Connection */
	private $dbLocal, $dbRemote;

	private $imported = array();

	/** @var array of array of class names */
	private $classes;

	private $graduatesFile;

	public function __construct(Connection $remote, Connection $local, array $classes, $graduatesFile) {
		$this->dbRemote = $remote;
		$this->dbLocal = $local;
		$this->classes = $classes;
		$this->graduatesFile = $graduatesFile;
	}

	private function insertFile($filename, $title = NULL) {
		if (strlen($filename) === 0) {
			return NULL;
		}
		$data = array(
			'filename' => $filename,
			'title' => $title,
		);
		$this->dbLocal->query('INSERT INTO file ? ON DUPLICATE KEY UPDATE ?', $data, $data);
		return $this->dbLocal->table('file')
			->select('id')
			->where('filename', $filename)
			->fetch()
			->id;
	}

	private function importTable($tableFrom, $tableTo, array $columns, $where = NULL) {
		//return;
		$localTable = $this->dbLocal->table($tableTo);
		$msg = "Importing '$tableFrom' to '$tableTo'" . @($where ? " WHERE $where" : '');
		if (!isset($this->imported[$tableTo])) {
			l("$msg, emptying '$tableTo'");
			$localTable->delete();
			$this->imported[$tableTo] = 1;
		} else {
			l($msg);
		}		
		$remoteTable = $this->dbRemote->table($tableFrom);
		if ($where) {
			$remoteTable->where($where);
		}
		foreach ($remoteTable as $row) {
			$insert = array();
			foreach ($columns as $remoteColumn => $localColumn) {
				$value = $row->$remoteColumn;
				if (is_array($localColumn)) {
					list($name, $type) = $localColumn;
					switch ($type) {
						case 'time':
							$value = DateTime53::from($value);
							break;						
						case 'file':
							$title = isset($localColumn[2]) ? $row[$localColumn[2]] : NULL;
							$value = $this->insertFile($value, $title);
							break;
						default:
							throw new InvalidArgumentException("Type '$localColumn[type]' is not known");
					}
					$insert[$name] = $value;
				} else {
					$insert[$localColumn] = $value;
				}
			}
			try {
				$localTable->insert($insert);
			} catch (PDOException $ex) {
				// ignore
			}
		}
	}

	public function import() {

		$db = $this->dbLocal;

		$db->beginTransaction();

		// disable foreign key check
		// tables needs to be emptied before inserting new rows
		$db->query('SET foreign_key_checks = 0');


		$this->importTable('menu', 'menu', array(
			'id' => 'id',
			'poradi' => 'order',
			'menu' => 'title',
		));

		$this->importTable('clanky', 'article', array(
			'id' => 'id',
			'nadpis' => 'title',
			'clanek' => 'text',
			'datum' => array('date', 'time'),
			'menu' => 'menu_id',
		));

		$this->importTable('kalendar', 'event', array(
			'nazev' => 'title',
			'text' => 'text',
			'od' => array('date', 'time'),
			'do' => array('date_to', 'time'),
		));

		l('Correcting dates in articles and events (date_to == date => date_to == NULL)');
		$this->dbLocal->table('article')->where('date = date_to')->update(array(
			'date_to' => NULL,
		));
		$this->dbLocal->table('event')->where('date = date_to')->update(array(
			'date_to' => NULL,
		));

		// delete all from table `file`
		l('emptying table `file`');
		$db->table('file')->delete();

		$this->importTable('deska', 'document', array(
			'id' => 'id',
			'text' => 'description',
			'soubor' => array('file_id', 'file', 'nazev'),
			'vyveseno' => array('date_from', 'time'),
			'sejmuto' => array('date_to', 'time'),
		));

		$this->importTable('galerie', 'photo', array(
			'id' => 'id',
			'nazev' => 'title',
			'cl_id' => 'article_id',
			'foto' => 'filename_photo',
			'nahled' => 'filename_thumb',
		));

		$this->importTable('navstevni_kniha', 'guestbook_item', array(
			'id' => 'id',
			'jmeno' => 'author',
			'datum' => array('date', 'time'),
			'text' => 'text',
		));

		$this->importTable('soubory', 'attachment', array(
			'id' => 'id',
			'cl_id' => 'article_id',
			'odkaz' => array('file_id', 'file', 'nazev'),
		), 'cl_id > 0');

		$this->importTable('soubory', 'download', array(
			'id' => 'id',
			'odkaz' => array('file_id', 'file', 'nazev'),
		), 'cl_id = 0');

		$this->importTable('users', 'teacher', array(
			'id' => 'id',
			'prijmeni' => 'surname',
			'jmeno' => 'firstname',
			'titul' => 'title',
			'skolni_mail' => 'school_email',
			'telefon' => 'phone_number',
		), 'status = 1 OR status = 3');

		$this->importTable('tridy', 'class', array(
			'id_tridy' => 'id',
			'nazev_tridy' => 'name',
		), "maturoval = 0000");

		l('Pairing teachers and theirs classes');
		// "SELECT id, trida FROM users WHERE status = 1" | "SET teacher_id = <id> WHERE id = <trida>";
		foreach ($this->dbRemote->table('users')->where('status = 1')->select('id, trida') as $user) {
			$db->table('class')->where('id', $user->trida)->update(array(
				'teacher_id' => $user->id,
			));
		}		

		l('Updating end years of classes');
		$endYears = array();
		$thisYear = date('Y');
		foreach ($this->classes as $classes) {
			for ($i=0; $i < count($classes); $i++) { 
				$endYears[$classes[$i]] = $thisYear + (count($classes) - $i) - 1;
			}
		}
		$classes = $db->table('class');
		foreach ($classes->where('end_year = 0') as $class) {
			$class->update(array(
				'end_year' => $endYears[$class->name],
			));
		}

		$this->importTable('users', 'user', array(
			'id' => 'id',
			'nick' => 'nickname',
			'heslo' => 'password',
		), 'status = 3');

		$this->importTable('users', 'student', array(
			'id' => 'id',
			'prijmeni' => 'surname',
			'jmeno' => 'firstname',
			'trida' => 'class_id',
		), array('(status = 0 OR status > 2) AND trida ?' => array_keys(iterator_to_array($classes)))); // filter only current classes
		// classes of previous years will be imported from pdf

		l('Importing graduates from pdf');
		$this->importGraduates();

		l('Importing banners from page source code');
		$this->importBanners();


		$db->query('SET foreign_key_checks = 1');
		$db->commit();
	}

	private function importBanners() {
		$table = $this->dbLocal->table('banner');
		$table->delete();

		$source = file_get_contents('http://www.gymn-dacice.cz/');
		preg_match_all('#<a href="(.*?)" title="(.*?)" >\s+<img src="images/(.*?)"#', $source, $matches, PREG_SET_ORDER);
		foreach ($matches as $order => $match) {
			$bannerRow = array(
				'title' => $match[2],
				'link' => $match[1],
				'order' => $order,
				'imagefile' => $match[3],
			);
			$table->insert($bannerRow);

		}
	}

	private function importGraduates() {
		$data = file_get_contents($this->graduatesFile);
		$db = $this->dbLocal;

		$classesSelection = $db->table('class');
		$studentsSelection = $db->table('student');
		foreach (explode('Školní rok', $data) as $classData) {
			$classRow = array();
			if (!preg_match('#\d{4}/(\d{4})#', $classData, $matches)) {
				// there is no class data in this item (only some note)
				continue;
			}
			$classRow['end_year'] = $matches[1];

			if (preg_match('#^Třída: (.*?)\r?$#mu', $classData, $matches)) {
				$classRow['name'] = $matches[1];
			}

			if (!preg_match('#^Třídní (.*?):\s+(.*?)\r?$#mu', $classData, $matches)) {
				continue;
			}
			$teacherName = preg_split('#\s+#u', $matches[2]);
			if (count($teacherName) > 3) {
				$classRow['name'] = "$teacherName[0] $teacherName[1]";
				$teacherName = array_slice($teacherName, 2);
			}
			$teacherData = array();
			if (count($teacherName) > 2) {
				$teacherData['title'] = array_shift($teacherName);
			}
			list($teacherData['firstname'], $teacherData['surname']) = $teacherName;
			$teacher = $db->table('teacher')->where($teacherData)->fetch();
			$teacherData['active'] = 0;
			$classRow['teacher_id'] = $teacher ? $teacher->id : $db->table('teacher')->insert($teacherData)->id;

			$classId = $classesSelection->insert($classRow)->id;

			preg_match_all('#^\d+\.\s+(.*?)\s+(.*?)[,.]\s+(.*?)\r?$#mu', $classData, $matches, PREG_SET_ORDER);
			$students = array();
			$reverse = FALSE;
			foreach ($matches as $studentMatch) {
				$students[] = $student = array(
					'firstname' => $studentMatch[1],
					'surname' => $studentMatch[2],
					'address' => $studentMatch[3],
					'class_id' => $classId,
				);
				if (!$reverse && mb_substr($student['firstname'], -3) == 'ová') {
					$reverse = TRUE;
				}
			}
			if ($reverse) {
				foreach ($students as $student) {
					$s = $student['firstname'];
					$student['firstname'] = $student['surname'];
					$student['surname'] = $s;
				}
			}
			array_map(array($studentsSelection, 'insert'), $students);
		}
	}

}
