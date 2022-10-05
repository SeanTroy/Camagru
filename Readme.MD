## How to use

Online version, where you can sign up and test all the functionality, available [here](https://camagru.pekkalehtikangas.fi).

If you want to clone the git to your own computer:

- you need to install some development environment, like [Bitnami MAMP](https://bitnami.com/stack/mamp)
- after installing, clone the git inside the 'apache2/htdocs' folder in your install path:
```
git clone https://github.com/SeanTroy/Camagru.git Camagru
```
- modify the $DB_USER and $DB_PASSWORD variables in the file 'config/database.php', with the username and password you provided when installing MAMP
- start the MariaDB and Apache Web servers using 'manager-osx.app' in the MAMP root folder (Manage Servers -> Start All)
- then you can open the app in your browser, using address http://localhost:8080/Camagru

## How it was done

The goal of this project was to create a small photo sharing web app, with the following limitations on languages used: on the server side only PHP, and on the client side only plain HTML, CSS and Javascript. No external frameworks or libraries were allowed.

### MySQL database initialization

I started the project by creating the database and basic data tables for the project. In the file ‘config/setup.php’ I set up the SQL queries using the required PDO abstraction driver and the connection variables defined in ‘config/database.php’.

```php
require_once 'database.php';
$pdo = new PDO($DB_DSN_SETUP, $DB_USER, $DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "CREATE DATABASE IF NOT EXISTS `camagru`";
$pdo->exec($sql);
...
```

Four data tables were created at this stage, for ‘users’, ‘images’, ‘comments’ and ‘likes’.

In the config folder, I also created a file ‘newpdo.php’ to initialise a new PDO connection. This file is later required for all the php files, where MySQL connection is needed.

### Signup, login and logout

When a new user signs up to the app, they are first supposed to register with basic information, and then confirm their account via e-mail.

In ‘signup.php’, when the user inputs their credentials via login form, we first check that they are in the correct form and only then check that no such user already exists in the database.

I set as requirements that the username has to be only letters and numbers, and no longer than 25 characters. The password needs to be between 8 and 30 characters, and include lowercase, uppercase, numeric and special characters. E-mail validation is done by both the form input type and PHP’s ‘filter_var’ method.

If everything is fine, we create a random 6-digit activation code which we send to the user via e-mail (as part of a GET request in a link). That code is also saved to the database with user’s credentials and a hashed password.

{To send the e-mail using Bitnami MAMP, the php.ini file had to be altered for these Win32 settings: SMTP = [localhost](http://localhost) (disable), smtp_port = 25 (disable), and for Unix setting: sendmail_path = … (enable)}

```php
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

$code = rand(100000, 999999);

$sql = "INSERT INTO `users` (`name`, `email`, `password`, `activation_code`) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_POST["login"], $_POST["email"], hash('whirlpool', $_POST["passwd"]), $code]);

sendConfirmationEmail($_POST['email'], $_POST['login'], $code);
```

When the user clicks on the link in the e-mail, they are forwarded to the page ‘login.php’, where the values in the GET request are compared to the ones in the database. And if they match, their activation code in the database is set to value 666, which I decided is a sign of an activated user. After that, the user still has to login with their password.

In the normal login procedure, the username and password that the user enters (password after hashing, of course) are compared to the ones in the database. If there is a match, we save the username and user_id to $_SESSION variables.

```php
if ($_POST["login"] && $_POST["passwd"] && auth($pdo, $_POST["login"], $_POST["passwd"])) {
	$_SESSION["loggued_on_user"] = $_POST["login"];
	$sql = "SELECT `id` FROM `users` WHERE `name` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_POST["login"]]);
	$_SESSION["user_id"] = $stmt->fetch(PDO::FETCH_COLUMN);
} else if ($_POST["login"] && $_POST["passwd"]) {
	$warning_message = "Wrong user or password!";
}
```

In ‘logout.php’, the session is just unset, freeing all the session variables.

```php
<?php
session_start();
session_unset();
header('Location: login.php');
?>
```

### Webcam connection and saving the picture

Next, I wanted to make the connection to the webcam and save something to the ‘images’ table in the database.

For the webcam connection, I created the needed elements in the file ‘capture_image.php’ and used Javascript’s getUserMedia method to stream the webcam output to a video element. Then on the click of the camera button, the video frame is saved to a canvas.

```jsx
window.onload = async function() {
		let stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
		video.srcObject = stream;
	}

click_button.addEventListener('click', function() {
		canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
	});
```

All of the image processing was required to be done on the server side, so I turned the canvas into a data URL and used an AJAX query to send the picture data to PHP.

```jsx
save_button.addEventListener('click', function() {
		let image_data_url = canvas.toDataURL('image/jpeg');

		let xml = new XMLHttpRequest();
		xml.open('post', 'merge_images.php', true);
		xml.onload = function() {
			alert("Image saved to Gallery!");
		}
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xml.send('new_image='+image_data_url);
	});
```

In the file ‘merge_images.php’, the data header is stripped from the URL and the base64 encoded string is saved to the database.

```php
if (isset($_SESSION['user_id']) && isset($_POST['new_image']) {
		$image_url = $_POST['new_image'];
		$image_url = preg_replace('/^data:image\/jpeg;base64,/', '', $image_url);
		$image_url = str_replace(' ', '+', $image_url);

		$sql = "INSERT INTO `images` (`user_id`, `image_data`) VALUES (?, ?)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$_SESSION['user_id'], $image_url]);
	}
```

I also needed to make an option for the user to upload an image of their own. This is done by creating an input of type ‘file’ (which accepts jpeg and png files), and then using the createObjectURL method to save the uploaded file to a new instance of class Image. The ‘draw’ function then draws that image to the canvas, cropping the picture to the right format depending on which dimension is larger.

```jsx
const image_upload = document.getElementById("image-upload");

image_upload.addEventListener("change", function() {
	var img = new Image();
	img.src = URL.createObjectURL(this.files[0]);
	img.onload = draw;
});

function draw() {
	let widthRatio = this.width / canvas.width;
	let heightRatio = this.height / canvas.height;

	if (widthRatio < heightRatio) {
		canvas.getContext('2d').drawImage(this, 0, 0, this.width, this.width * 3 / 4, 0, 0, canvas.width, canvas.height);
	} else {
		canvas.getContext('2d').drawImage(this, 0, 0, this.height * 4 / 3, this.height, 0, 0, canvas.width, canvas.height);
	}
}
```

### Photo Gallery

The main part of the site is of course the photo gallery. I wanted the saved images to appear like polaroid pictures, with white frames, comments written below the picture and the bottom part of the frame growing in relation to the amount of comments. The functions needed to display the gallery are in a separate file ‘gallery_functions.php’.

The gallery was required to have pagination of the images, so I first set up a simple query to get the total amount of images and then count the required amount of total pages. Then on top of the gallery I created the navigation buttons, which use the GET request to change the pictures displayed on the page.

```html
<div class="pagination">
	<a href="?page=1">First</a>
	<a href="<?php if ($page == 1) : echo '#';
				else : echo "?page=" . ($page - 1);
				endif ?>">&laquo;</a>
	<text><?= $page . "/" . $total_pages; ?></text>
	<a href="<?php if ($page >= $total_pages) : echo '#';
				else : echo "?page=" . ($page + 1);
				endif ?>">&raquo;</a>
	<a href="?page=<?= $total_pages; ?>">Last</a>
</div>
```

To display only the required images we then fetch the data from the MySQL database as an associative array, limited by the wanted amount of images per page and the correct offset for every page. The actual username and the time when the photo was saved are also retrieved.

```php
$images_per_page = 10;
$offset = ($page - 1) * $images_per_page;
...
$sql = "SELECT `image_id`, `user_id`, `image_data`, `name`,
		FROM_UNIXTIME(UNIX_TIMESTAMP(`time`), '%d.%m.%Y %H:%i:%s') AS 'time'
		FROM `images`
		LEFT JOIN `users` ON `images`.`user_id` = `users`.`id`
		ORDER BY `image_id`
		LIMIT $offset, $images_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

Now that all the required data is saved in the variable $images, we just loop through the array creating all the wanted elements for each photo. First the ‘jpeg/base64’ header is attached to the base64 data, then username and creation time are displayed, the actual photo below that and then the user comments. The showComments function just gets all the comments for the current image_id, and echoes through them.

```php
<div class="gallery">
		<?php
		foreach ($images as $key => $value) {
			$base64 = $value['image_data'];
			$image = "data:image/jpeg;base64," . $base64;
		?>
			<div id="picture_with_buttons">
				<figure>
					<p id="image_user_tag"><?= $value['name']; ?> <?= $value['time']; ?></p>
					<img id="<?= $value['image_id']; ?>" src="<?= $image; ?>">
					...
					<figcaption>
						<?= showComments($value['image_id'], $pdo) ?>
					</figcaption>
				</figure>
				...
			</div>
		<?php } ?>
</div>
```
