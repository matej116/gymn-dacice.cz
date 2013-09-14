<?php

/**
 * model for loading filename of photos
 */
class PhotoStorage extends Object {

	/** @var Connection */
	private $db;

	/** @var string */
	private $dir;

	public function __construct(Connection $db, $dir) {
		$this->db = $db;
		$this->dir = rtrim($dir, '/');
	}

	protected function getSelection($article = NULL) {
		$selection = $this->db->table('photo');
		if ($article !== NULL) {
			$selection->where('article_id', $article);
		}
		return $selection;
	}

	protected function prefix($file) {
		return $this->dir . '/' . $file;
	}

	/** @return array($photos, $thumbs) */
	public function getFileNames($article) {
		$selection = $this->getSelection($article);
		$photos = array();
		$thumbs = array();
		foreach ($selection as $row) {
			$key = $row->id;
			$photos[$key] = $this->prefix($row->filename_photo);
			$thumbs[$key] = $this->prefix($row->filename_thumb);
		}
		return array($photos, $thumbs);
	}

}
