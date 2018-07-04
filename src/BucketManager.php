<?php

namespace Jormin\Qiniu;

use Qiniu\Auth;
use Qiniu\Cdn\CdnManager;
use Qiniu\Config;
use Qiniu\Http\Client;
use Qiniu\Http\Error;

/**
 * 七牛
 *
 * @package Jormin\Qiniu
 */
class BucketManager{
    private $auth;
    private $config;

    public function __construct(Auth $auth, Config $config = null)
    {
        $this->auth = $auth;
        if ($config == null) {
            $this->config = new Config();
        } else {
            $this->config = $config;
        }
    }

    /**
     * 创建空间
     *
     * @param String $bucket 空间名称
     * @param String $region 区域，默认滑动  z0 华东 z1 华北   z2 华南   na0 北美  as0 东南亚
     *
     * @return Mixed
     * @link https://developer.qiniu.com/kodo/api/1382/mkbucketv2
     */
    public function createBucket($bucket, $region='z0')
    {
        $bucket = \Qiniu\base64_urlSafeEncode($bucket);
        $path = "/mkbucketv2/".$bucket."/region/".$region;
        list(, $error) = $this->rsPost($path);
        return $error;
    }

    /**
     * 删除空间
     *
     * @param String $bucket 空间名称
     *
     * @return Mixed
     * @link https://developer.qiniu.com/kodo/api/1601/drop-bucket
     */
    public function dropBucket($bucket)
    {
        $path = "/drop/".$bucket;
        list(, $error) = $this->rsPost($path);
        return $error;
    }

    /**
     * 设置 Bucket 访问权限
     *
     * @param String $bucket 空间名称
     * @param integer $private 权限 0 公开 1 私有
     *
     * @return Mixed
     * @link https://developer.qiniu.com/kodo/api/3946/put-bucket-acl
     */
    public function setBucketAuth($bucket, $private)
    {
        $path = "/private";
        $params = "bucket=".$bucket."&private=".$private;
        list(, $error) = $this->ucPost($path, $params);
        return $error;
    }

    private function getRsHost()
    {
        $scheme = "http://";
        if ($this->config->useHTTPS == true) {
            $scheme = "https://";
        }
        return $scheme . Config::RS_HOST;
    }

    private function getUcHost()
    {
        $scheme = "http://";
        if ($this->config->useHTTPS == true) {
            $scheme = "https://";
        }
        return $scheme . 'uc.qbox.me';
    }

    private function rsPost($path, $body = null)
    {
        $url = $this->getRsHost() . $path;
        return $this->post($url, $body);
    }

    private function ucPost($path, $body = null)
    {
        $url = $this->getUcHost() . $path;
        return $this->post($url, $body);
    }

    private function post($url, $body)
    {
        $headers = $this->auth->authorization($url, $body, 'application/x-www-form-urlencoded');
        $ret = Client::post($url, $body, $headers);
        if (!$ret->ok()) {
            return array(null, new Error($url, $ret));
        }
        $r = ($ret->body === null) ? array() : $ret->json();
        return array($r, null);
    }

}