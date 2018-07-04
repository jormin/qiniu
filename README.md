基于七牛官方SDK的扩展包

## 安装

``` bash
$ composer require jormin/qiniu -vvv
```

## 使用

### 生成七牛对象

``` php
$accessKey = 'your access key';
$secretKey = 'your secret key';
$qiniu = new \Jormin\Qiniu\Qiniu($accessKey, $secretKey);
```

### 空间域名相关功能

```php
// 获取空间列表
$qiniu->buckets();

// 获取指定空间的域名
$qiniu->domains($bucket);

// 创建空间 $region:区域，默认滑动  z0 华东 z1 华北   z2 华南   na0 北美  as0 东南亚
$qiniu->createBucket($bucket, [$region='z0']);

// 删除空间
$qiniu->dropBucket($bucket);

// 设置空间访问权限 $private:权限 0 公开 1 私有
$qiniu->setBucketAuth($bucket, $private);
```

### 文件操作相关功能

> 统计指定空间的文件总数功能慎用！！！！！官方并未提供获取总数的方法，该方法原理是分批次读取空间文件列表，直到没有剩余文件，由于读取列表接口限制单次最大数量为1000，所以空间文件数量巨大的情况下该方法效率很差！

```php
// 统计指定空间的文件总数
$qiniu->count($bucket);

// 读取指定空间的文件列表
$qiniu->listFiles($bucket, [$limit=1000, $prefix='', $marker='', $delimiter='']);

// 读取文件信息
$qiniu->stat($bucket, $key);

// 批量读取文件信息 $keys:数组
$qiniu->batchStat($bucket, $keys);

// 获取上传文件Token
$qiniu->uploadToken($bucket, [$key = null, $expires = 3600, $policy = null, $strictPolicy = true]);

// 上传文件（默认方法）
$qiniu->upload($bucket, $filePath, [$key = null]);

// 带Token上传文件
$qiniu->uploadWithToken($uploadToken, $filePath, [$key = null]);

// 移动文件
$qiniu->move($srcBucket, $srcKey, $destBucket, $destKey, [$force=true]);

// 批量移动文件 $keys:数组 $prefix:目标文件名前缀 $suffix:目标文件名后缀
$qiniu->batchMove($srcBucket, $keys, $destBucket, [$prefix='', $suffix='', $force=true]);

// 复制文件
$qiniu->copy($srcBucket, $srcKey, $destBucket, $destKey, [$force=true]);

// 批量复制文件 $keys:数组 $prefix:目标文件名前缀 $suffix:目标文件名后缀
$qiniu->batchCopy($srcBucket, $keys, $destBucket, [$prefix='', $suffix='', $force=true]);

// 修改文件存储类型 $type 0:标准存储；1:低频存储
$qiniu->changeType($bucket, $key, $type);

// 批量修改文件存储类型 $type 0:标准存储；1:低频存储 $keys:数组
$qiniu->batchChangeType($bucket, $keys, $type);

// 修改文件状态 $status 0:启用；1:禁用
$qiniu->changeStatus($bucket, $key, $status);

// 修改文件MIME
$qiniu->changeMime($bucket, $key, $mime);

// 批量修改文件MIME
$qiniu->batchChangeMime($bucket, $keys, $mime);

// 删除文件
$qiniu->delete($bucket, $key);

// 批量删除文件 $keys:数组
$qiniu->batchDelete($bucket, $keys);

// 指定天数后删除文件 $days:天数，0表示永久保存
$qiniu->deleteAfterDays($bucket, $key, $days);

// 指定天数后批量删除文件 $keys:数组 $days:天数，0表示永久保存
$qiniu->batchDeleteAfterDays($bucket, $keys, $days);

// 从指定Url抓取资源
$qiniu->fetch($bucket, $url, [$key = null]);
```

### CDN相关功能

```php
// 镜像资源更新
$qiniu->prefetch($bucket, $key);

// 刷新文件或目录 $urls:数组 $dirs:数组
$qiniu->refresh($urls, $dirs);

// 文件预取 $urls:数组
$qiniu->prefetchUrls($urls);
```


## 参考项目

1. [qiniu/php-sdk](https://github.com/qiniu/php-sdk)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
