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
			->order('date DESC, id DESC');
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

	/** For administration: *********************************************/

	public function getGOneVideos() {
		return $this->db->table('g_one_video')
			->order('id DESC')
			->fetchPairs('id', 'title');
	}

	private function filterData($data) {
		return array(
			'title' => $data->title,
			'text' => $data->text,
			'date' => $data->date,
			'g_one_video_id' => $data->g_one_video_id,
			'menu_id' => $data->menu_id,
		);
	}

	public function insert($data) {
		return $this->table()->insert($this->filterData($data));
	}

	public function update($id, $data) {
		return $this->table()->wherePrimary($id)->update($this->filterData($data));
	}

	public function getAllArticles() {
		return $this->table()->order('date DESC');
	}

	public function getPhoto($articleId, $photoId) {
		return $this->db->table('photo')
			->where('article_id', $articleId)
			->where('id', $photoId)
			->fetch();
	}

	public function deletePhoto($articleId, $photoId) {
		$photo = $this->getPhoto($articleId, $photoId);
		if ($photo) {
			$photo->delete();
		}
	}

	public function addPhoto($articleId, $title, $fileNamePhoto, $fileNameThumb) {
		return $this->db->table('photo')->insert(array(
			'article_id' => $articleId,
			'title' => $title,
			'filename_photo' => $fileNamePhoto,
			'filename_thumb' => $fileNameThumb,
		));
	}

	public function changePhotoTitle($articleId, $photoId, $newTitle) {
		return $this->getPhoto($articleId, $photoId)->update(array(
			'title' => $newTitle,
		));
	}
}
