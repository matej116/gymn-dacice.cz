<?php

/**
 * Automatically adds 'redirect' to payload when forward happens
 * to simplify userland code in presenters.
 *
 * Also bypasses 'redirect()' calls with 'forward()' calls.
 *
 * Sets 'Vary: X-Requested-With' header to disable payload caching.
 *
 * @author Vojtěch Dobeš
 */
class AjaxOnResponseHandler
{

	/** @var Http\IRequest */
	private $httpRequest;

	/** @var Http\IResponse */
	private $httpResponse;

	/** @var IRouter */
	private $router;

	/** @var bool */
	private $forwardHasHappened = FALSE;



	/**
	 * @param  Http\IRequest
	 * @param  Http\IResponse
	 * @param  IRouter
	 */
	public function __construct(IHttpRequest $httpRequest, IHttpResponse $httpResponse, IRouter $router)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->router = $router;
	}



	/**
	 * Stores information about ocurring forward() call
	 */
	public function markForward()
	{
		$this->forwardHasHappened = TRUE;
	}



	public function __invoke($application, $response)
	{
		if ($response instanceof JsonResponse && ($payload = $response->getPayload()) instanceof stdClass) {
			if (!$this->forwardHasHappened && isset($payload->redirect)) {
				$url = new UrlScript($payload->redirect);
				$url->setScriptPath($this->httpRequest->url->scriptPath);
				$httpRequest = new HttpRequest($url);

				if ($this->router->match($httpRequest) !== NULL) {
					$prop = new PropertyReflection($application, 'httpRequest');
					$prop->setAccessible(TRUE);
					$prop->setValue($application, $httpRequest);

					$application->run();
					exit;
				}
			} elseif ($this->forwardHasHappened && !isset($payload->redirect)) {
				$payload->redirect = $application->getPresenter()->link('this');
			}
		}
		$this->httpResponse->addHeader('Vary', 'X-Requested-With');
	}

}
