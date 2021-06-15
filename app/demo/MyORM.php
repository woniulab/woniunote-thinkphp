<?php
//namespace app\demo;
//use mysqli;
//
//class MyORM {
//    private $conn = null;   // 定义数据库连接对象
//    public $table_name = '';    // 定义表名table_name
//
//    public $username = '';  // 定义列名username
//    public $password = '';  // 定义列名password
//    public $role = '';      // 定义列名role
//    public $createtime = '';    // 定义列名createtime
//
//    // 在实例化对象时直接创建数据库连接，并指定表名
//    public function __construct($table_name) {
//        $conn = new mysqli('127.0.0.1', 'qiang', '123456', 'woniunote');
//        if ($conn->connect_error) {
//            die("连接失败: " . $conn->connect_error);
//        }
//        $this->table_name = $table_name;
//        $this->conn = $conn;
//    }
//
//    // 利用已有字段拼接insert语句，并执行insert操作
//    public function insert() {
//         $sql = "insert into $this->table_name (username, password, role, createtime) values('$this->username', '$this->password', '$this->role', '$this->createtime')";
//         $this->conn->query($sql);
//    }
//
//    // 利用已有字段拼接select语句，并带where查询条件
//    public function select($where) {
//        $sql = "select * from $this->table_name where $where";
//        $result = $this->conn->query($sql)->fetch_all();
//        return $result;
//    }
//}
//
//// 实例化MyORM对象对指定表名
//$orm = new MyORM('users');
//// 利用类属性赋值方式为各列赋值
//$orm->username = 'orm@woniuxy.com';
//$orm->password = md5('123456');
//$orm->role = 'user';
//$orm->createtime = '2020-03-12 12:45:28';
//$orm->insert();
//
//// 执行select查询语句，并打印结果
//print_r($orm->select('userid=1'));








// ====================== 这是改进版本  ===================== //

namespace app\demo;
use mysqli;
use ReflectionClass;

class MyORM
{
    private $conn = null;   // 定义数据库连接对象
    private $table_name = '';    // 定义表名table_name

    private $order = '';    // 指定排序方式，默认不带order
    private $where = '1=1';   // 指定过滤条件，默认不带条件
    private $field = '*';   // 指定查询列名，默认所有列

    // 在实例化对象时直接创建数据库连接，并指定表名
    public function __construct($table_name)
    {
        $conn = new mysqli('127.0.0.1', 'qiang', '123456', 'woniunote');
        if ($conn->connect_error) {
            die("连接失败: " . $conn->connect_error);
        }
        $this->table_name = $table_name;
        $this->conn = $conn;
    }

    // 利用链式操作为order赋值，即赋值后返回类实例全身
    public function order($order) {
        $this->order = $order;
        return $this;
    }

    // 利用链式操作为where赋值，默认为1=1表示无查询条件
    public function where($where) {
        $this->where = $where;
        return $this;
    }

    // 利用链式操作指定查询的字段名，默认为*，查询所有列
    public function field($field) {
        $this->field = $field;
        return $this;
    }

    // 删除get_object_vars获取到的所有类属性里面预先定义好的属性
    // 这样返回的就只是在调用ORM模型方法调用代码设置的动态属性
    public function get_column_value() {
        $columns = get_object_vars($this);
        unset($columns['conn']);
        unset($columns['table_name']);
        unset($columns['order']);
        unset($columns['where']);
        unset($columns['field']);
        return $columns;
    }

    // 利用已有字段拼接insert语句，并执行insert操作
    public function insert() {
        $sql = "insert into $this->table_name (";  // 按insert语法先拼接第一部分
        $columns = $this->get_column_value();
        // 使用implode函数通过逗号来拼接属性数组中的key作为列名
        $sql .= implode(',', array_keys($columns));
        $sql .= ") values('";    // 注意values的值需要前后都加单引号
        // 继续拼接insert语句的values部分内容
        $sql .= implode("','", $columns);   // 注意分隔符为 ','
        $sql .= "')";   // 最后的单引号也要加上
        print($sql);    // 可以在此处打印拼接的SQL语句是否正确，便于调试
        $this->conn->query($sql);
    }

    // 利用已有字段拼接select语句，必须在链式条件中放到最后执行
    public function select() {
        $sql = "select $this->field from $this->table_name where $this->where ";
        if ($this->order != '') {
            $sql .= 'order by ' . $this->order;
        }
        print($sql);    // 可以在此处打印拼接的SQL语句是否正确，便于调试
        // $result = $this->conn->query($sql)->fetch_all();

        // 也可以使用fetch_assoc按行获取并返回关联数组
        $result = array();
        $cursor = $this->conn->query($sql);
        while ($row = $cursor->fetch_assoc()) {
            $result[] = $row;
        }
        return $result;
    }
}

// 实例化MyORM对象对指定表名
$orm = new MyORM('users');

// 利用动态属性赋值方式为各列赋值，并插入新数据
$orm->username = 'orm@woniuxy.com';
$orm->password = md5('123456');
$orm->role = 'user';
$orm->createtime = '2020-03-12 12:45:28';
$orm->insert();

// 执行select查询语句，并通过链式操作设定查询条件，并打印结果
//$result = $orm->field('username, password, nickname')
//    ->where("userid < 5")->order('userid desc')
//    ->select();
//print_r($result);