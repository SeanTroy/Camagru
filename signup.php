<?php
require_once 'config/newpdo.php';

$warning_message = "";
$success_message = "";

function confirmPassword($passwd, $confirm_passwd)
{
	if ($passwd === $confirm_passwd)
		return TRUE;
	else
		return FALSE;
}

function checkNewUser($user, $pdo) {
	$sql = "SELECT `name` FROM `users` WHERE `name` LIKE ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$user]);

	if ($stmt->fetch(PDO::FETCH_COLUMN)) {
		return FALSE;
	}
	return TRUE;
}

function checkNewEmail($email, $pdo) {
	$sql = "SELECT `email` FROM `users` WHERE `email` LIKE ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$email]);

	if ($stmt->fetch(PDO::FETCH_COLUMN)) {
		return FALSE;
	}
	return TRUE;
}

function sendConfirmationEmail($email, $user, $code)
{
	$message = "Hello! Welcome to Camagru!" . "\n" . "\n" .
		"Please click on the following link to activate your account:" . "\n" . "\n" .
		"http://localhost:8080/09_Camagru/login.php?user=$user&code=$code" . "\n";
	$headers = 'From: camagru.admin@hive.fi' . "\r\n" .
		'Reply-To: camagru.admin@hive.fi' . "\r\n" .
		'X-Mailer: PHP/' . phpversion();
	mail($email, 'Confirmation Email', $message, $headers);
}

$warning_message = "";

if (isset($_POST["submit"]) && $_POST["submit"] == "OK") {

	if (strlen($_POST["login"]) > 25) {
		$warning_message = "Username is too long. Maximum length is 25 characters.";
	} else if (!preg_match('/^[a-z0-9]+$/i', $_POST["login"])) {
		$warning_message = "Username should only include characters (a-z or A-Z) and numbers (0-9).";
	} else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$warning_message = "Please enter a valid e-mail address!";
	} else if (!confirmPassword($_POST["passwd"], $_POST['confirm_passwd'])) {
		$warning_message = "The entered passwords are not the same!";
	} else if (!(checkNewUser($_POST["login"], $pdo))) {
		$warning_message = "Sorry, that username has already been reserved.";
	} else if (!(checkNewEmail($_POST["email"], $pdo))) {
		$warning_message = "An account with this e-mail address already exists.";
	} else if (!preg_match('/(?=^.{8,30}$)(?=.*\d)(?=.*[!@#$%^&*]+)(?=.*[A-Z])(?=.*[a-z]).*$/', $_POST["passwd"])) {
		$msg1 = "PLEASE ENTER A PASSWORD WITH: <br>";
		$msg2 = "- a length between 8 and 30 characters <br>";
		$msg3 = "- at least one lowercase character (a-z) <br>";
		$msg4 = "- at least one uppercase character (A-Z) <br>";
		$msg5 = "- at least one numeric character (0-9) <br>";
		$msg6 = "- at least one special character (!@#$%^&*)";
		$warning_message = $msg1 . $msg2 . $msg3 . $msg4 . $msg5 . $msg6;
	} else {
		$code = rand(100000, 999999);

		try {$sql = "INSERT INTO `users` (`name`, `email`, `password`, `activation_code`, `notify_mail`)
				VALUES (?, ?, ?, ?, ?)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_POST["login"], $_POST["email"], hash('whirlpool', $_POST["passwd"]), $code, 'YES']);

		sendConfirmationEmail($_POST['email'], $_POST['login'], $code);
		$success_message = "New user successfully created!" . PHP_EOL .
			"Please check your inbox for confirmation e-mail.";
		} catch (PDOException $e) {
			print("Error!: " . $e->getMessage() . "<br/>");
		}
	}
}
?>

<html>

<head>
	<?php include_once 'elements/header.html'; ?>
</head>

<body>
	<?php
	include 'elements/topbar.html';
	?>
	<div class="profile_container">
		<h3>CREATE NEW ACCOUNT HERE</h3>
		<form name="create" action="signup.php" method="post"  class="profile_form">
			Username: <input type="text" name="login" value="<?php if (isset($_POST['login'])){echo $_POST['login'];} ?>" autocomplete="off" required />
			<br />
			E-mail: <input type="email" name="email" value="<?php if (isset($_POST['email'])){echo $_POST['email'];} ?>" autocomplete="off" required />
			<br />
			Password: <input type="password" name="passwd" value="" required />
			<br />
			Confirm password: <input type="password" name="confirm_passwd" value="" required />
			<br />
			<input type="submit" name="submit" value="OK" />
			<p id="warning_message"><?=$warning_message?></p>
			<p id="success_message"><?=$success_message?></p>
		</form>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

</html>