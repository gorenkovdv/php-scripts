<?php
function savePdfFile($path, $data){
	$base64_data = str_replace("data:application/pdf;base64,", "", $data);
	file_put_contents($path, base64_decode($base64_data));
}