<?php
// 应用公共文件
use think\Image;

function compress_image($source, $dest, $size) {
    $image = Image::open($source);
    $width = $image->width();

    // 如果图片的宽度大于指定大小$size，则缩小并保存
    if ($width > $size) {
        $image->thumb($size, $size)->save($dest);
    }
    // 如果小于$size，则直接通过保存压缩质量
    else {
        $image->save($dest);
    }
}

// 解析文章内容中的图片地址
function parse_image_url($content) {
    preg_match_all('/<img src="(.+?)"/', $content, $match);
    $urls = array();
    foreach ($match[1] as $item) {
        // 如果图片类型为gif，则直接跳过，不对其作任何处理
        if (strpos(strtolower($item), '.gif') > 0)
            continue;
        $urls[] = $item;
    }
    return $urls;
}

// 远程下载指定URL地址的图片，并保存到临时目录中
function download_image($url, $path) {
    $image = file_get_contents($url);
    file_put_contents($path, $image);
}

// 解析列表中的图片URL地址并生成缩略图，返回缩略图名称
function generate_thumb($urls) {
    # 根据URL地址解析出其文件名和域名
    # 通常建议使用文章内容中的第一张图片来生成缩略图
    # 先遍历整个url_list，查找里面是否存在本地图片，找到即处理，代码结束
    foreach ($urls as $url) {
        if (preg_match('/^\/upload/', $url)) {
            // 获取本地图片文件名
            $split = explode('/', $url);
            $filename = $split[count($split) - 1];
            compress_image('../public' . $url, '../public/thumb/' . $filename, 400);
            return $filename;
        }
    }

    # 如果在内容中没有找到本地图片，则需要先将网络图片下载到本地再处理
    # 直接将第一张图片作为缩略图，并生成基于时间戳的标准文件名
    $url = $urls[0];
    // 获取URL地址中的图片文件名
    $split = explode('/', $url);
    $filename = $split[count($split) - 1];
    // 根据图片文件名获取后缀名
    $split = explode('.', $filename);
    $suffix = $split[count($split) - 1];
    $thumbname = date('Ymd_His.') . $suffix;
    download_image($url, '../public/download/' . $thumbname);
    compress_image('../public/download/' . $thumbname, '../public/thumb/' . $thumbname, 400);
    return $thumbname;
}

// 发送短信
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
function send_sms()
{
    // 请根据生成的AccessKey和AccessSecret字符串正确填写
    AlibabaCloud::accessKeyClient('<accessKeyId>', '<accessSecret>')
        ->regionId('cn-hangzhou')->asDefaultClient();
    try {
        $result = AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->scheme('https')   // https | http
            ->version('2017-05-25')
            ->action('SendSms')
            ->method('POST')
            ->host('dysmsapi.aliyuncs.com')
            ->options([
                'query' => [
                    'RegionId' => "cn-hangzhou",
                    'PhoneNumbers' => "18812345678",
                    'SignName' => "蜗牛学院",
                    'TemplateCode' => "SMS_184115860",
                    'TemplateParam' => "{'code':'368926'}",
                ],
            ])
            ->request();
        print_r($result->toArray());
    } catch (ClientException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    } catch (ServerException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    }
}