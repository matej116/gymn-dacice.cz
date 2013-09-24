<?php

class JokePresenter extends BasePresenter {
	
	public function renderShow($id = NULL) {
		$jokes = $this->jokes;
		$latest = $jokes->getLatest();
		if (!$id) {
			$this->redirect('show', array('id' => $latest->id));
		}

		$template = $this->template;

		$template->latestId = $latest->id;
		$template->joke = $row = ($id === $latest->id ? $latest : $jokes->getJoke($id));

		$next = $jokes->getNext($row->date_from);
		$template->nextId = $next ? $next->id : NULL;
		
		$previous = $jokes->getPrevious($row->date_from);
		$template->previousId = $previous ? $previous->id : NULL;

	}

}