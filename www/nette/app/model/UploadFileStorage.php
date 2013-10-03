<?php

class UploadFileStorage extends Object {
	
	/** @var string */
	private $dir;

	private $wwwDir;

	public function __construct($wwwDir, $dir) {
		$this->wwwDir = $wwwDir;
		$this->dir = rtrim($dir, '/');
	}


	protected function prefix($file) {
		return $this->wwwDir . DIRECTORY_SEPARATOR . $this->dir . DIRECTORY_SEPARATOR . $file;
	}


	public function getDir() {
		return $this->dir;
	}

	public function delete($filename) {
		$filename = $this->prefix($filename);
		if (file_exists($filename)) {
			return unlink($filename);
		} else {
			return FALSE;
		}
	}

	protected function getNonExistentFilename($filename) {
		if ($pos = strrpos($filename, '.')) {
			$name = substr($filename, 0, $pos);
			$ext = substr($filename, $pos);
		} else {
			$name = $filename;
			$ext = '';
		}
		$counter = 0;
		while (file_exists($this->prefix($filename = $name . ($counter ? "_$counter" : '') . $ext))) {
			$counter++;
		}
		return $filename;
	}

	/* For administration *************************************************/

	public function save(HttpUploadedFile $file, $prefix = '') {
		$filename = $prefix . $file->getName();
		$filename = $this->getNonExistentFilename($filename);
		$file->move($this->prefix($filename));
		return $filename;
	}
}