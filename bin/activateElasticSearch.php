#!/usr/bin/env php
<?php
use oat\oatbox\service\ServiceManager;
use oat\oatbox\action\ActionService;
use oat\tao\elasticsearch\Action\InitElasticSearch;

$params = $argv;
array_shift($params);

if (count($params) < 1) {
    echo 'Usage: '.__FILE__.' TAOROOT [HOST] [PORT] [LOGIN] [PASSWORD]'.PHP_EOL;
    die(1);
}

$root = rtrim(array_shift($params), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
$rawStart = $root.'tao'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'raw_start.php';

if (!file_exists($rawStart)) {
    echo 'Tao not found at "'.$rawStart.'"'.PHP_EOL;
    die(1);
}

require_once $rawStart;
$params[] = require(__DIR__ . '/../config/index.conf.php');

$actionService = ServiceManager::getServiceManager()->get(ActionService::SERVICE_ID);
$factory = $actionService->resolve(InitElasticSearch::class);
$report = $factory->__invoke($params);
echo tao_helpers_report_Rendering::renderToCommandline($report);
