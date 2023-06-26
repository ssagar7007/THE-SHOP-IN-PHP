<?php
function isAdmin($email,$conn){
    $sql = "SELECT role from ecommerce.userdetail where email = '$email'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $role = $row['role'];
    }
    if($role == 1){
        return true;
    }
    else{
        return false;
    }
}
?>