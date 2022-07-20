<?php

session_start();
require_once 'config/newpdo.php';

if (isset($_SESSION['user_id']) && isset($_POST['liked'])) {
	$sql = "SELECT * FROM `likes` WHERE `image_id` = ? AND `user_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST['liked'], $_SESSION['user_id']]);
	$userlike = $stmt->fetch();

	if ($userlike) {
		$sql = "DELETE FROM `likes` WHERE `image_id` = ? AND `user_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['liked'], $_SESSION['user_id']]);
	} else {
		$sql = "INSERT INTO `likes` (`image_id`, `user_id`) VALUES (?,?)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['liked'], $_SESSION['user_id']]);
		echo "Booked!";
	}
}
