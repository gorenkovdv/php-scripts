<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
    foreach($_POST as $key => $value):
		$_POST[$key] = htmlspecialchars(trim($value));
    endforeach;
    
    $snilsCondition = ""; $birthdateCondition = "";
    if(isset($_POST["snils"]) && strlen($_POST["snils"]) > 0):
        $snilsCondition = " OR `snils` = '".$_POST["snils"]."'";
    endif;

    if(isset($_POST["birthDate"])):
        $birthdateCondition = " AND `birthdate` = '".date("Y-m-d", strtotime($_POST["birthDate"]))."'";
    endif;

    $sql = "SELECT * FROM `users` WHERE `lastname` = '".$_POST["lastName"]."' AND `firstname` = '".$_POST["firstName"]."' AND `middlename` = '".$_POST["middleName"]."' ".$birthdateCondition." ".$snilsCondition;
    $data["sql"] = $sql;
    if($result = $link->query($sql)):
        if($result->num_rows > 0):
            $data["response"] = 1;
        else:
            /* Поиск в LDAP или создание нового пользователя */

            /*
            $sql = "INSERT INTO `users` SET `lastname` = '".$_POST["lastName"]."' AND `firstname` = '".$_POST["firstName"]."' AND `middlename` = '".$_POST["middleName"]."'";
            */
        endif;
    else:
        $data["error"] = "Ошибка при выполнении SQL-запроса";
        $data["sqlerror"] = $link->error;
    endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;