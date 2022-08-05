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
	<div class="pagination_container">
		<div class="pagination">
			<a href=<?= "?page=1" . "&paginate=" . $images_per_page; ?>>First</a>
			<a href="<?php if ($page == 1) : echo '#';
						else : echo "?page=" . ($page - 1) . "&paginate=" . $images_per_page;
						endif ?>">&laquo;</a>
			<text><?= $page . "/" . $total_pages; ?></text>
			<a href="<?php if ($page >= $total_pages) : echo '#';
						else : echo "?page=" . ($page + 1) . "&paginate=" . $images_per_page;
						endif ?>">&raquo;</a>
			<a href="?page=<?= $total_pages . "&paginate=" . $images_per_page; ?>">Last</a><br>
		</div>
		<nav>
			<text id="photo_select">Photos per page:</text>
			<select id="photo_amount" onChange="window.location.href=this.value" name="categories" id="categories">
				<option value="?paginate=5">5</option>
				<option value="?paginate=10">10</option>
				<option value="?paginate=20">20</option>
				<option value="?paginate=50">50</option>
				<option value="?paginate=100">100</option>
			</select>
		</nav>
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
							<div class="heart_picture">
								<?php if (!checkUserLikes($value['image_id'], $pdo)) : ?>
									<img alt="Like" title="Like" id="heart<?= $value['image_id']; ?>" src="icons/heart_empty.png" onclick=changeLikes(this.id)>
								<?php else : ?>
									<img alt="Like" title="Like" id="heart<?= $value['image_id']; ?>" src="icons/heart_full.png" onclick=changeLikes(this.id)>
								<?php endif ?>
							</div>
							<p id="likes<?= $value['image_id']; ?>"><?= getLikesAmount($value['image_id'], $pdo) ?></p>
						</div>
						<?php if ($value['user_id'] == $_SESSION['user_id']) { ?>
							<form class="trash_and_profile" id="trash<?= $value['image_id']; ?>"
							action="gallery.php?page=<?= $page ?>&paginate=<?= $images_per_page ?>" method="post">
								<input type="text" name="image_id" value="<?= $value['image_id']; ?>" hidden>
								<input type="text" name="action" value="delete" hidden>
								<?php if ($profile_pict_id != $value['image_id']) : ?>
									<img class="profile_icon" alt="Make profile picture" title="Make profile picture" src="icons/profile.png" onclick="setProfile(this, <?= $value['image_id']; ?>)">
								<?php else : ?>
									<img id="profile_icon_select" alt="Remove profile picture" title="Remove profile picture" src="icons/profile_selected.png" onclick="setProfile(this, <?= $value['image_id']; ?>)">
								<?php endif ?>
								<img class="trash_icon" alt="Delete" title="Delete" src="icons/trashcan.png" onclick="deletePhoto(<?= $value['image_id']; ?>)">
							</form>
						<?php } ?>
					</div>
					<figcaption id="comments<?= $value['image_id']; ?>">
						<?= showComments($value['image_id'], $pdo) ?>
					</figcaption>
				</figure>
				<form class="gallery_form" id="gallery_form<?= $value['image_id']; ?>" method="post">
					<input type="text" name="comment_img_id" value="<?= $value['image_id']; ?>" hidden>
					<input type="text" class="like_comment" name="comment" maxlength="160" autocomplete="off" required>
					<button class="like_comment" onclick="addComment(<?= $value['image_id']; ?>)">Add comment</button>
				</form>
			</div>
		<?php } ?>
	</div>
	<?php include 'elements/footer.html'; ?>
</body>

<script>
	let userlogged = document.querySelector('.topuser_logout');

	/* disable like and comment buttons if user not logged in */
	window.onload = function() {
		var $_GET = <?php echo json_encode($_GET); ?>; /* encodes $_GET array to a JSON array */

		document.getElementById("photo_amount").value = "?paginate=" + $_GET['paginate'];

		if (!userlogged) {
			let buttons = document.getElementsByClassName("like_comment");
			for (let i = 0; i < buttons.length; i++) {
				buttons[i].disabled = true;
				buttons[i].innerHTML = "Login to like or comment";
				buttons[i].style = "color: grey";
			}
		}
	}

	/* delete photo if confirmed, by submitting the POST form */
	function deletePhoto(image_id) {
		if (confirm("Are you sure you want to delete this picture?")) {
			document.getElementById("trash" + image_id).submit();
		}
	}

	/* add or delete likes */
	function changeLikes(like_id) {

		if (userlogged) {
			let like_button = document.getElementById(like_id);
			let image_id = like_id.replace("heart", "");
			let likes = parseInt(document.getElementById('likes' + image_id).innerHTML);

			if (like_button.src.match("icons/heart_full.png")) {
				like_button.src = "icons/heart_empty.png";
				document.getElementById('likes' + image_id).innerHTML = (likes - 1);
			} else {
				like_button.src = "icons/heart_full.png";
				document.getElementById('likes' + image_id).innerHTML = (likes + 1);
			}

			let xml = new XMLHttpRequest();
			xml.open('post', 'likesandcomments.php', true);
			xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xml.send('liked=' + image_id);
		}
	}

	/* set profile picture */
	function setProfile(profile_icon, image_id) {
		let profile_picture = document.getElementById('top-profilepic');
		let former_profile_icon = document.getElementById('profile_icon_select');

		if (profile_icon.src.match("icons/profile.png")) {
			profile_icon.src = "icons/profile_selected.png";
			profile_icon.id = "profile_icon_select";
			profile_icon.title = "Remove profile picture";
			if (former_profile_icon) {
				former_profile_icon.src = "icons/profile.png";
				former_profile_icon.id = "";
				former_profile_icon.style = "height: 20px; width: auto;";
				former_profile_icon.title = "Make profile picture";
			}
		} else {
			profile_icon.src = "icons/profile.png";
			profile_icon.id = "";
			profile_icon.style = "height: 20px; width: auto;";
			profile_icon.title = "Make profile picture";
		}

		let xml = new XMLHttpRequest();
		xml.open('post', 'profile_pics.php', true);
		xml.onload = function() {
			profile_picture.src = this.response;
		}
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xml.send('setpic=' + image_id);
	}

	/* add comment */

	function addComment(image_id) {
		function sendData(form, image_id) {
			let comments = document.getElementById('comments' + image_id);
			let formData = new FormData(form);

			let xml = new XMLHttpRequest();
			xml.open('post', 'likesandcomments.php', true);
			xml.onload = function() {
				comments.innerHTML = this.response;
			}
			xml.send(formData);
		}

		let form = document.getElementById('gallery_form' + image_id);
		form.addEventListener("submit", (event) => {
			event.preventDefault();
			sendData(form, image_id);
			form.reset();
		});
	}

	/* delete comment */
	function deleteComment(comment_id, image_id) {
		if (confirm("Are you sure you want to delete this comment?")) {

			let comments = document.getElementById('comments' + image_id);

			let xml = new XMLHttpRequest();
			xml.open('post', 'likesandcomments.php', true);
			xml.onload = function() {
				comments.innerHTML = this.response;
			}
			xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xml.send('action=del_comment&comment_id=' + comment_id + '&image_id=' + image_id);
		}
	}
</script>

</html>