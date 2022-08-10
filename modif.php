<?php
require_once 'config/newpdo.php';

session_start();

/* if user is not signed in, forward to login page */

if (!isset($_SESSION["loggued_on_user"])) {
	header("Location: login.php");
}

$warning_message = "";
$success_message = "";

function confirmPassword($passwd, $confirm_passwd)
{
	if ($passwd === $confirm_passwd)
		return TRUE;
	else
		return FALSE;
}

function checkNewUser($user, $pdo)
{
	$sql = "SELECT `name` FROM `users` WHERE `name` LIKE ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$user]);

	if ($stmt->fetch(PDO::FETCH_COLUMN)) {
		return FALSE;
	}
	return TRUE;
}

function checkNewEmail($email, $pdo)
{
	$sql = "SELECT `email` FROM `users` WHERE `email` LIKE ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$email]);

	if ($stmt->fetch(PDO::FETCH_COLUMN)) {
		return FALSE;
	}
	return TRUE;
}

function sendChangeEmail($email, $code)
{
	$message = "Hello! You requested a change for your e-mail address!" . "\n" . "\n" .
		"Please confirm this new address by clicking on the following link:" . "\n" . "\n" .
		"https://camagru.pekkalehtikangas.fi/modif.php?mailchange=yes&code=$code" . "\n";
	$headers = 'From: camagru.admin@hive.fi' . "\r\n" .
		'Reply-To: camagru.admin@hive.fi' . "\r\n" .
		'X-Mailer: PHP/' . phpversion();
	mail($email, 'Email Change Confirmation', $message, $headers);
}

if (isset($_SESSION['user_id']) && isset($_GET['mailchange']) && isset($_GET['code'])) {

	try {
		$sql = "SELECT * FROM `email_change` WHERE `user_id` = ? AND `change_code` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['user_id'], $_GET['code']]);
		$changedata = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		print("Error!: " . $e->getMessage() . "<br/>");
	}

	if ($changedata) {
		$sql = "UPDATE `users` SET `email` = ? WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$changedata['new_email'], $_SESSION['user_id']]);

		$sql = "DELETE FROM `email_change` WHERE `user_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['user_id']]);

		$success_message = "Your e-mail address has been successfully changed!";
		header("Location: profile.php");
	}
}

if (isset($_SESSION['user_id']) && isset($_POST['new_email']) && isset($_POST["submit"]) && $_POST["submit"] == "Change e-mail address") {
	if (!filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL)) {
		$warning_message = "Please enter a valid e-mail address!";
	} else if (!(checkNewEmail($_POST["new_email"], $pdo))) {
		$warning_message = "An account with this e-mail address already exists.";
	} else {
		$code = rand(100000, 999999);

		$sql = "INSERT INTO `email_change` (`user_id`, `new_email`, `change_code`) VALUES (?, ?, ?)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['user_id'], $_POST['new_email'], $code]);

		sendChangeEmail($_POST['new_email'], $code);
		$success_message = "Please check your inbox for confirmation e-mail.";
	}
}

if (isset($_SESSION['user_id']) && isset($_POST['newuser']) && isset($_POST["submit"]) && $_POST["submit"] == "Change username") {
	if (strlen($_POST["newuser"]) > 25) {
		$warning_message = "Username is too long. Maximum length is 25 characters.";
	} else if (!preg_match('/^[a-z0-9]+$/i', $_POST["newuser"])) {
		$warning_message = "Username should only include characters (a-z or A-Z) and numbers (0-9).";
	} else if (!(checkNewUser($_POST["newuser"], $pdo))) {
		$warning_message = "Sorry, that username has already been reserved.";
	} else {
		$sql = "UPDATE `users` SET `name` = ? WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST['newuser'], $_SESSION['user_id']]);
		$_SESSION["loggued_on_user"] = $_POST["newuser"];
		$success_message = "Username successfully changed to: " . $_SESSION["loggued_on_user"];
	}
}

if (
	isset($_SESSION['user_id']) && isset($_SESSION['loggued_on_user']) && isset($_POST['newpw']) && isset($_POST['oldpw'])
	&& isset($_POST["submit"]) && $_POST["submit"] == "Change password"
) {
	if (!confirmPassword($_POST["newpw"], $_POST['confirm_newpw'])) {
		$warning_message = "The entered passwords are not the same!";
	} else if (!preg_match('/(?=^.{8,30}$)(?=.*\d)(?=.*[!@#$%^&*]+)(?=.*[A-Z])(?=.*[a-z]).*$/', $_POST["newpw"])) {
		$msg1 = "PLEASE ENTER A PASSWORD WITH:" . PHP_EOL;
		$msg2 = "- a length between 8 and 30 characters" . PHP_EOL;
		$msg3 = "- at least one lowercase character (a-z)" . PHP_EOL;
		$msg4 = "- at least one uppercase character (A-Z)" . PHP_EOL;
		$msg5 = "- at least one numeric character (0-9)" . PHP_EOL;
		$msg6 = "- at least one special character (!@#$%^&*)";
		$warning_message = $msg1 . $msg2 . $msg3 . $msg4 . $msg5 . $msg6;
	} else {
		$sql = "SELECT * FROM `users` WHERE `name` LIKE ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['loggued_on_user']]);
		if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($user["password"] == hash('whirlpool', $_POST["oldpw"])) {
				$sql = "UPDATE `users` SET `password` = ? WHERE `id` = ?";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([hash('whirlpool', $_POST["newpw"]), $_SESSION['user_id']]);
				$success_message = "PASSWORD CHANGED!";
			} else {
				$warning_message = "WRONG OLD PASSWORD!";
			}
		}
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
		<div class="profile_container">
			<?php if (isset($_POST["submit"]) && $_POST["submit"] == "Change username") : ?>
				<form name="username_modify" action="modif.php" method="post" class="profile_form">
					<h3>CHANGE USERNAME HERE</h3>
					New username: <input type="text" name="newuser" value="" autocomplete="off" required />
					<br />
					<input type="submit" name="submit" value="Change username" />
					<p id="warning_message"><?= $warning_message ?></p>
					<p id="success_message"><?= $success_message ?></p>
				</form>
			<?php elseif (isset($_POST["submit"]) && $_POST["submit"] == "Change password") : ?>
				<form name="password_modify" action="modif.php" method="post" class="profile_form">
					<h3>CHANGE PASSWORD HERE</h3>
					Old password: <input type="password" name="oldpw" value="" required />
					<br />
					New password: <input type="password" name="newpw" value="" required />
					<br />
					Confirm new password: <input type="password" name="confirm_newpw" value="" required />
					<br />
					<input type="submit" name="submit" value="Change password" />
					<p id="warning_message"><?= $warning_message ?></p>
					<p id="success_message"><?= $success_message ?></p>
				</form>
			<?php elseif (isset($_POST["submit"]) && $_POST["submit"] == "Change e-mail address") : ?>
				<form name="email_modify" action="modif.php" method="post" class="profile_form">
					<h3>CHANGE E-MAIL HERE</h3>
					Please enter a new e-mail address:
					<input type="email" name="new_email" value="" required />
					<br />
					<input type="submit" name="submit" value="Change e-mail address" />
					<p id="warning_message"><?= $warning_message ?></p>
					<p id="success_message"><?= $success_message ?></p>
				</form>
			<?php endif ?>
		</div>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

</html>