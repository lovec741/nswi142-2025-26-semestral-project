<?php

function isNullOrEmptyString(string|null $str){
    return $str === null || trim($str) === '';
}

function getUUID(): string {
	return bin2hex(random_bytes(16));
}
