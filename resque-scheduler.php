<?php

// Look for an environment variable with 
$RESQUE_PHP = getenv('RESQUE_PHP');
if (!empty($RESQUE_PHP)) {
	require_once $RESQUE_PHP;
}
// Otherwise, if we have no Resque then assume it is in the include path
else if (!class_exists('Resque')) {
	require_once 'Resque/Resque.php';
}

// Load resque-scheduler
require_once dirname(__FILE__) . '/lib/ResqueScheduler.php';
require_once dirname(__FILE__) . '/lib/ResqueScheduler/Worker.php';

$REDIS_BACKEND = getenv('REDIS_BACKEND');
$REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
if(!empty($REDIS_BACKEND)) {
	if (empty($REDIS_BACKEND_DB)) 
		Resque::setBackend($REDIS_BACKEND);
	else
		Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB);
}

$logLevel = false;
$LOGGING = getenv('LOGGING');
$VERBOSE = getenv('VERBOSE');
$VVERBOSE = getenv('VVERBOSE');
if(!empty($LOGGING) || !empty($VERBOSE)) {
	$logLevel = true;
}
else if(!empty($VVERBOSE)) {
	$logLevel = true;
}

// Load the user's application if one exists
$APP_INCLUDE = getenv('APP_INCLUDE');
if($APP_INCLUDE) {
	if(!file_exists($APP_INCLUDE)) {
		die('APP_INCLUDE ('.$APP_INCLUDE.") does not exist.\n");
	}

	require_once $APP_INCLUDE;
}

// See if the APP_INCLUDE containes a logger object,
// If none exists, fallback to internal logger
if (!isset($logger) || !is_object($logger)) {
    $logger = new Resque_Log($logLevel);
}

// Check for jobs every $interval seconds
$interval = 5;
$INTERVAL = getenv('INTERVAL');
if(!empty($INTERVAL)) {
	$interval = $INTERVAL;
}

$PREFIX = getenv('PREFIX');
if(!empty($PREFIX)) {
    $logger->log(Psr\Log\LogLevel::INFO, 'Prefix set to {prefix}', array('prefix' => $PREFIX));
    Resque_Redis::prefix($PREFIX);
}

$worker = new ResqueScheduler_Worker();
$worker->setLogger($logger);

$PIDFILE = getenv('PIDFILE');
if ($PIDFILE) {
	file_put_contents($PIDFILE, getmypid()) or
		die('Could not write PID information to ' . $PIDFILE);
}

$logger->log(Psr\Log\LogLevel::NOTICE, 'Starting scheduler worker');
$worker->work($interval);
