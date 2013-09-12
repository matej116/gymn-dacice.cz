<?php

class GuestBook extends Object {
	
	private $db;

	public function __construct(Connection $db) {
		$this->db = $db;
	}

	protected function getTable() {
		return $this->db->table('guestbook_item');
	}

	public function getItems() {
		$selection = $this->getTable()->order('date DESC');
		$items = array(); // array of array($row, $respondRow1, $respondRow2 ...)
		// @TODO optimalize tree building (don't 3 foreach loops)
		foreach ($selection as $id => $row) {
			$items[$id] = $this->createItem($row);
		}
		foreach ($items as $id => $item) {
			if ($parent = $item->parent_id) {
				$items[$parent]->addReply($item);
			}
		}
		foreach ($items as $id => $item) {
			if ($item->parent_id) {
				unset($items[$id]);
			}
			$item->freeze();
		}
		return $items;
	}

	public function getItem($id) {
		return $this->getTable()->wherePrimary($id)->fetch();
	}

	/**
	 * @return bool If new item was succesfully inserted
	 */
	public function addItem($author, $text, $parentId = NULL) {
		$table = $this->getTable();		
		$newRow = array(
			'author' => $author,
			'text' => $text,
			'date' => new DateTime,
			'parent_id' => $parentId,
		);
		return $table->insert($newRow) ? TRUE : FALSE;
	}

	protected function createItem(TableRow $row) {
		return new GuestBookItem($row);
	}
}

/**
 * represents one guest book item as tree node
 * @TODO make general tree structure node class
 */
class GuestBookItem extends FreezableObject {

	/** @var array of GuestBookItem */
	protected $replies = array();

	/** @var TableRow */
	protected $data;

	public function __construct(TableRow $data) {
		$this->data = $data;
	}

	public function &__get($name) {
		if (parent::__isset($name)) {
			return parent::__get($name);
		} else {
			return $this->data->__get($name);
		}
	}

	public function addReply(GuestBookItem $item) {
		$this->updating();
		$this->replies[$item->id] = $item;
	}

	public function getReplies() {
		return $this->replies;
	}

	public function freeze() {
		parent::freeze();
		foreach ($this->replies as $respond) {
			$respond->freeze();
		}
	}
}
