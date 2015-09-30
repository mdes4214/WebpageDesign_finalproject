<?php
if (count($_POST)) {
    extract($_POST, EXTR_OVERWRITE);
} else {
    if (count($_GET)) extract($_GET, EXTR_OVERWRITE);
}
if (isset($_SESSION) && count($_SESSION)>0) {
    extract($_SESSION, EXTR_OVERWRITE);
}
if (count($_SERVER) && isset($_SERVER['PHP_SELF'])) {
    $PHP_SELF = $_SERVER['PHP_SELF'];
}
$UserIP = '';
if (isset($_SERVER['HTTP_VIA'])) $UserIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
else if (isset($_SERVER['REMOTE_ADDR'])) $UserIP = $_SERVER['REMOTE_ADDR'];
?>