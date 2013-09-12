<?php

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

// Let bootstrap create Dependency Injection container.
$container = require dirname(__FILE__) . '/nette/app/bootstrap.php';

// Run application.
$container->application->run();
