<?php

/* setting up the pagination of images */

if (isset($_GET['page'])) {
	$page = $_GET['page'];
} else {
	$page = 1;
}

$images_per_page = 10;
$offset = ($page - 1) * $images_per_page;

$sql = "SELECT * FROM `images`";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$total_images = $stmt->rowCount();
$total_pages = CEIL($total_images / $images_per_page);
if (!$total_pages)
	$total_pages = 1;

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
			echo "IT SHOULD SEND A MESSAGE!";
			return $image_owner['email'];
		} else
			return FALSE;
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}
}

/* function to check if user has liked certain image */

function checkUserLikes($image_id, $pdo)
{
	try {
		$sql = "SELECT * FROM `likes`
	LEFT JOIN `users` ON `likes`.`user_id` = `users`.`id`
	WHERE `likes`.`image_id` = ? AND `likes`.`user_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$image_id, $_SESSION['user_id']]);
		$liked = $stmt->fetch();
		if ($liked)
			return TRUE;
		else
			return FALSE;
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}
}

/* function to get the amount of likes for an image */

function getLikesAmount($image_id, $pdo)
{
	$sql = "SELECT * FROM `likes`
	LEFT JOIN `users` ON `likes`.`user_id` = `users`.`id`
	WHERE `likes`.`image_id` = ?";

	$stmt = $pdo->prepare($sql);
	$stmt->execute([$image_id]);

	$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$likes_amount = $stmt->rowCount();

	return "Likes: " . $likes_amount . "<br>";
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
		echo $text['name'] . ": " . $text['comment'] . "<br>";
	}
}

/* deletion of images, making sure that user owns the picture */

if ($_POST['submit'] == "delete" && isset($_SESSION['user_id']) && isset($_POST['image_id'])) {
	$sql = "SELECT `user_id` FROM `images` WHERE `image_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST['image_id']]);
	$image_owner_id = $stmt->fetchColumn();
	if ($image_owner_id == $_SESSION['user_id']) {
		$sql = "DELETE FROM `images` WHERE `image_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['image_id']]);
		$sql = "DELETE FROM `comments` WHERE `image_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['image_id']]);
	}
	if ($total_images % $images_per_page == 1)
		$page = $page - 1;
	header("Location: gallery.php?page=" . $page, true, 303); /* prevents page refresh from sending the data again */
}

/* inserting comments */

if (isset($_SESSION['user_id']) && isset($_POST['comment']) && isset($_POST['comment_img_id'])) {
	$comment = filter_var($_POST['comment'], FILTER_SANITIZE_SPECIAL_CHARS); /* prevents input of html code */
	$sql = "INSERT INTO `comments` (`image_id`, `user_id`, `comment`) VALUES (?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST['comment_img_id'], $_SESSION['user_id'], $comment]);
	if ($email = checkImageNotification($_POST['comment_img_id'], $_SESSION['user_id'], $pdo)) {
		$message = "Someone commented on your picture!" . "\n" . "\n" .
			$_SESSION['loggued_on_user'] . " wrote the following comment in your picture:" . "\n" . "\n" .
			$_POST['comment'] . "\n" . "\n" .
			"You can see all the comments in the Camagru gallery: http://localhost:8080/09_Camagru/gallery.php" . "\n";
		$headers = 'From: camagru.admin@hive.fi' . "\r\n" .
			'Reply-To: camagru.admin@hive.fi' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		mail($email, 'You have a comment in Camagru!', $message, $headers);
	}
	header("Location: gallery.php?page=" . $page, true, 303); /* prevents page refresh from sending the data again */
}

/* adding and deleting likes */

if ($_POST['submit'] == "like" && isset($_SESSION['user_id']) && isset($_POST['like_image_id'])) {
	$sql = "INSERT INTO `likes` (`image_id`, `user_id`) VALUES (?, ?)";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST['like_image_id'], $_SESSION['user_id']]);
	header("Location: gallery.php?page=" . $page, true, 303); /* prevents page refresh from sending the data again */
} else if ($_POST['submit'] == "unlike" && isset($_SESSION['user_id']) && isset($_POST['unlike_image_id'])) {
	$sql = "DELETE FROM `likes` WHERE `image_id` = ? AND `user_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST['unlike_image_id'], $_SESSION['user_id']]);
	header("Location: gallery.php?page=" . $page, true, 303); /* prevents page refresh from sending the data again */
}

/* displaying the required images for the page */

$sql = "SELECT `image_id`, `user_id`, `image_data`, `name`,
		FROM_UNIXTIME(UNIX_TIMESTAMP(`time`), '%d.%m.%Y %H:%i:%s') AS 'time'
		FROM `images`
		LEFT JOIN `users` ON `images`.`user_id` = `users`.`id`
		ORDER BY `image_id`
		LIMIT $offset, $images_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
