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

/* function to check if the owner of the image has notifications allowed */

function checkImageNotification($image_id, $sender_id, $pdo)
{
	try {
		$sql = "SELECT * FROM `images`
				INNER JOIN `users` ON `images`.`user_id` = `users`.`id`
				WHERE `images`.`image_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$image_id]);

		$image_owner = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($image_owner['notify_mail'] == "YES" && $sender_id != $image_owner['id']) {
			return $image_owner['email'];
		} else
			return FALSE;
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}
}

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

/* add a new comment and send mail if required */

if (isset($_SESSION['user_id']) && isset($_POST['comment']) && isset($_POST['comment_img_id'])) {

	if ($_POST['comment'] !== "" && strlen($_POST['comment']) <= 160) {

		$comment = filter_var($_POST['comment'], FILTER_SANITIZE_SPECIAL_CHARS); /* prevents input of html code */
		$sql = "INSERT INTO `comments` (`image_id`, `user_id`, `comment`) VALUES (?, ?, ?)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['comment_img_id'], $_SESSION['user_id'], $comment]);

		if ($email = checkImageNotification($_POST['comment_img_id'], $_SESSION['user_id'], $pdo)) {
			$message = "Someone commented on your picture!" . "\n" . "\n" .
				$_SESSION['loggued_on_user'] . " wrote the following comment in your picture:" . "\n" . "\n" .
				"'" . $_POST['comment'] . "'" . "\n" . "\n" .
				"You can see all the comments in the Camagru gallery: https://camagru.pekkalehtikangas.fi/gallery.php" . "\n";
			$headers = 'From: camagru.admin@hive.fi' . "\r\n" .
				'Reply-To: camagru.admin@hive.fi' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
			mail($email, 'You have a comment in Camagru!', $message, $headers);
		}
	}
	showComments($_POST['comment_img_id'], $pdo);
}

/* delete selected comment, double checking user owns it */

if (isset($_SESSION['user_id']) && isset($_POST['action']) && $_POST['action'] === 'del_comment' && isset($_POST['comment_id']) && isset($_POST['image_id'])) {

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
