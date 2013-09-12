<?php

class SearchPresenter extends BasePresenter {

	private $searcher;

	public function injectSearcher(FulltextSearcher $searcher) {
		$this->searcher = $searcher;
	}

	public function renderResults($q) {
		$template = $this->template;
		$template->q = $q;
		$template->results = $this->searcher->getResults($q);
	}

}
