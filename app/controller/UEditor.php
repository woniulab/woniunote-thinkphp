<?php
namespace app\controller;
use app\BaseController;

class UEditor extends BaseController {
    # 根据UEditor的接口定义规则，如果前端参数为action=config，
    # 则表示试图请求后台的config.json文件，请求成功则说明后台接口能正常工作
    # 路由地址："/uedit"，请求类型为任意类型
    public function index() {
        $param = request()->param('action');
        $method = request()->method();
        // 获取config.json配置文件信息
        if ($method == 'GET' && $param == 'config') {
            $config = file_get_contents('../view/public/config.json');
            $config = preg_replace("/\/\*[\s\S]+?\*\//", "", $config, true);
            $config = json_decode($config);     // 生成PHP关联数组
            return json($config);   # 再以JSON格式返回
        }
        // 上传图片，图片后缀名的合法性UEditor已经完成了判断，不需要再进行判断
        else if ($method == 'POST' && $param = 'uploadimage') {
            // 上传图片, 获取表单上传文件名upfile（UEditor预定义好的名称）
            $file = request()->file('upfile');
            $filename = $file->getOriginalName();
            // 获取文件后缀名，并转换为小写
            $split = explode('.', $filename);
            $suffix = strtolower($split[count($split)-1]);
            // 上传到本地服务器，保存在public/upload目录下
            // 对图片进行压缩，直接将$file作为图片数据进行处理即可
            $newname = date('Ymd_His.') . $suffix;
            $dest = '../public/upload/' . $newname;
            compress_image($file, $dest, 1200);

            // 之前的直接上传并保存的代码可以注释掉，因为compress_image函数会保存
            /**
            默认保存在当前日期目录下的MD5文件名中
            也可以通过函数闭包自定义文件名，比如此处定义文件名为日期时间格式
            $savename = \think\facade\Filesystem::putFile( '/', $file, function (){
                return date('Ymd_His');    // 定义文件名格式
            }); */

            // 构建满足UEditor响应格式的JSON数据
            $result = array();
            $result['state'] = 'SUCCESS';
            $result['url'] = '/upload/' . $newname;
            $result['title'] = $filename;
            $result['original'] = $filename;
            return json($result);
        }
        // 列出upload目录下的图片，以便于在线选择已经上传过的图片
        else if ($method == 'GET' && $param == 'listimage') {
            $list = array();
            // 使用glob函数遍历upload目录下的所有文件
            // 获取到的文件路径类似于：../public/upload/20200220_023754.jpg
            $filelist = glob('../public/upload/*');
            foreach ($filelist as $path) {
                // 获取到文件列表中的文件名（去掉路径前缀）
                // 使用 / 来分隔文件路径以构建一个数组
                $split = explode('/', $path);
                // 从数组中获取最后一个值即为文件名
                $filename = $split[count($split)-1];
                // 拼接上URL地址前缀即可构建一个完整的图片URL
                $url = '/upload/' . $filename;
                $list[] = ['url'=>$url];
            }
            $result = array();
            $result['state'] = 'SUCCESS';
            $result['list'] = $list;
            $result['start'] = 0;
            $result['total'] = 20;
            return json($result);
        }
    }

    // 路由地址："/uedit/test",请求类型为Get
    public function test() {
        // 测试上述函数
        $content = '<p style="text-align:left;text-indent:28px">
            <span style="font-size:14px;font-family:宋体">文章编辑完成后当然就得发布文章，某种意义上来说就是一个请求而已。但是要优化好整个发布功能，其实要考虑的问题是很多的。</span></p>
            <p><img src="/upload/20200222_173320.PNG" title="image.png" alt="image.png"/></p>
            <p><span style="font-size:14px;font-family:宋体">首先要解决的问题是图片压缩的问题，作者发布文章时，并不会去关注图片有多大，只是简单的上传上去前端能正常显示即可。</span></p>
            <p><img src="http://www.woniuxy.com/page/img/banner/newBank.jpg"/></p>
            <p><span style="font-size:14px;font-family:宋体">图片压缩分两种压缩方式，一种是压缩图片的尺寸，另外一种是压缩图片的大小。</span><img src="http://ww1.sinaimg.cn/large/68b02e3bgy1g2rzifbr5fj215n0kg1c3.jpg"/>
            </p>';

        $urls= parse_image_url($content);
        if (count($urls) == 0) {
            return "文章没有图片，根据类别指定缩略图.";
        }
        else {
            $thumbname = generate_thumb($urls);
            return $thumbname;
        }
    }

    public function sms() {
        send_sms();
    }

    // 文件上传测试，路由地址为："/doupload"，请求类型Post
    public function doUpload() {
        $headline = request()->post('headline');
        $content = request()->post('content');
        $file = request()->file('upfile');

        // 取得文件的后缀名以判断是否合法
        $list = array('jpg', 'jpeg', 'png', 'rar', 'zip', 'doc', 'docx');
        if (in_array(strtolower($file->getOriginalExtension()), $list)) {
            $savename = \think\facade\Filesystem::putFile('/', $file, function () {
                return date('Ymd_His');    // 定义文件名格式
            });
            return 'Done';
        }
        else {
            return 'Invalid';
        }
    }
}