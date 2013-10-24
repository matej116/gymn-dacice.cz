<?php

class DatabaseFileStorage extends UploadFileStorage {
	
	private $db;

	private $tableName;

	public function __construct(Connection $db, $tableName, $wwwDir, $dir) {
		parent::__construct($wwwDir, $dir);
		$this->db = $db;
		$this->tableName = $tableName;
	}

	protected function getTable() {
		return $this->db->table($this->tableName);
	}

	public function getFilename($id) {
		return $this->getTable()->where('id', $id)->fetch()->filename;
	}

	public function save(HttpUploadedFile $file, $prefix = '', $title = '') {
		$filename = parent::save($file, $prefix);		
		$row = $this->getTable()->insert(array(
			'title' => $title,
			'filename' => $filename,
		));
		return $row->id;
	}

	public function updateTitle($fileId, $title) {
		return $this->getTable()->wherePrimary($fileId)->update(array(
			'title' => $title,
		));
	}

	public function delete($filename) {
		$this->getTable()->where('filename', $filename)->delete();
		return parent::delete($filename);
	}

	public function deleteById($id) {
		$row = $this->getTable()->where('id', $id)->fetch();
		if (!$row) {
			throw new InvalidStateException("ID $id not found in table $this->tableName");
		}
		$filename = $row->filename;
		$row->delete();
		return parent::delete($filename);
	}

}