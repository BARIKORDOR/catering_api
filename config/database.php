<?php

$host = 'localhost';
$db   = 'catering_api';
$user = 'root';
$pass = '';
$port = "3307";
$options = [
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_EMULATE_PREPARES   => false,
];
$dsn = "mysql:host=$host;dbname=$db;port=$port";
$pdo = new \PDO($dsn, $user, $pass, $options);