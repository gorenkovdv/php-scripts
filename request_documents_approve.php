<?php

require("./dbconnection.php");

$data = array("get" => $_POST, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "POST"):
    $requestID = intval($_POST["requestID"]);
    $status = intval($_POST["status"]);

    $sql = "UPDATE `requests` SET `DocumentsApproved` = '".$status."' WHERE `ID` = '".$requestID."'";
    $data["sql"] = $sql;
    if($link->query($sql)):
        $data["response"] = 1;
    else:
        $data["error"] = "Ошибка при выполнении SQL-запроса";
        $data["sqlerror"] = $link->error;
    endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;