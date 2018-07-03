<?php
require_once '../vendor/autoload.php';
require_once '../src/Qiniu.php';
require_once './function.php';
$accessKey = 'LJLTXDLpE4CYKbpZvq1PhhYFhNXJnA1KvKuyAQO9';
$secretKey = 'HBLpmenwWcOY1fa9Ma4EoIKHPandFwQATiG0ZE0u';
$bucket = 'temp';
$qiniu = new \Jormin\Qiniu\Qiniu($accessKey, $secretKey);
$urls = [
    'http://onzkl5men.bkt.clouddn.com/1111.jpg',
    'http://onzkl5men.bkt.clouddn.com/FhGB9z_kT8_p7Iw4AdfwaEZss4Ah'
];
$dirs = [
    'http://onzkl5men.bkt.clouddn.com/test/'
];
$response = $qiniu->refresh($urls, $dirs);
var_dump($response);
