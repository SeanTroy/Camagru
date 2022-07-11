<?php
require_once 'database.php';
// Create a PDO instance for connecting to MySQL server and set the error mode
$pdo = new PDO($DB_DSN_SETUP, $DB_USER, $DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS `camagru`";
$pdo->exec($sql);

// Select database
$sql = "USE `camagru`";
$pdo->exec($sql);

// Create the 'users' table
$sql = "CREATE TABLE IF NOT EXISTS `users` (
	`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`name` VARCHAR(255) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	`activation_code` VARCHAR(255) NOT NULL,
	`forgot_pw_code` INT(11),
	`notify_mail` ENUM('YES','NO') NOT NULL,
	`password` VARCHAR(255) NOT NULL
)";
$pdo->exec($sql);

// Create the 'images' table
$sql = "CREATE TABLE IF NOT EXISTS `images` (
	`image_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`user_id` INT(11) NOT NULL,
	`image_data` LONGTEXT NOT NULL,
	`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);

// Create the 'comments' table
$sql = "CREATE TABLE IF NOT EXISTS `comments` (
	`comment_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`image_id` INT(11) NOT NULL,
	`user_id` INT(11) NOT NULL,
	`comment` TEXT
)";
$pdo->exec($sql);

// Create the 'likes' table
$sql = "CREATE TABLE IF NOT EXISTS `likes` (
	`like_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`image_id` INT(11) NOT NULL,
	`user_id` INT(11) NOT NULL
)";
$pdo->exec($sql);

// Create the 'email_change' table
$sql = "CREATE TABLE IF NOT EXISTS `email_change` (
	`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`user_id` INT(11) NOT NULL,
	`new_email` VARCHAR(255) NOT NULL,
	`change_code` INT(11) NOT NULL
)";
$pdo->exec($sql);
