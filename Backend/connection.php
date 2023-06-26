<?php
$servername = "localhost";
$username = "root";
$password = "sagar";

$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

?>