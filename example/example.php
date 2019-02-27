<?php
// +----------------------------------------------------------------------
// | cmdProcess
// +----------------------------------------------------------------------
// | Copyright (c) 2019
// +----------------------------------------------------------------------
// | Licensed GPL-3.0
// +----------------------------------------------------------------------
// | Author: zhangjs
// +----------------------------------------------------------------------
// | Date: 2019-2-27
// +----------------------------------------------------------------------
// | Time: 上午 10:23
// +----------------------------------------------------------------------
namespace phpth\example;

use phpth\process\factory;

require '../src/factory.php';
require '../src/runner.php';
require '../src/collection.php';
require '../src/executor.php';


$fin = factory::getInstance ();
$fin->addCmd ('php -r "echo 1231231,PHP_EOL;sleep(100);"');
$fin->addCmd ('php -r "echo 1231231,PHP_EOL;sleep(100);"');
$fin->addCmd ('php -r "echo 1231231,PHP_EOL;sleep(100);"');
$fin->addCmd ('php -r "echo 1231231,PHP_EOL;sleep(100);"');
$fin->addCmd ('php -r "echo 1231231,PHP_EOL;sleep(100);"');
$exec = $fin->getExecutor ();
$exec->run ();
$exec->wait ();
