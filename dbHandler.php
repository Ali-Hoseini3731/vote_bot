<?php
$servername = "localhost";
$dbname = "fordir_calculator_bot";
$username = "fordir_fordir";
$password = "@A9408752h";
$conn = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "succeed";
} catch (PDOException $e) {
    echo $e->getMessage();
}