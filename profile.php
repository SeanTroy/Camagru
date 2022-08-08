<?php
require_once 'config/newpdo.php';

session_start();

/* if user is not signed in, forward to login page */

if (!isset($_SESSION["loggued_on_user"])) {
	header("Location: login.php");
}

function userNotificationStatus($user_id, $pdo)
{
	try {
		$sql = "SELECT `notify_mail` FROM `users`
				WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$user_id]);

		$status = $stmt->fetch(PDO::FETCH_COLUMN);
		return $status;
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}
}

function userEmail($user_id, $pdo)
{
	try {
		$sql = "SELECT `email` FROM `users` WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$user_id]);

		$mail = $stmt->fetch(PDO::FETCH_COLUMN);
		return $mail;
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}
}

$notify_status = userNotificationStatus($_SESSION["user_id"], $pdo);
$user_email = userEmail($_SESSION["user_id"], $pdo);

if (isset($_POST['submit']) && $_POST['submit'] == "Change notifications") {
	try {
		if ($notify_status === "YES") {
			$sql = "UPDATE `users` SET `notify_mail` = 'NO' WHERE `id` = ?";
			$notify_status = "NO";
		} else {
			$sql = "UPDATE `users` SET `notify_mail` = 'YES' WHERE `id` = ?";
			$notify_status = "YES";
		}
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION["user_id"]]);
		header("Location: profile.php", true, 303); /* prevents page refresh from sending the data again */
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}
}

?>

<html>

<head>
	<?php include_once 'elements/header.html'; ?>
</head>

<body>
	<?php include 'elements/topbar.html'; ?>
	<div class="profile_container">
		<div class="profile_picture">
			<img id="profile_picture" src="icons/background.jpg">
		</div>
		Username: <?= $_SESSION["loggued_on_user"]; ?>
		<a href="logout.php"><button type="button">Logout</button></a>

		<form method="post" action="modif.php" class="profile_form">
			<input type="submit" name="submit" value="Change username" />
			<input type="submit" name="submit" value="Change password" />
			<br>
			E-mail: <?= $user_email; ?>
			<a href="modif.php"><input type="submit" name="submit" value="Change e-mail address" /></a>
		</form>

		<form method="post" action="profile.php" class="profile_form">
			E-mail notifications: <?= $notify_status; ?>
			<input type="submit" name="submit" value="Change notifications" />
		</form>
		<button type="button" id="delete_user" onclick=deleteUser()>Delete user</button>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

<script>
	window.onload = function() {
		let profile_picture = document.getElementById('profile_picture');

		let xml = new XMLHttpRequest();
		xml.open('post', 'profile_pics.php', true);
		xml.onload = function() {
			profile_picture.src = this.response;
			if (this.response != "icons/background_orig.jpg") {
				profile_picture.style="margin-left: -15%;";
			}
		}
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xml.send();
	}

	function deleteUser() {
		if (confirm("Are you sure you want to delete this user permanently? This will delete all your photos, comments etc.")) {
			window.location.href = "delete_user.php";
		}
	}
</script>

</html>