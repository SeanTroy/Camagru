<?php
session_start();

require_once 'config/newpdo.php';
require 'gallery_functions.php';

?>

<html>

<head>
	<?php include 'elements/header.html'; ?>
</head>

<body>
	<?php
	include 'elements/topbar.html';
	?>
	<div class="pagination">
		<a href="?page=1">First</a>
		<a href="<?php if ($page <= 1) : echo '#';
					else : echo "?page=" . ($page - 1);
					endif ?>">&laquo;</a>
		<text><?= $page . "/" . $total_pages; ?></text>
		<a href="<?php if ($page >= $total_pages) : echo '#';
					else : echo "?page=" . ($page + 1);
					endif ?>">&raquo;</a>
		<a href="?page=<?= $total_pages; ?>">Last</a>
	</div>
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
					<div class="delete_like_container">
						<div class="like_container">
							<?php if (!checkUserLikes($value['image_id'], $pdo)) : ?>
								<form id="gallery_form" name="like" action="gallery.php?page=<?= $page ?>" method="post">
									<input type="text" name="like_image_id" value="<?= $value['image_id']; ?>" hidden>
									<button type="submit" class="like_comment" name="submit" value="like">Like</button>
								</form>
							<?php else : ?>
								<form id="gallery_form" name="unlike" action="gallery.php?page=<?= $page ?>" method="post">
									<input type="text" name="unlike_image_id" value="<?= $value['image_id']; ?>" hidden>
									<button type="submit" name="submit" value="unlike">Unlike</button>
								</form>
							<?php endif ?>
							<?= getLikesAmount($value['image_id'], $pdo) ?>
						</div>
						<?php if ($value['user_id'] == $_SESSION['user_id']) { ?>
							<form id="gallery_form" name="delete" action="gallery.php?page=<?= $page ?>" method="post">
								<input type="text" name="image_id" value="<?= $value['image_id']; ?>" hidden>
								<button type="submit" id="delete_button" name="submit" value="delete" onclick="return confirm('Are you sure you want to delete this picture?')">Delete</button>
							</form>
						<?php } ?>
					</div>
					<figcaption>
						<?= showComments($value['image_id'], $pdo) ?>
					</figcaption>
				</figure>
				<form id="gallery_form" name="comment" action="gallery.php?page=<?= $page ?>" method="post">
					<input type="text" name="comment_img_id" value="<?= $value['image_id']; ?>" hidden>
					<input type="text" class="like_comment" name="comment" maxlength="160" autocomplete="off" required>
					<button type="submit" class="like_comment">Add comment</button>
				</form>
			</div>
		<?php } ?>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

<script>
	/* disable like and comment buttons if user not logged in */
	window.onload = function() {
		let userlogged = document.querySelector('.topuser_logout');

		if (!userlogged) {
			let buttons = document.getElementsByClassName("like_comment");
			for (let i = 0; i < buttons.length; i++) {
				buttons[i].disabled = true;
				buttons[i].innerHTML = "Login to like or comment";
			}
		}
	}
</script>

</html>