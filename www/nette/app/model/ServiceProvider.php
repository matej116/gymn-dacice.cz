<?php

/**
 * provider of given service from DI container
 * if given service does not exist, $this->getService() returns NULL and catches all raised exceptions
 */
class ServiceProvider extends Object {

	protected $container;

	protected $serviceName;

	public function __construct(DIContainer $container, $serviceName) {
		$this->container = $container;
		$this->serviceName = $serviceName;
	}

	public function getService() {
		try {
			$service = @$this->container->getService($this->serviceName);
			return $service;
		} catch (Exception $ex) {
			return NULL;
		}
	}

}