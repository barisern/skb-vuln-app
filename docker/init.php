<?php
require_once '/var/www/vendor/autoload.php';

use App\Config\Initializer;

$initializer = new Initializer();
$initializer->initialize(); 