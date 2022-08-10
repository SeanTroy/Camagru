<?php
require_once 'config/newpdo.php';

session_start();

$warning_message = "";
$success_message = "";

if (isset($_GET['user']) && isset($_GET['code'])) {

	$sql = "SELECT * FROM `users` WHERE `name` LIKE ? AND `activation_code` LIKE ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_GET['user'], $_GET['code']]);

	$user = $stmt->fetch();

	if ($user) {
		$sql = "UPDATE `users` SET `activation_code` = 666 WHERE `name` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_GET['user']]);

		$success_message = "New user successfully validated! Please log in to start using Camagru!";
	}
}

function auth($pdo, $login, $passwd)
{
	$sql = "SELECT * FROM `users` WHERE `name` LIKE ? AND `activation_code` LIKE 666";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$login]);
	if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($user["password"] == hash('whirlpool', $passwd)) {
			return (TRUE);
		}
	}
	return (FALSE);
}

if (isset($_POST["login"]) && isset($_POST["passwd"]) && auth($pdo, $_POST["login"], $_POST["passwd"])) {
	$_SESSION["loggued_on_user"] = $_POST["login"];
	$sql = "SELECT `id` FROM `users` WHERE `name` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST["login"]]);
	$_SESSION["user_id"] = $stmt->fetch(PDO::FETCH_COLUMN);
	header("Location: profile.php");
} else if (isset($_POST["login"]) && isset($_POST["passwd"])) {
	$warning_message = "Wrong user or password!";
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
			<br>
			<h3>LOGIN HERE TO ENTER CAMAGRU</h3>
			<form name="login" action="login.php" method="post" class="profile_form">
				Username: <input type="text" name="login" value="" required />
				<br />
				Password: <input type="password" name="passwd" value="" required />
				<br>
				<input type="submit" name="submit" value="OK" />
			</form>
			<a href="password_reset.php"><button>Forgot Password</button></a>
			<hr id="divider_line">
			<h3>NOT SIGNED UP YET?</h3>
			<a href="signup.php"><button>Create new user account</button></a>
			<p id="warning_message"><?= $warning_message ?></p>
			<p id="success_message"><?= $success_message ?></p>
		</div>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

</html>