<?php
require("./dbconnection.php");

$data = array("get" => $_GET, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "GET"):
    $value = htmlspecialchars(trim($_GET["value"]));
    $sql = "SELECT u.`id`, u.`username`, CONCAT_WS(' ', u.`lastname`, u.`firstname`, u.`middlename`) `fullname`
    FROM `users` u WHERE CONCAT_WS(' ', u.`lastname`, u.`firstname`, u.`middlename`) LIKE '".$value."%'";
    $data["sql"] = $sql;
    $listeners = array();
	if($dbResult = $link->query($sql)):
		$data["response"] = 1;
        if($dbResult->num_rows > 0):
            while($arResult = $dbResult->fetch_assoc()):
                $listeners[] = $arResult;
            endwhile;
        endif;
    else:
        $data["error"] = "Ошибка при выполнении запроса";
        $data["sqlerror"] = $link->error;
    endif;

    $data["listeners"] = $listeners;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;