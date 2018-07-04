<?php

namespace Jormin\Qiniu;

use Qiniu\Auth;
use Qiniu\Cdn\CdnManager;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

/**
 * 七牛
 *
 * @package Jormin\Qiniu
 */
class Qiniu{

    private $accessKey, $secretKey;

    public $auth, $bucketManager, $cdnManager;

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
     * 读取文件的Metadata信息
     *
     * @param $bucket
     * @param $key
     * @return array
     */
    public function fileInfo($bucket, $key){
        list($fileInfo, $err) = $this->bucketManager->stat($bucket, $key);
        if($err){
            $return = ['error' => true, 'message' => $err->message(), 'errorCode'=>$err->code()];
            return $return;
        }
        $return = ['error' => false, 'message' => '操作成功', 'data'=>$fileInfo];
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
     * 修改文件存储类型
     *
     * @param $bucket
     * @param $key
     * @param $type 0 表示标准存储；1 表示低频存储。
     * @return array
     */
    public function changeFileType($bucket, $key, $type){
        if(!in_array($type, [0, 1])){
            $return = ['error' => true, 'message' => '文件存储类型错误', 'errorCode'=>-1];
            return $return;
        }
        list($ret, $err) = $this->bucketManager->changeType($bucket, $key, $type);
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
}
