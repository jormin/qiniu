基于七牛官方SDK的扩展包

## 安装

``` bash
$ composer require jormin/qiniu -vvv
```

## 使用

1. 生成七牛对象

``` php
$accessKey = 'your access key';
$secretKey = 'your secret key';
$qiniu = new \Jormin\Qiniu\Qiniu($accessKey, $secretKey);
```

2. 具体功能

> 统计指定空间的文件总数功能慎用！！！！！官方并未提供获取总数的方法，该方法原理是分批次读取空间文件列表，直到没有剩余文件，由于读取列表接口限制单次最大数量为1000，所以空间数量巨大的情况下该方法效率很差！

```php
// 获取空间列表
$qiniu->buckets();

// 获取指定空间的域名
$qiniu->domains($bucket);

// 统计指定空间的文件总数
$qiniu->count($bucket);

// 读取指定空间的文件列表
$qiniu->listFiles($bucket, [$limit=1000, $prefix='', $marker='', $delimiter='']);

// 读取指定文件的信息
$qiniu->fileInfo($bucket, $key);

// 获取上传文件Token
$qiniu->uploadToken($bucket, [$key = null, $expires = 3600, $policy = null, $strictPolicy = true]);

// 上传文件（默认方法）
$qiniu->upload($bucket, $filePath, [$key = null]);

// 带Token上传文件
$qiniu->uploadWithToken($uploadToken, $filePath, [$key = null]);

// 移动文件
$qiniu->move($srcBucket, $srcKey, $destBucket, $destKey, [$force=true]);

// 复制文件
$qiniu->copy($srcBucket, $srcKey, $destBucket, $destKey, [$force=true]);

// 修改文件存储类型 $type 0:标准存储；1:低频存储
$qiniu->changeFileType($bucket, $key, $type);

// 从指定Url抓取资源
$qiniu->fetch($bucket, $url, [$key = null]);

// 镜像资源更新
$qiniu->prefetch($bucket, $key);

// 刷新文件或目录
$qiniu->refresh($urls, $dirs);
```

## 参考项目

1. [qiniu/php-sdk](https://github.com/qiniu/php-sdk)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
