<?php

/**
 * Router factory.
 * all mthod static <- it's PHP 5.2
 */
class RouterFactory
{

	private $captchaControl;

	public function __construct(LazyCaptchaControl $captchaControl) {
		$this->captchaControl = $captchaControl;
	}

	/**
	 * create back compatibility router matching all URLs from old website
	 */
	protected function createBCRouter() {
		$router = new RouteList;

		$router[] = new Route('clanek.php?id=<id \d+>', 'Article:show');
		$router[] = new Route('vypis.php?m=<menu \d+>&str=<paginator-page \d+>', 'Article:list');	

		$router[] = new Route('navstevni_kniha.php', 'GuestBook:default');
		$router[] = new Route('prispevek_kniha.php', 'GuestBook:add');
		// fotogalerie v nové verzi neexsituje
		$router[] = new Route('ke-stazeni.php', 'SpecialPage:downloads');	
		$router[] = new Route('kalendar.php', 'Article:list');
		$router[] = new Route('trid<? a|y >.php', 'SpecialPage:classes');
		// @todo absolventi

		return $router;
	}

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

		// router for CLI (command line) - default action is import data from old to new database
		if (PHP_VERSION_ID > 50300) { // only if PHP version > 5.3.0
			$router[] = new CliRouter(array('action'=>'Import:import'));
		}

		// @TODO SEO URL (<id \d+>-<title>)
		$router[] = new Route('[index.php]', 'Article:list', Route::ONE_WAY);
		$router[] = new Route('article?id=<id \d+>', 'Article:show');
		$router[] = new Route('[list]?menu=<menu \d+>&page=<page \d+>', 'Article:list');

		// Admin
		$router[] = new Route('admin/<action>', 'Admin:default');

		$router[] = new Route('navstevni-kniha[/<action>]', 'GuestBook:default');
		$router[] = new Route('<action>', array(
			'presenter' => 'SpecialPage',
			'action' => array(
				Route::FILTER_TABLE => array(
					'kontakty' => 'contacts',
					'uredni-deska' => 'documents',
					'ke-stazeni' => 'downloads',
					'tridy' => 'classes',
				),
			),
		));

		$router[] = $this->captchaControl->createImageRoute();

		// Back compatibility URLs
		$router[] = $this->createBCRouter();

		// Default fallback
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}

}
