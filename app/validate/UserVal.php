<?php
namespace app\validate;
use think\Validate;

class UserVal extends Validate {
    // 定义针对某个字段的验证规则
    protected $rule =   [
        'username'  => 'require|min:5|max:20',
        'password'   => 'require|alphaNum|min:8',
        'email' => 'require|email',
        'age' => 'number|between:18,100'
    ];

    // 为相应字段的验证规则定义错误消息,变量名必须为$message
    protected $message  =   [
        'username.require' => '用户名必须填写.',
        'username.min'     => '用户名至少应该包含5字符',
        'username.max'     => '用户名最多不能超过20个字符',
        'age.number'   => '年龄必须是数字',
        'age.between'  => '年龄只能在18-100之间',
        'email'        => '你的邮箱格式错误',
    ];
}
