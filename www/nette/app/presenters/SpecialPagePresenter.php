<?php

/**
 *
 */
class SpecialPagePresenter extends BasePresenter {
	
	/** @var Connection */
	private $db;

	public function injectDb(Connection $db) {
		$this->db = $db;
	}

	public function renderContacts() {
		$template = $this->template;
		$template->teachers = $this->db->table('teacher')
			->where('active = 1')
			->order('surname');
	}

	/**
	 * Úřední deska
	 */
	public function renderDocuments() {
		$template = $this->template;
		$template->documents = $this->db->table('document')
			->where('date_from <= CURDATE()')
			->where('date_to >= CURDATE()')
			->order('id');
	}

	/**
	 * Ke stažení
	 */
	public function renderDownloads() {
		$template = $this->template;
		$template->downloads = $this->db->table('download')
			->order('id');
	}

	/**
	 * třídy
	 */
	public function renderClasses() {
		$classNames = $this->context->params['classes'];
		$classes = array_fill_keys(array_keys($classNames), array());
		foreach ($this->db->table('class')->where('end_year >= ?', date('Y')) as $classRow) {
			$title = NULL;
			foreach ($classNames as $classesTitle => $names) {
				if (in_array($classRow->name, $names)) {
					$title = $classesTitle;
					break;
				}
			}
			if ($title === NULL) {
				throw new InvalidStateException("Class '$classRow->name' is not defined in config");
			}
			$classes[$title][$classRow->name] = $classRow;
		}
		// sort $classes[$title] by $classNames
		// inspired by http://stackoverflow.com/a/9098675
		foreach ($classes as $title => &$classList) {
			$classList = array_merge(array_flip($classNames[$title]), $classList);
		}

		$template = $this->template;
		$template->classes = $classes;
	}

	
}