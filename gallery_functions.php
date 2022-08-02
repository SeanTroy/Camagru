<?php

/* setting up the pagination of images */

if (isset($_GET['page'])) {
	$page = $_GET['page'];
} else {
	$page = 1;
}

if (isset($_GET['paginate'])) {
	$images_per_page = $_GET['paginate'];
} else {
	$images_per_page = 10;
}

$offset = ($page - 1) * $images_per_page;

$sql = "SELECT * FROM `images`";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$total_images = $stmt->rowCount();
$total_pages = CEIL($total_images / $images_per_page);
if (!$total_pages)
	$total_pages = 1;

/* check the current users profile picture */

if (isset($_SESSION['user_id'])) {
	try {
		$sql = "SELECT image_id FROM profile_pics WHERE `user_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['user_id']]);
		$profile_pict_id = $stmt->fetchColumn();
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

	return $likes_amount . "<br>";
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
		if (($text['name'] === $_SESSION['loggued_on_user'] && $text['comment'] != "[user deleted this comment]")) {
			echo '<img class="del_comment" alt="Delete" title="Delete comment" src="icons/delete.png" onclick="deleteComment(' . $text['comment_id'] . ', ' . $image_id . ')">';
		}
		echo "<br></div>";
	}
}

/* deletion of images, making sure that user owns the picture */

if ($_POST['action'] == "delete" && isset($_SESSION['user_id']) && isset($_POST['image_id'])) {
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
	header("Location: gallery.php?page=" . $page . "&paginate=" . $images_per_page, true, 303); /* prevents page refresh from sending the data again */
}

/* displaying the required images for the page */

$sql = "SELECT `image_id`, `user_id`, `image_data`, `name`,
		FROM_UNIXTIME(UNIX_TIMESTAMP(`time`), '%d.%m.%Y %H:%i:%s') AS 'time'
		FROM `images`
		LEFT JOIN `users` ON `images`.`user_id` = `users`.`id`
		ORDER BY `image_id` DESC
		LIMIT $offset, $images_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
