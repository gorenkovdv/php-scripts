<?php

function toBoolean($value){
	return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function formatData($format, $data){
	if(is_null($data)) return null;

	return date($format, strtotime($data));
}