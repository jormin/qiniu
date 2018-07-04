<?php

namespace Jormin\Qiniu;

use Qiniu\Auth;
use Qiniu\Cdn\CdnManager;
use Qiniu\Storage\BucketManager;
use Jormin\Qiniu\BucketManager as SubBucketManager;
use Qiniu\Storage\UploadManager;

/**
 * 七牛
 *
 * @package Jormin\Qiniu
 */
class Qiniu{

    private $accessKey, $secretKey;

    public $auth, $bucketManager, $subBucketManager, $cdnManager;

    /**
     * Qiniu constructor.
     * @param $accessKey
     * @param $secretKey
     */
    public function __construct($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->auth = new Auth($accessKey, $secretKey);
        $this->bucketManager = new BucketManager($this->auth);
        $this->subBucketManager = new SubBucketManager($this->auth);
        $this->cdnManager = new CdnManager($this->auth);
    }

    /**
     * 空间列表
     *
     * @return array
     */
    public function buckets(){
        list($buckets, $err) = $this->bucketManager->buckets(true);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        foreach ($buckets as $key => $bucket){
            $response = $this->domains($bucket);
            if($response['error']){
                return $response;
            }
            $buckets[$key] = [
                'name' => $bucket,
                'domains' => $response['data']
            ];
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$buckets];
        return $return;
    }

    /**
     * 创建空间
     *
     * @param String $bucket 空间名称
     * @param String $region 区域，默认滑动  z0 华东 z1 华北   z2 华南   na0 北美  as0 东南亚
     * @return array
     */
    public function createBucket($bucket, $region='z0'){
        $err = $this->subBucketManager->createBucket($bucket, $region);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 删除空间
     *
     * @param String $bucket 空间名称
     * @return array
     */
    public function dropBucket($bucket){
        $err = $this->subBucketManager->dropBucket($bucket);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 设置空间访问权限
     *
     * @param String $bucket 空间名称
     * @param integer $private 权限 0 公开 1 私有
     * @return array
     */
    public function setBucketAuth($bucket, $private){
        $err = $this->subBucketManager->setBucketAuth($bucket, $private);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 空间绑定域名
     *
     * @param $bucket
     * @return array
     */
    public function domains($bucket){
        list($domains, $err) = $this->bucketManager->domains($bucket);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$domains];
        return $return;
    }

    /**
     * 读取文件
     *
     * @param $bucket
     * @param int $limit
     * @param string $prefix
     * @param string $marker
     * @param string $delimiter
     * @return array
     */
    public function listFiles($bucket, $limit=1000, $prefix='', $marker='', $delimiter=''){
        list($ret, $err) = $this->bucketManager->listFiles($bucket, $prefix, $marker, $limit, $delimiter);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 读取文件信息
     *
     * @param $bucket
     * @param $key
     * @return array
     */
    public function stat($bucket, $key){
        list($fileInfo, $err) = $this->bucketManager->stat($bucket, $key);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$fileInfo];
        return $return;
    }

    /**
     * 批量读取文件信息
     *
     * @param $bucket
     * @param $keys
     * @return array
     */
    public function batchStat($bucket, $keys){
        $ops = $this->bucketManager->buildBatchStat($bucket, $keys);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 统计数量
     *
     * @param $bucket
     * @return array
     */
    public function count($bucket){
        $amount = 0;
        $flag = true;
        $marker = '';
        while ($flag){
            $response = $this->listFiles($bucket, 1000, '', $marker);
            if($response['error']){
                return $response;
            }
            $marker = isset($response['data']['marker']) ? $response['data']['marker'] : '';
            $items = $response['data']['items'];
            $amount += count($items);
            if(!$marker){
                $flag = false;
            }
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>['amount'=>$amount]];
        return $return;
    }

    /**
     * 文件上传Token
     *
     * @param $bucket
     * @param null $key
     * @param int $expires
     * @param null $policy
     * @param bool $strictPolicy
     * @return array
     */
    public function uploadToken($bucket, $key = null, $expires = 3600, $policy = null, $strictPolicy = true){
        $token = $this->auth->uploadToken($bucket, $key, $expires, $policy, $strictPolicy);
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$token];
        return $return;
    }

    /**
     * 上传文件
     *
     * @param $bucket
     * @param $filePath
     * @param null $key
     * @return array
     * @throws \Exception
     */
    public function upload($bucket, $filePath, $key = null){
        $response = $this->uploadToken($bucket, $key);
        if($response['error']){
            return $response;
        }
        $uploadToken = $response['data'];
        return $this->uploadWithToken($uploadToken, $filePath, $key);
    }

    /**
     * Token上传文件
     *
     * @param $uploadToken
     * @param $filePath
     * @param null $key
     * @return array
     * @throws \Exception
     */
    public function uploadWithToken($uploadToken, $filePath, $key = null){
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($uploadToken, $key, $filePath);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 移动文件
     *
     * @param $srcBucket
     * @param $srcKey
     * @param $destBucket
     * @param $destKey
     * @param bool $force
     * @return array|mixed
     * @throws \Exception
     */
    public function move($srcBucket, $srcKey, $destBucket, $destKey, $force=true){
        $err = $this->bucketManager->move($srcBucket, $srcKey, $destBucket, $destKey, $force);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 批量移动文件
     *
     * @param $srcBucket
     * @param $keys
     * @param $destBucket
     * @param string $prefix
     * @param string $suffix
     * @param bool $force
     * @return array
     */
    public function batchMove($srcBucket, $keys, $destBucket, $prefix='', $suffix='', $force=true){
        $keyPairs = array();
        foreach ($keys as $key) {
            $keyPairs[$key] = $prefix."_".$key."_".$suffix;
        }
        $ops = $this->bucketManager->buildBatchMove($srcBucket, $keyPairs, $destBucket, $force);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 复制文件
     *
     * @param $srcBucket
     * @param $srcKey
     * @param $destBucket
     * @param $destKey
     * @param bool $force
     * @return array|mixed
     * @throws \Exception
     */
    public function copy($srcBucket, $srcKey, $destBucket, $destKey, $force=true){
        $err = $this->bucketManager->copy($srcBucket, $srcKey, $destBucket, $destKey, $force);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 批量复制文件
     *
     * @param $srcBucket
     * @param $keys
     * @param $destBucket
     * @param string $prefix
     * @param string $suffix
     * @param bool $force
     * @return array
     */
    public function batchCopy($srcBucket, $keys, $destBucket, $prefix='', $suffix='', $force=true){
        $keyPairs = array();
        foreach ($keys as $key) {
            $keyPairs[$key] = $prefix."_".$key."_".$suffix;
        }
        $ops = $this->bucketManager->buildBatchCopy($srcBucket, $keyPairs, $destBucket, $force);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 修改文件存储类型
     *
     * @param $bucket
     * @param $key
     * @param $type 0 表示标准存储；1 表示低频存储。
     * @return array
     */
    public function changeType($bucket, $key, $type){
        if(!in_array($type, [0, 1])){
            $return = ['error' => true, 'message' => '文件存储类型错误', 'errorCode'=>-1];
            return $return;
        }
        $err = $this->bucketManager->changeType($bucket, $key, $type);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 批量修改文件存储类型
     *
     * @param $bucket
     * @param $keys
     * @param $type 0 表示标准存储；1 表示低频存储。
     * @return array
     */
    public function batchChangeType($bucket, $keys, $type){
        if(!in_array($type, [0, 1])){
            $return = ['error' => true, 'message' => '文件存储类型错误', 'errorCode'=>-1];
            return $return;
        }
        $keyTypePairs = array();
        foreach ($keys as $key) {
            $keyTypePairs[$key] = 1;
        }
        $ops = $this->bucketManager->buildBatchChangeType($bucket, $keyTypePairs);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 修改文件状态
     *
     * @param $bucket
     * @param $key
     * @param $status 0 表示启用；1 表示禁用。
     * @return array
     */
    public function changeStatus($bucket, $key, $status){
        if(!in_array($status, [0, 1])){
            $return = ['error' => true, 'message' => '文件状态错误', 'errorCode'=>-1];
            return $return;
        }
        $err = $this->bucketManager->changeStatus($bucket, $key, $status);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 修改文件MIME
     *
     * @param $bucket
     * @param $key
     * @param $mime
     * @return array
     */
    public function changeMime($bucket, $key, $mime){
        $err = $this->bucketManager->changeMime($bucket, $key, $mime);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 批量修改文件MIME
     *
     * @param $bucket
     * @param $keys
     * @param $mime
     * @return array
     */
    public function batchChangeMime($bucket, $keys, $mime){
        $keyMimePairs = array();
        foreach ($keys as $key) {
            $keyMimePairs[$key] = $mime;
        }
        $ops = $this->bucketManager->buildBatchChangeMime($bucket, $keyMimePairs);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 删除文件
     *
     * @param $bucket
     * @param $key
     * @return array
     */
    public function delete($bucket, $key){
        $err = $this->bucketManager->delete($bucket, $key);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 批量删除文件
     *
     * @param $bucket
     * @param $keys
     * @return array
     */
    public function batchDelete($bucket, $keys){
        $ops = $this->bucketManager->buildBatchDelete($bucket, $keys);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 指定天数后删除文件
     *
     * @param $bucket
     * @param $key
     * @param $days
     * @return array
     */
    public function deleteAfterDays($bucket, $key, $days){
        $err = $this->bucketManager->deleteAfterDays($bucket, $key, $days);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 指定天数后批量删除文件
     *
     * @param $bucket
     * @param $keys
     * @param $days 0表示永久存储
     * @return array
     */
    public function batchDeleteAfterDays($bucket, $keys, $days){
        $keyDayPairs = array();
        foreach ($keys as $key) {
            $keyDayPairs[$key] = $days;
        }
        $ops = $this->bucketManager->buildBatchDeleteAfterDays($bucket, $keyDayPairs);
        list($ret, $err) = $this->bucketManager->batch($ops);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 从指定Url抓取资源
     *
     * @param $bucket
     * @param $url
     * @param null $key
     * @return array
     */
    public function fetch($bucket, $url, $key = null){
        list($ret, $err) = $this->bucketManager->fetch($url, $bucket, $key);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$ret];
        return $return;
    }

    /**
     * 镜像资源更新
     *
     * @param $bucket
     * @param $key
     * @return array
     */
    public function prefetch($bucket, $key){
        $err = $this->bucketManager->prefetch($bucket, $key);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>null];
        return $return;
    }

    /**
     * 刷新文件或目录
     *
     * @param $urls
     * @param $dirs
     * @return array
     */
    public function refresh($urls, $dirs){
        if(is_array($urls) && is_array($dirs)){
            list($response) = $this->cdnManager->refreshUrlsAndDirs($urls, $dirs);
        }else if(is_array($urls)){
            list($response) = $this->cdnManager->refreshUrls($urls);
        }else if(is_array($dirs)){
            list($response) = $this->cdnManager->refreshDirs($dirs);
        }else{
            $return = ['error' => true, 'message' => '刷新Url或者目录不能都为空', 'errorCode'=>-1];
            return $return;
        }
        if($response['code'] != 200){
            $return = ['error' => true, 'message' => $response['error'], 'errorCode'=>$response['code']];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$response];
        return $return;
    }

    /**
     * 文件预取
     *
     * @param $urls
     * @return array
     */
    public function prefetchUrls($urls){
        list($response) = $this->cdnManager->prefetchUrls($urls);
        if($response['code'] != 200){
            $return = ['error' => true, 'message' => $response['error'], 'errorCode'=>$response['code']];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$response];
        return $return;
    }
}
