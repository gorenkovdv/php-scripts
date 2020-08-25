<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');

$host = "localhost";
$user = "root";
$password = "";
$database = "dbdpo";

$baseURL = "http://localhost:3000/";
$saveFilesPath = $_SERVER['DOCUMENT_ROOT']."/files/";

$link = new mysqli($host, $user, $password, $database) 
	or die("Соединение не удалось: ".$link->connect_error);