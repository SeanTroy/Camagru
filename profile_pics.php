<?php
session_start();

require_once 'config/newpdo.php';

if (isset($_SESSION['user_id'])) {

	if (isset($_POST['setpic'])) {
		$sql = "SELECT `user_id` FROM `images` WHERE `image_id` = ?"; /* double check that user owns the image */
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['setpic']]);
		$image_owner_id = $stmt->fetchColumn();

		if ($image_owner_id === $_SESSION['user_id']) {

			$sql = "SELECT * FROM profile_pics WHERE `user_id` = ?"; /* see if user already has a profile picture */
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$_SESSION['user_id']]);
			$entry = $stmt->fetch();

			if ($entry) {
				if ($entry['image_id'] == $_POST['setpic']) {
					$sql = "DELETE FROM profile_pics WHERE `user_id` = ?";
					$stmt = $pdo->prepare($sql);
					$stmt->execute([$_SESSION['user_id']]);
				} else {
					$sql = "UPDATE profile_pics SET image_id = ? WHERE `user_id` = ?";
					$stmt = $pdo->prepare($sql);
					$stmt->execute([$_POST['setpic'], $_SESSION['user_id']]);
				}
			} else {
				$sql = "INSERT INTO profile_pics (image_id, `user_id`) VALUES (?,?)";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([$_POST['setpic'], $_SESSION['user_id']]);
			}
		}
	}

	try {
		$sql = "SELECT * FROM profile_pics
			INNER JOIN images ON profile_pics.image_id = images.image_id
			WHERE profile_pics.user_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['user_id']]);
		$profile_pic = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}

	if ($profile_pic) {
		echo "data:image/jpeg;base64," . $profile_pic['image_data'];
	} else {
		echo "icons/background.jpg";
	}
}
