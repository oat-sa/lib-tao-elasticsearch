#!/usr/bin/env php
<?php
use oat\oatbox\service\ServiceManager;
use oat\oatbox\action\ActionService;
use oat\tao\elasticsearch\Action\InitElasticSearch;

$parms = $argv;
array_shift($parms);

if (count($parms) < 1) {
    echo 'Usage: '.__FILE__.' TAOROOT [HOST] [PORT] [PATH]'.PHP_EOL;
    die(1);
}

$root = rtrim(array_shift($parms), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
$rawStart = $root.'tao'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'raw_start.php';

if (!file_exists($rawStart)) {
    echo 'Tao not found at "'.$rawStart.'"'.PHP_EOL;
    die(1);
}

require_once $rawStart;

$actionService = ServiceManager::getServiceManager()->get(ActionService::SERVICE_ID);
$factory = $actionService->resolve(InitElasticSearch::class);
$report = $factory->__invoke($parms);
echo tao_helpers_report_Rendering::renderToCommandline($report);
