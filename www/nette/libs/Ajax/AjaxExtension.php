<?php

/**
 * Provides support for History API
 */
class AjaxExtension extends ConfigCompilerExtension
{

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->prefix('onRequestHandler'))
			->setClass('AjaxOnRequestHandler');

		$container->addDefinition($this->prefix('onResponseHandler'))
			->setClass('AjaxOnResponseHandler');

		$application = $container->getDefinition('application');
		$application->addSetup('$service->onRequest[] = ?', array('@' . $this->prefix('onRequestHandler')));
		$application->addSetup('$service->onResponse[] = ?', array('@' . $this->prefix('onResponseHandler')));
	}

}
