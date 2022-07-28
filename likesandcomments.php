<?php

session_start();
require_once 'config/newpdo.php';

/* modifying likes */

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

if (isset($_SESSION['user_id']) && $_POST['action'] === 'del_comment' && isset($_POST['comment_id']) && isset($_POST['image_id'])) {

	/* function to get the comments for an image */

	function showComments($image_id, $pdo)
	{
		$sql = "SELECT * FROM `comments`
		LEFT JOIN `users` ON `comments`.`user_id` = `users`.`id`
		WHERE `comments`.`image_id` = ?";

		$stmt = $pdo->prepare($sql);
		$stmt->execute([$image_id]);

		$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($comments as $key => $text) {
			echo "<div class=comment>";
			echo $text['name'] . ": " . $text['comment'] . " ";
			if ($text['name'] === $_SESSION['loggued_on_user'] && $text['comment'] != "[user deleted this comment]") {
				echo '<img class="del_comment" alt="Delete" title="Delete comment" src="icons/delete.png" onclick="deleteComment(' . $text['comment_id'] . ', ' . $image_id . ')">';
			}
			echo "<br></div>";
		}
	}

	/* delete selected comment, double checking user owns it */

	$sql = "SELECT `user_id` FROM `comments` WHERE `comment_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST['comment_id']]);
	$comment_owner_id = $stmt->fetchColumn();
	if ($comment_owner_id == $_SESSION['user_id']) {
		$replacement = "[user deleted this comment]";
		$sql = "UPDATE `comments` SET `comment` = ? WHERE `comment_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$replacement, $_POST['comment_id']]);
	}
	showComments($_POST['image_id'], $pdo);
}
