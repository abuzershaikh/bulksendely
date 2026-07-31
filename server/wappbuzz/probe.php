<?php
header('Content-Type: text/plain');
var_dump(extension_loaded('intl'));
var_dump(class_exists('Locale'));
var_dump(class_exists('\\Locale'));
