<?php

/**
 * model for loading filename of photos
 */
class PhotoStorage extends Object {

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

	public function getImagesDir() {
		return $this->dir;
	}

	public function delete() {
		if (func_num_args() > 1) {
			$args = func_get_args();
			return array_map(callback($this, 'delete'), $args);
		} else {
			$file = func_get_arg(0);
			return @unlink($this->prefix($file));
		}
	}

	/* For administration *************************************************/

	public function save(HttpUploadedFile $file, $maxSize = 0, $prefix = '') {
		$image = Image::fromFile($file->getTemporaryFile());
		if ($maxSize > 0) {
			$image->resize($maxSize, $maxSize, Image::FIT | Image::SHRINK_ONLY);
		}
		$filename = $prefix . $file->getName();
		//find new file name
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
		$image->save($this->prefix($filename));
		return $filename;
	}

}
