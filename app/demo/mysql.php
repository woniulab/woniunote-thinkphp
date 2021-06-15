<?php
$host = "127.0.0.1";
$port = 3306;
$username = "qiang";
$password = "123456";
$dbname = "woniunote";

// 创建连接，使用面向对象方式
$conn = new mysqli($host, $username, $password, $dbname, $port);

// 检测连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}
echo "连接成功\n";

$sql = "select * from users";
$result = $conn->query($sql);
// 获取结果集的行数
echo "本次共查询到: $result->num_rows 条记录\n";
// 以索引数组一次性取出所有结果
$rows = $result->fetch_all();
print_r($rows);   // 打印数组的值
foreach ($rows as $row) {
    print("$row[0], $row[1], $row[2] \n");
}

// 也可以按行遍历，并使用fetch_assoc返回关联数组
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    echo "用户编号: " . $row['userid'] . ", 用户名称: " . $row['username'] . "\n";
}

// 另外，在执行带条件的SQL语句时，也可以使用预处理语句
$sql = "select userid, username, password from users where userid=?";
$stmt = $conn->prepare($sql);
$userid = 1;
// 将SQL语句的查询条件参数绑定到变量$userid中，并且设置为整型
// d为双精度浮点型，s为字符串型，b为二进制型（不常用）
$stmt->bind_param("i", $userid);
$stmt->bind_result($uid, $username, $password);
$stmt->execute();
while ($stmt->fetch()) {
    echo "\n用户编号: " . $uid . ", 用户名称: " . $username . "\n";
}

// 使用标准SQL语句插入一条数据
$password = md5('123456');
// 如果当前时间不正确，通常是因为时区设置的原因，请在php.ini配置文件中
// 设置date.timezone=PRC，并重启Apache即可
$createtime = $updatetime = date('Y-m-d H:i:s');
$sql = "insert into users(
            username, password, nickname, avatar, qq, role, createtime, updatetime)
        values ('reader@woniuxy.com', '$password', '读者', '1.png', 
            '12345678', 'user', '$createtime', '$updatetime')";
$result = $conn->query($sql);
if ($result == 1) {
    echo '插入数据成功.\n';
}
else {
    echo '插入数据失败.\n';
}

// 同样的，也可以使用预处理SQL语句来插入数据，代码可读性更强，执行效率更高
$username = 'reader@woniuxy.com';
$password = md5('123456');
$nickname = '读者';
$avatar = '1.png';
$qq = '12345678';
$role = 'user';
$createtime = $updatetime = date('Y-m-d H:i:s');
$sql = "insert into users(
            username, password, nickname, avatar, qq, role, createtime, updatetime)
        values (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $username, $password, $nickname,
    $avatar, $qq, $role, $createtime, $updatetime);
$result = $stmt->execute();     // 非select语句不需要bind_result
if ($result == 1) {
    echo '插入数据成功.\n';
}
else {
    echo '插入数据失败.\n';
}

// Update和Delete
$sql = "update users set avatar='2.png' where username='reader@woniuxy.com'";
$conn->query($sql);
$sql = "delete from users where username='reader@woniuxy.com'";
$conn->query($sql);