<?php

/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 * @link       http://extras.nettephp.com
 * @package    Nette Extras
 */

/*use Nette\Application\Control;*/

/*use Nette\Paginator;*/

/**
 * Visual paginator control.
 *
 * @author     David Grudl, edited by Matěj Polák
 * @copyright  Copyright (c) 2009 David Grudl
 * @package    Nette Extras
 */
class VisualPaginator extends Control
{
	/** @var Paginator */
	private $paginator;

	/** @persistent */
	public $page = 1;

	/** @var bool */
	public $showSteps = FALSE;

	/**
	 * @return Nette\Paginator
	 */
	public function getPaginator()
	{
		if (!$this->paginator) {
			$this->paginator = new Paginator;
		}
		return $this->paginator;
	}

	/**
	 * Renders paginator.
	 * @return void
	 */
	public function render()
	{
		$paginator = $this->getPaginator();
		$page = $paginator->page;
		$template = $this->getTemplate();

		if ($this->showSteps) {
			if ($paginator->pageCount < 2) {
				$steps = array($page);
			} else {
				$arr = range(max($paginator->firstPage, $page - 3), min($paginator->lastPage, $page + 3));
				$count = 4;
				$quotient = ($paginator->pageCount - 1) / $count;
				for ($i = 0; $i <= $count; $i++) {
					$arr[] = round($quotient * $i) + $paginator->firstPage;
				}
				sort($arr);
				//check useless spaces in $steps, by Matěj Polák
				$count = count($arr)-1;
				for ($i = 0; $i < $count; $i++) {
					if ($arr[$i+1] - $arr[$i] == 2) {
						$arr[] = $arr[$i]+1;
					}
				}
				$steps = array_values(array_unique($arr));
				sort($steps);
			}
			$template->steps = $steps;
		}

		$template->paginator = $paginator;
		$template->setFile(dirname(__FILE__) . '/template.latte');
		$template->render();
	}



	/**
	 * Loads state informations.
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->getPaginator()->page = $this->page;
	}

}