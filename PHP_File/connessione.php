<?php
$host = "*your mySQL host*";
$username = "*your mySQL username*";
$passwd = "*your mySQL password*";
$dbname = "*your mySQL database name*";

$conn = mysqli_connect($host, $username, $passwd, $dbname);

if ($conn == false || $conn->connect_error) {
    print('NO CONNECTION');
    exit();
}
