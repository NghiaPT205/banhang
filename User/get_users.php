<?php
$conn = new mysqli("localhost","root","","qlbanhang");

$sql = "
SELECT 
  user_id as id,
  username,
  full_name as fullname,
  status,
  CASE role_id
    WHEN 1 THEN 'Admin'
    WHEN 2 THEN 'Nhân viên'
    ELSE 'Khách hàng'
  END as role
FROM users
";

$result = $conn->query($sql);

$data = [];

while($r = $result->fetch_assoc()){
  $data[] = $r;
}

echo json_encode($data);
?>
