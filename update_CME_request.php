<?php

require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "POST"):
    $rowID = intval($_POST["rowID"]);
    $speciality = htmlspecialchars(trim($_POST["speciality"]));
    $number = htmlspecialchars(trim($_POST["number"]));

    $arRequestCME = array($speciality, $number);

    $jsonCME = json_encode($arRequestCME, JSON_UNESCAPED_UNICODE);

    $sql = "UPDATE `requests_listeners` SET `requestCME` = '".$jsonCME."' WHERE `ID` = '".$rowID."'";
    $data["sql"] = $sql;

    if($link->query($sql)):
        $data["response"] = 1;
    else:
        $data["error"] = "Ошибка при выполнении запроса";
        $data["sqlerror"] = $link->error;
    endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;