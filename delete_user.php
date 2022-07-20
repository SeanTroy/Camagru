<?php
require_once 'config/newpdo.php';
session_start();

if (isset($_SESSION['user_id'])) {
	$sql = "DELETE FROM users WHERE id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_SESSION['user_id']]);

	$sql = "DELETE FROM images WHERE user_id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_SESSION['user_id']]);

	$sql = "DELETE FROM comments WHERE user_id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_SESSION['user_id']]);

	$sql = "DELETE FROM likes WHERE user_id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_SESSION['user_id']]);

	$sql = "DELETE FROM email_change WHERE user_id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_SESSION['user_id']]);

	session_unset();
	header("Location: login.php");
}
