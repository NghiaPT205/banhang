<?php
$conn = new mysqli("localhost","root","","qlbanhang");

$data = json_decode(file_get_contents("php://input"), true);

$username = $data["username"];
$password = $data["password"];
$fullname = $data["fullname"];
$role     = $data["role"];
$status   = $data["status"];

/* map role text -> role_id */
$role_id = 3;
if($role == "Admin") $role_id = 1;
if($role == "Nhân viên") $role_id = 2;

/* insert đúng cấu trúc bảng của bạn */
$sql = "INSERT INTO users(username,password,full_name,role_id,status,created_at)
        VALUES('$username','$password','$fullname',$role_id,'$status',NOW())";

$conn->query($sql);

echo json_encode(["ok"=>true]);
?>
