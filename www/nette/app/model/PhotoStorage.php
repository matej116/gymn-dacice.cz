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

class PhotoImageStorage extends Object {
	
	/** @var Connection */
	private $db;

	/** @var ImageStorage */
	private $photos, $thumbs;

	public function __construct(Connection $db, ImageStorage $photos, ImageStorage $thumbs) {
		$this->db = $db;
		$this->photos = $photos;
		$this->thumbs = $thumbs;
	}

	/**
	 * @return PhotoStorage
	 */
	public static function create(Connection $db, $dir, $width, $height, $thumbWidth, $thumbHeight, 
		$format = '%s', $thumbFormat = '%s_thumb', $ext = 'jpg') 
	{
		if ($format === $thumbFormat) {
			throw new InvalidArgumentException("Format of photo and hit thumbnail cannot be same");
		}
		$photos = new ImageStorage($dir, $width, $height, $format, $ext);
		$thumbs = new ImageStorage($dir, $thumbWidth, $thumbHeight, $thumbFormat, $ext);
		return new self($db, $photos, $thumbs);
	}

	protected function getDbSelection() {
		return $this->db->table('photo');
	}

	public function getPhotoFileName($id) {
		return $this->photos->getFileName($id);
	}

	public function getThumbFileName($id) {
		return $this->photos->getFileName($id);
	}

	public function getFileNamesOfArticle($id) {
		
	}

	public function exists($id) {
		return file_exists($this->getPhotoFileName($id)) &&
		       file_exists($this->getThumbFileName($id));
	}

	public function delete($id) {
		@unlink($this->getPhotoFileName($id));
		@unlink($this->getThumbFileName($id));
	}

	public function add($filename, $title, $articleId, $id = NULL) {
		$id = $this->getDbSelection()->insert(array(
			'id' => $id,
			'title' => $title,
			'article_id' => $articleId,
		))->id;
		$this->photos->save($filename, $id);
		$this->thumbs->save($filename, $id);
	}

}

class ImageStorage extends Object {

	/** @var string */
	private $dir;

	/** @var int */
	private $width, $height;

	/** @var string */
	private $format;

	/** @var string */
	private $ext;

	public function __construct($dir, $width, $height, $format = '%s', $ext = 'jpg') 
	{
		if (!is_dir($dir) || !is_writable($dir)) {
			throw new InvalidArgumentException("Directory '$dir' does not exist or is not writable");
		}
		$this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
		$this->width = $width;
		$this->height = $height;
		$this->format = $format;
		$this->ext = $ext;
	}

	public function getFileName($id) {
		$filename = $this->dir . 
			DIRECTORY_SEPARATOR . 
			sprintf($this->format, $id) . 
			'.' . 
			$this->ext;
		return $filename;
	}

	public function save($filename, $id) {
		$img = Image::fromFile($filename);
		$img->resize($this->width, $this->height);
		$newFileName = $this->getFileName($id);
		$img->save($newFileName);
	}

}