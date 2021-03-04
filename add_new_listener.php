<?php
require("./dbconnection.php");
require("./functions.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
    foreach($_POST as $key => $value):
		$_POST[$key] = htmlspecialchars(trim($value));
    endforeach;

    if(isset($_POST["birthdate"])): $birthdate = formatDate("Y-m-d", $_POST["birthdate"]); endif;
    if(isset($_POST["snils"]) && strlen($_POST["snils"]) > 0): $snils = $_POST["snils"]; endif;
    
    $snilsCondition = ""; $birthdateCondition = "";
    if(isset($snils)): $snilsCondition = "AND `snils` = '".$snils."'"; endif;
    if(isset($birthdate)): $birthdateCondition = " AND `birthdate` = '".$birthdate."'"; endif;

    $sql = "SELECT * FROM `users` WHERE `lastname` = '".$_POST["lastname"]."' AND `firstname` = '".$_POST["firstname"]."'
        AND `middlename` = '".$_POST["middlename"]."' ".$birthdateCondition." ".$snilsCondition;
    $data["sql"][] = $sql;
    if($result = $link->query($sql)):
        $data["num_rows"] = $result->num_rows;
        if($result->num_rows > 0):
            $data["error"] = "Пользователь уже существует";
        else:
            /* Поиск логина в LDAP */
            $url = "https://accounts.asmu.local/api/public/users/search?lastname=".$_POST["lastname"]."&firstname=".$_POST["firstname"]."&middlename=".$_POST["middlename"];
            if(isset($birthdate)): $url .= "&birthdate=".$birthdate; endif;
            if(isset($snils)): $url .= "&snils=".$snils; endif;
            $data["url"] = $url;
            $jsonUsers = curl_get($url);
            $arUsers = json_decode($jsonUsers, true);

            $data["usersLDAP"] = $arUsers;
            if(count($arUsers) > 0):
                $username = $arUsers[0]["name"];
                $data["usernameLDAP"] = $username;
            endif;

            $birthdateInsert = ""; if($birthdate): $birthdateInsert = ", `birthdate` = '".$birthdate."'"; endif;
            $snilsInsert = ""; if($snils): $snilsInsert = ", `snils` = '".$snils."'"; endif;
            $sql = "INSERT INTO `users` SET `lastname` = '".$_POST["lastname"]."', `firstname` = '".$_POST["firstname"]."',
            `middlename` = '".$_POST["middlename"]."'".$birthdateInsert." ".$snilsInsert;
            if(isset($username)) $sql .= " , `username` = '".$username."'";
            $data["sql"][] = $sql;

            $data["response"] = 1;

            if($link->query($sql)):
                $data["response"] = 1;
                $data["user"] = array(
                    "id" => $link->insert_id,
                    "name" => $_POST["lastname"]." ".$_POST["firstname"]." ".$_POST["middlename"],
					"login" => null
                );
            else:
                $data["error"] = "Ошибка выполнения SQL-запроса";
                $data["sqlerror"] = $link->error;
            endif;

        endif;
    else:
        $data["error"] = "Ошибка при выполнении SQL-запроса";
        $data["sqlerror"] = $link->error;
    endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;