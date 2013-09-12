<?php

/**
 * Listens for forward calls
 */
class AjaxOnRequestHandler
{

	/** @var Http\IRequest */
	private $httpRequest;

	/** @var OnResponseHandler */
	private $onResponseHandler;



	/**
	 * @param  Http\IRequest
	 * @param  OnResponseHandler
	 */
	public function __construct(IHttpRequest $httpRequest, AjaxOnResponseHandler $onResponseHandler)
	{
		$this->httpRequest = $httpRequest;
		$this->onResponseHandler = $onResponseHandler;
	}



	public function __invoke($application, $request)
	{
		if ($this->httpRequest->isAjax() && count($application->getRequests()) > 1) {
			$this->onResponseHandler->markForward();
		}
	}

}
