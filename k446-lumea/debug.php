<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '/www/wwwroot/test.susufuture.com/index.php';
$view = core\view\View::getInstance();
$content = $view->parser('/template/default/index.html');
echo "Length:" . strlen($content) . "\n";
echo substr($content, 0, 200);
?>