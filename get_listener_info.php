<?php

require("./dbconnection.php");
$data = array("get" => $_GET, "response" => 0, "sql" => array(), "documents" => array());

if($_SERVER["REQUEST_METHOD"] == "GET"):
    $uid = intval($_GET["uid"]);

    $sql = "SELECT CONCAT_WS(' ',`lastname`,`firstname`,`middlename`) AS `fullname`, `username`, `birthdate`, `phone`, `email`, `work`, `workCheck`
    FROM `users` WHERE `id` = '".$uid."'";
    $data["sql"][] = $sql;
    if($dbResult = $link->query($sql)):
        $arResult = $dbResult->fetch_assoc();
        if($arResult["work"] == "") $arResult["work"] = null;
        $arResult["lastUpdate"] = !is_null($arResult["last_update"]) ? date("d.m.Y H:i:s", strtotime($arResult["last_update"])) : $arResult["last_update"];

        $sql = "SELECT d.`id`, d.`type`, d.`level`, d.`organization`, d.`speciality`, d.`fullName`, d.`comment`,  d.`firstDate`, d.`secondDate`, d.`serial`, d.`hours`, d.`fileURL`, d.`documentCheck`,
        CASE WHEN d.`type` = 4 THEN et.`name` WHEN d.`type` = 5 THEN 'Сертификат специалиста' WHEN d.`type` = 6 THEN d.`documentName` END AS `name`,
        IF(d.`type` != 5, et.`firstDateName`, 'Дата комиссии') AS `firstDateName`, et.`secondDateName`
        FROM `documents` d LEFT JOIN `education_types` et ON et.`level` = d.`level` WHERE d.`uid` = '".$uid."'";
        $data["sql"][] = $sql;
        if($dbResult = $link->query($sql)):
            $data["documentsNum"] = $dbResult->num_rows;
            if($dbResult->num_rows > 0):
                while($arDocument = $dbResult->fetch_assoc()):
                    $data["documents"][] = $arDocument;
                endwhile;
            endif;
        endif;

        $data["response"] = 1;
        $data["info"] = $arResult;
    else:
        $data["error"] = "Ошибка при выполнении запроса";
        $data["sqlerror"] = $link->error;
    endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;