<?php
session_start();
require_once 'config/newpdo.php';

if (isset($_SESSION['user_id']) && isset($_POST['new_image']) && isset($_POST['sticker'])) {
// Create image instances

$image_url = $_POST['new_image'];
$image_url = preg_replace('/^data:image\/jpeg;base64,/', '', $image_url);
$image_url = str_replace(' ', '+', $image_url);
$image = base64_decode($image_url);

$sticker_values = explode(',', $_POST['sticker']);
$sticker = file_get_contents('stickers/' . $sticker_values[0]);
$h_offset = $sticker_values[1];
$v_offset = $sticker_values[2];
$width = $sticker_values[3];
$height = $sticker_values[4];

$image = imagecreatefromstring($image);
$sticker = imagecreatefromstring($sticker);
$sticker = imagescale($sticker, $width, $height);

// Copy and merge
imagecopy($image, $sticker, $h_offset, $v_offset, 0, 0, $width, $height);

ob_start();
imagejpeg($image);
$image = ob_get_clean();

$image = base64_encode($image);

$sql = "INSERT INTO `images` (`user_id`, `image_data`) VALUES (?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $image]);

echo "data:image/jpeg;base64," . $image;

}	else {
	echo "ERROR!";
}
?>
