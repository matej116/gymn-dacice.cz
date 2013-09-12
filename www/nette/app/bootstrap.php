<?php

// workaround for PHP 5.2 (constant __DIR__ is not available)
$__DIR__ = dirname(__FILE__);

// Load Nette Framework
require $__DIR__ . '/../libs/nette.min.php';


$configurator = new Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->setDebugMode(TRUE);
$configurator->enableDebugger($__DIR__ . '/../log');

// Specify folder for cache
$configurator->setTempDirectory($__DIR__ . '/../temp');

// Enable RobotLoader - this will load all classes automatically
$configurator->createRobotLoader()
	->addDirectory($__DIR__) // app dir
	->addDirectory($__DIR__ . '/../libs')
	->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig($__DIR__ . '/config/config.neon');
if (file_exists($localConfig = $__DIR__ . '/config/secret.neon')) {
	$configurator->addConfig($localConfig, Configurator::NONE); // none section
}
if (file_exists($localConfig = $__DIR__ . '/config/config.local.neon')) {
	$configurator->addConfig($localConfig, Configurator::NONE); // none section
}

$configurator->onCompile[] = create_function(
	'$configurator, $compiler', 
	'$compiler->addExtension("ajax", new AjaxExtension);'
);
// @TODO
// suppress errors, it is meant to suppress Strict error, which is raising in PHP 5.2 on 
// is_callable(array('RouterFactory', 'createRouter'))
$container = @$configurator->createContainer();

CaptchaControl::register($container->getByType('Session'));

return $container;
