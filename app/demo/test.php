<?php

while (true) {
    $now = date('H:i');
    if ($now == '03:07') {
        // 如果当前时间为凌晨两点，则直接清空静态文件
        $list = scandir('../../view/statics');
        foreach ($list as $file) {
            if ($file != '.' && $file != '..') {
                unlink('../../view/statics/' . $file);
            }
        }
        $delete_time = date('Y-m-d H:i:s');
        print("于 $delete_time 清空了一次静态文件.\t");
        // 清空静态文件完成后，再发送Get请求生成一次静态文件，更新完成
        file_get_contents('http://127.0.0.1/statics/all');
    }
    sleep(60);  // 每1分钟判断一次是否当达凌晨2点
}