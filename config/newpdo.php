<?php
require_once 'database.php';
// Create a PDO instance for connecting to MySQL server and set the error mode
$pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>