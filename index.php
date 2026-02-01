<?php
$conn = new mysqli("localhost","root","","qlbanhang");
$conn->set_charset("utf8");


/* ================= API ================= */

if(isset($_GET["action"])){

    /* ===== LIST ===== */
    if($_GET["action"]=="list"){
        $sql="
        SELECT u.user_id,u.username,u.full_name,u.status,
               r.role_name,u.role_id
        FROM users u
        LEFT JOIN roles r ON u.role_id=r.role_id
        ORDER BY u.user_id DESC";

        $rs=$conn->query($sql);

        $data=[];
        while($r=$rs->fetch_assoc()) $data[]=$r;

        echo json_encode($data);
        exit;
    }


    /* ===== DELETE ===== */
    if($_GET["action"]=="delete"){
        $id=$_GET["id"];
        $conn->query("DELETE FROM users WHERE user_id=$id");
        exit;
    }


    /* ===== SAVE (THÊM / SỬA) ===== */
    if($_GET["action"]=="save"){

        $id=$_POST["id"];
        $username=$_POST["username"];
        $password=$_POST["password"];
        $fullname=$_POST["fullname"];
        $role_id=$_POST["role_id"];
        $status=$_POST["status"];


        /* ===== THÊM ===== */
        if($id==""){

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $conn->query("
            INSERT INTO users(username,password,full_name,role_id,status)
            VALUES('$username','$hash','$fullname','$role_id','$status')
            ");
        }

        /* ===== SỬA ===== */
        else{

            if($password!=""){
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $conn->query("
                UPDATE users SET
                username='$username',
                password='$hash',
                full_name='$fullname',
                role_id='$role_id',
                status='$status'
                WHERE user_id=$id
                ");
            }
            else{
                $conn->query("
                UPDATE users SET
                username='$username',
                full_name='$fullname',
                role_id='$role_id',
                status='$status'
                WHERE user_id=$id
                ");
            }
        }

        exit;
    }
}
?>

<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Quản lý người dùng</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#f6f8fa}
.table th{background:#e9ecef;text-align:center}
td{vertical-align:middle!important}
</style>
</head>


<body class="p-4">

<div class="container">

<h3 class="mb-3">👤 Quản lý người dùng</h3>

<button id="btnAdd" class="btn btn-success mb-3">+ Thêm người dùng</button>

<input id="searchInput" class="form-control mb-3"
placeholder="🔍 Tìm theo tên đăng nhập, họ tên, vai trò...">


<table class="table table-bordered table-hover text-center">

<thead>
<tr>
<th>Tên đăng nhập</th>
<th>Họ và tên</th>
<th>Vai trò</th>
<th>Trạng thái</th>
<th width="160">Thao tác</th>
</tr>
</thead>

<tbody id="tbody"></tbody>
</table>

<ul class="pagination justify-content-center" id="pagination"></ul>

</div>



<!-- ================= MODAL ================= -->

<div class="modal fade" id="m">
<div class="modal-dialog">
<div class="modal-content p-3">

<h5 class="mb-3">Thông tin người dùng</h5>

<input type="hidden" id="id">

<input id="username" class="form-control mb-2" placeholder="Tên đăng nhập">

<!-- ⭐ THÊM PASSWORD -->
<input id="password" type="password" class="form-control mb-2"
placeholder="Mật khẩu (để trống nếu không đổi)">

<input id="fullname" class="form-control mb-2" placeholder="Họ và tên">

<select id="role_id" class="form-control mb-2">
<option value="1">Quản trị</option>
<option value="2">Nhân viên</option>
<option value="3">Khách hàng</option>
</select>

<select id="status" class="form-control mb-3">
<option value="1">Hoạt động</option>
<option value="0">Ngưng hoạt động</option>
</select>

<button id="save" class="btn btn-primary w-100">💾 Lưu</button>

</div>
</div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
const PER_PAGE = 3;

let users=[];
let currentPage=1;

const tbody=document.getElementById("tbody");
const pagination=document.getElementById("pagination");
const searchInput=document.getElementById("searchInput");

const btnAdd=document.getElementById("btnAdd");
const save=document.getElementById("save");

const id=document.getElementById("id");
const username=document.getElementById("username");
const password=document.getElementById("password");
const fullname=document.getElementById("fullname");
const role_id=document.getElementById("role_id");
const status=document.getElementById("status");

const modal=new bootstrap.Modal(m);



/* ===== LOAD ===== */

async function load(){
 const r=await fetch("?action=list");
 users=await r.json();
 render(searchInput.value);
}



/* ===== RENDER ===== */

function render(keyword=""){

 const k=keyword.toLowerCase();

 const filtered=users.filter(u =>
   u.username.toLowerCase().includes(k) ||
   u.full_name.toLowerCase().includes(k) ||
   (u.role_name||"").toLowerCase().includes(k)
 );

 const total=Math.ceil(filtered.length/PER_PAGE)||1;

 if(currentPage>total) currentPage=total;

 const start=(currentPage-1)*PER_PAGE;
 const pageData=filtered.slice(start,start+PER_PAGE);


 tbody.innerHTML=pageData.map(u=>`
<tr>
<td>${u.username}</td>
<td>${u.full_name}</td>
<td>${u.role_name||""}</td>
<td>
${u.status==1
 ? '<span class="badge bg-success">Hoạt động</span>'
 : '<span class="badge bg-danger">Ngưng</span>'}
</td>
<td>
<button class="btn btn-warning btn-sm" onclick="edit(${u.user_id})">Sửa</button>
<button class="btn btn-danger btn-sm" onclick="del(${u.user_id})">Xóa</button>
</td>
</tr>
`).join("");


 pagination.innerHTML="";
 for(let i=1;i<=total;i++){
   pagination.innerHTML+=`
   <li class="page-item ${i===currentPage?'active':''}">
   <button class="page-link" onclick="go(${i})">${i}</button>
   </li>`;
 }
}


function go(p){
 currentPage=p;
 render(searchInput.value);
}

searchInput.oninput=()=>{
 currentPage=1;
 render(searchInput.value);
};



/* ===== CRUD ===== */

btnAdd.onclick=()=>{
 id.value="";
 username.value="";
 password.value="";
 fullname.value="";
 role_id.value=1;
 status.value=1;
 modal.show();
};



save.onclick=async ()=>{

 const f=new FormData();

 f.append("id",id.value);
 f.append("username",username.value);
 f.append("password",password.value);
 f.append("fullname",fullname.value);
 f.append("role_id",role_id.value);
 f.append("status",status.value);

 await fetch("?action=save",{method:"POST",body:f});

 modal.hide();
 load();
};



async function del(i){
 if(!confirm("Bạn có chắc muốn xóa người dùng này?")) return;
 await fetch("?action=delete&id="+i);
 load();
}



function edit(i){
 const u=users.find(x=>x.user_id==i);

 id.value=u.user_id;
 username.value=u.username;
 password.value="";
 fullname.value=u.full_name;
 role_id.value=u.role_id;
 status.value=u.status;

 modal.show();
}



load();
</script>

</body>
</html>
