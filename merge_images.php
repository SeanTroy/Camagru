<?php
session_start();
require_once 'config/newpdo.php';

if (isset($_SESSION['user_id']) && isset($_POST['new_image']) && isset($_POST['sticker'])) {
	// Create image instances

	$image_url = $_POST['new_image'];
	$image_url = preg_replace('/^data:image\/jpeg;base64,/', '', $image_url);
	$image_url = str_replace(' ', '+', $image_url);
	$image = base64_decode($image_url);
	$image = imagecreatefromstring($image);

	if ($image) {
		if ($_POST['uploaded'] == "N")
			imageflip($image, IMG_FLIP_HORIZONTAL);

		$sticker_values = explode(',', $_POST['sticker']);
		$i = 0;
		while ($sticker_values[$i] !== "") {
			$sticker = file_get_contents('stickers/' . $sticker_values[$i]);
			$h_offset = $sticker_values[$i + 1];
			$v_offset = $sticker_values[$i + 2];
			$width = $sticker_values[$i + 3];
			$height = $sticker_values[$i + 4];

			$sticker = imagecreatefromstring($sticker);
			$sticker = imagescale($sticker, $width, $height);

			imagecopy($image, $sticker, $h_offset, $v_offset, 0, 0, $width, $height);
			$i += 5;
		}

		ob_start();
		imagejpeg($image);
		$image = ob_get_clean();

		$image = base64_encode($image);

		/* string size tests for both PHP 8 and earlier */
		if (substr($image, 512000, 1) === "" || !substr($image, 512000, 1)) {
			$sql = "INSERT INTO `images` (`user_id`, `image_data`) VALUES (?, ?)";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$_SESSION['user_id'], $image]);

			echo "data:image/jpeg;base64," . $image;
		}
	}
}
