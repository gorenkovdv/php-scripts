<?php
require("./dbconnection.php");
require("./functions.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
    $userID = intval($_POST["userID"]);
    $rowID = intval($_POST["rowID"]);
    $inputData = $_POST["data"];
    $currentDatetime = date("Y-m-d H:i:s");

    $userFullname = "";
    $sql = "SELECT CONCAT_WS(' ', `lastname`, `firstname`, `middlename`) `fullname` FROM `users` WHERE `id` = '".$userID."'";
    $data["sql"][] = $sql;
    if($dbResult = $link->query($sql)):
        if($arResult = $dbResult->fetch_assoc()):
            $userFullname = $arResult["fullname"];
        endif;
    endif;

    $comment = ""; $cathedraChanges = ""; $instituteChanges = "";

    if(isset($inputData["comment"])) $comment = ", `Comment` = '".$inputData["comment"]."'";

    if(isset($inputData["cathedraAllow"])):
        $cathedraAllow = toBoolean($inputData["cathedraAllow"]) ? "1" : "0";
        $cathedraChanges = ", `CathedraAllow` = '".$cathedraAllow."', `CathedraComment` = '".$inputData["cathedraComment"]."', `CathedraEmployee` = '".$userFullname."',
        `CathedraCheck` = '".$currentDatetime."'";
    endif;

    if(isset($inputData["instituteAllow"])):
        $instituteAllow = toBoolean($inputData["instituteAllow"])? "1" : "0";
        $instituteChanges = ", `InstituteAllow` = '".$cathedraAllow."', `InstituteComment` = '".$inputData["instituteComment"]."', `InstituteEmployee` = '".$userFullname."',
        `InstituteCheck` = '".$currentDatetime."'";
    endif;
    
    $sql = "UPDATE `requests_listeners` SET `UserID` = '".$userID."'".$comment.$cathedraChanges.$instituteChanges." WHERE `ID` = '".$rowID."'";
    $data["sql"][] = $sql;
    if($link->query($sql)):
        $data["response"] = 1;
        $data["currentDatetime"] = $currentDatetime;
    else:
        $data["error"] = "Ошибка при выполнении запроса";
        $data["sqlerroor"] = $link->error;
    endif;

    $sql = "UPDATE `users` SET `workCheck` = '".$inputData["workCheck"]."' WHERE `id` = '".$userID."'";
    $data["sql"][] = $sql;
    if($link->query($sql)):
        $data["response"] = 1;
    else:
        $data["response"] = 0;
        $data["error"] = "Ошибка при выполнении запроса";
        $data["sqlerroor"] = $link->error;
    endif;

    foreach($_POST["data"]["documents"] as $document):
        $sql = "UPDATE `documents` SET `documentCheck` = '".$document["check"]."' WHERE `id` = '".$document["id"]."'";
        $data["sql"][] = $sql;

        if($link->query($sql)):
            $data["response"] = 1;
        else:
            $data["response"] = 0;
            $data["error"] = "Ошибка при выполнении запроса";
            $data["sqlerroor"] = $link->error;
        endif;
    endforeach;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;