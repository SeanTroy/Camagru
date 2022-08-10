<?php
session_start();

require_once 'config/newpdo.php';

$warning_message = "";
$success_message = "";
$tempuser = "";

function sendPasswordEmail($email, $user, $code)
{
	$message = "Hello! You requested to reset a forgotten password!" . "\n" . "\n" .
		"Please click on the following link to create a new password:" . "\n" . "\n" .
		"https://camagru.pekkalehtikangas.fi/password_reset.php?user=$user&code=$code" . "\n";
	$headers = 'From: camagru.admin@hive.fi' . "\r\n" .
		'Reply-To: camagru.admin@hive.fi' . "\r\n" .
		'X-Mailer: PHP/' . phpversion();
	mail($email, 'Password Reset Email', $message, $headers);
}

if (isset($_GET['user']) && isset($_GET['code'])) {

	$sql = "SELECT * FROM `users` WHERE `name` LIKE ? AND `forgot_pw_code` LIKE ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_GET['user'], $_GET['code']]);

	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($user) {
		$tempuser = $user['name'];
	}
}

if (isset($_POST['submit']) && $_POST['submit'] == "OK" && isset($_POST["login"])) {

	if ($_POST['resetpw'] === $_POST['confirm_resetpw']) {

		$sql = "UPDATE `users` SET `password` = ?, `forgot_pw_code` = ? WHERE `name` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([hash('whirlpool', $_POST['resetpw']), 0, $_POST["login"]]);

		$success_message = "Password successfully changed! Please log in.";
		header("Location: login.php");
	} else {
		$warning_message = "The entered passwords are not the same!";
		$tempuser = $_POST["login"];
	}
}

if (isset($_POST['submit']) && $_POST['submit'] == "Send e-mail") {

	$code = rand(100000, 999999);

	if (isset($_POST['login']) && $_POST['login'] != "") {

		$sql = "SELECT `email` FROM `users` WHERE `name` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['login']]);

		$email = $stmt->fetch(PDO::FETCH_COLUMN);
		$success_message = "If this username or e-mail exists in the database, an e-mail has been sent. " .
			"Please check your inbox for a link to reset your password.";

		if ($email) {
			$sql = "UPDATE `users` SET `forgot_pw_code` = ? WHERE `name` = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$code, $_POST['login']]);

			sendPasswordEmail($email, $_POST['login'], $code);
		}
	} else if (isset($_POST['email']) && $_POST['email'] != "") {

		$sql = "SELECT `name` FROM `users` WHERE `email` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['email']]);

		$user = $stmt->fetch(PDO::FETCH_COLUMN);
		$success_message = "If this username or e-mail exists in the database, an e-mail has been sent. " .
			"Please check your inbox for a link to reset your password.";

		if ($user) {
			$sql = "UPDATE `users` SET `forgot_pw_code` = ? WHERE `email` = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$code, $_POST['email']]);

			sendPasswordEmail($_POST['email'], $user, $code);
		}
	} else {
		$warning_message = "You need to enter either the username or e-mail address.";
	}
}
?>

<html>

<head>
	<?php include_once 'elements/header.html'; ?>
</head>

<body>
	<div class="page-wrap">
		<?php include 'elements/topbar.html'; ?>
		<?php if ($tempuser !== "") : ?>
			<div class="profile_container">
				<h3>PLEASE ENTER A NEW PASSWORD</h3>
				<form name="reset" action="password_reset.php" method="post" class="profile_form">
					<input type="text" name="login" value="<?= $tempuser ?>" hidden />
					New password: <input type="password" name="resetpw" value="" required />
					<br />
					Confirm password: <input type="password" name="confirm_resetpw" value="" required />
					<br />
					<input type="submit" name="submit" value="OK" />
				</form>
				<p id="warning_message"><?= $warning_message ?></p>
				<p id="success_message"><?= $success_message ?></p>
			</div>
		<?php else : ?>
			<div class="profile_container">
				<h3>PLEASE ENTER YOUR USERNAME OR E-MAIL TO RESET PASSWORD</h3>
				<form name="user_info" action="password_reset.php" method="post" class="profile_form">
					Username: <input type="text" name="login" autocomplete="off" value="" />
					<br />
					E-mail: <input type="email" name="email" autocomplete="off" value="" />
					<br />
					<input type="submit" name="submit" value="Send e-mail" />
				</form>
				<p id="warning_message"><?= $warning_message ?></p>
				<p id="success_message"><?= $success_message ?></p>
			</div>
		<?php endif; ?>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

</html>