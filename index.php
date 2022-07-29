<?php
require_once 'config/setup.php';
session_start();

/* getting the promo images for the page */

$sql = "SELECT `image_id`, `user_id`, `image_data`
		FROM `images`
		ORDER BY `image_id` DESC
		LIMIT 18";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$index_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<html>

<head>
	<?php include_once 'elements/header.html'; ?>
</head>

<body>
	<?php include 'elements/topbar.html'; ?>
	<div class="index_container">
		<?php
		foreach ($index_images as $key => $value) {
			$base64 = $value['image_data'];
			$image = "data:image/jpeg;base64," . $base64;
		?>
			<img class="index_picture" id="<?= $key; ?>" src="<?= $image; ?>" style="opacity: 0">
		<?php } ?>
		<h3 style="opacity: 0" id="18">Welcome to CAMAGRU!</h3>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

<script>
	window.onload = setTimeout(displayImage, 200);
	var count = 0;

	function displayImage() {
		if (count < 18)
			document.getElementById(count).style.opacity = "0.3";
		else
		document.getElementById(count).style.opacity = "1";
		count += 1;
		if (count < 19) {
			setTimeout(displayImage, 50);
		}
	}
</script>

</html>