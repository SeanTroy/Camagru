<?php
require_once 'config/newpdo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
	header("Location: login.php");
}

?>

<html>

<head>
	<?php include_once 'elements/header.html'; ?>
</head>

<body>
	<?php include 'elements/topbar.html'; ?>
	<div class="editing_area">
		<div class="preview_area">
			<div class="capture_preview">
				<video id="video" autoplay></video>
				<canvas id="sticker_preview1" width="640" height="480"></canvas>
				<figcaption id="title_text">Webcam</figcaption>
				<button id="take-photo" disabled>Take Photo</button>
			</div>
			<div class="capture_preview">
				<canvas id="canvas" width="640" height="480"></canvas>
				<canvas id="sticker_preview2" width="640" height="480"></canvas>
				<figcaption id="title_text">Preview</figcaption>
				<button id="save-photo" disabled>Save photo</button>
			</div>
			<div class="stickerbar">
				<div class="stickerbar_content">
					<img class="sticker" id="empty.png" src="stickers/empty.png" onclick="drawSticker(this,0,0,1,1)">
					<img class="sticker" id="42.png" src="stickers/42.png" onclick="drawSticker(this,30,30,180,110)">
					<img class="sticker" id="fireframe.png" src="stickers/fireframe.png" onclick="drawSticker(this, 0, 0, 640, 480)">
					<img class="sticker" id="crown.png" src="stickers/crown.png" onclick="drawSticker(this, 200, 0, 240, 160)">
					<img class="sticker" id="blackhair.png" src="stickers/blackhair.png" onclick="drawSticker(this, 180, 0, 320, 250)">
					<img class="sticker" id="mario.png" src="stickers/mario.png" onclick="drawSticker(this, 30, 30, 200, 276)">
					<img class="sticker" id="8bitpipe.png" src="stickers/8bitpipe.png" onclick="drawSticker(this, 120, 150, 400, 400)">
					<img class="sticker" id="pinkglasses.png" src="stickers/pinkglasses.png" onclick="drawSticker(this, 180, 0, 300, 215)">
					<img class="sticker" id="moustache.png" src="stickers/moustache.png" onclick="drawSticker(this, 180, 300, 240, 100)">
					<img class="sticker" id="bloodyscar.png" src="stickers/bloodyscar.png" onclick="drawSticker(this, 200, 100, 180, 60)">
					<img class="sticker" id="Skullandbones.png" src="stickers/Skullandbones.png" onclick="drawSticker(this, 90, 0, 480, 480)">
				</div>
			</div>
		</div>
		<div class="previous_photos_bar" id="previous_photos_bar">
			<div class="photo_bar_content" id="photo_bar_content">

			</div>
		</div>
	</div>
	<div class="upload_area">
		<text>Upload image file:</text>
		<input type="file" id="image-upload" accept="image/jpeg, image/png, image/jpg">
	</div>
	<form>
		<input type="text" id="selected_sticker" value="empty.png,0,0,1,1" hidden>
	</form>
	<?php include 'elements/footer.html'; ?>
</body>

<script type='text/Javascript'>
	let video = document.getElementById("video");
	let click_button = document.getElementById("take-photo");
	let canvas = document.getElementById("canvas");
	let save_button = document.getElementById("save-photo");

	let preview1 = document.getElementById("sticker_preview1");
	let preview2 = document.getElementById("sticker_preview2");

	/* start webcam when entering the page and output to video element */

	window.onload = async function(){
		let stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
		video.srcObject = stream;
	}

	/* draw video frame to canvas */

	click_button.addEventListener('click', function() {
		canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

		/* enable the save photo button */
		document.getElementById("save-photo").disabled = false;
	});

	/* send canvas image to server side with selected sticker */

	save_button.addEventListener('click', function() {
		let image_data_url = canvas.toDataURL('image/jpeg');
		let sticker = document.getElementById("selected_sticker").value;

		// console.log("this is before: "+sticker);
		let xml = new XMLHttpRequest();
		xml.open('post', 'merge_images.php', true);
		xml.onload = function() {
			alert("Image saved to Gallery!");
			// console.log('RETURN_VALUE', this.response);
			appendPhotoBar(this.response);
		}
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xml.send('new_image='+image_data_url+'&sticker='+sticker);
	});

	/* upload and draw image to canvas */

	const image_upload = document.getElementById("image-upload");

	image_upload.addEventListener("change", function() {
		/* enable the save photo button */
		document.getElementById("save-photo").disabled = false;

		// const reader = new FileReader();

		// reader.addEventListener("load", () => {
		// let uploaded_image_url = reader.result;
		// });
		// reader.readAsDataURL(this.files[0]);

		var img = new Image();
		img.src = URL.createObjectURL(this.files[0]);
		img.onload = drawImage;
	});

	function drawImage() {
		let widthRatio = this.width / canvas.width;
		let heightRatio = this.height / canvas.height;

		if (widthRatio < heightRatio) {
			canvas.getContext('2d').drawImage(this, 0, 0, this.width, this.width * 3 / 4, 0, 0, canvas.width, canvas.height);
		} else {
			canvas.getContext('2d').drawImage(this, 0, 0, this.height * 4 / 3, this.height, 0, 0, canvas.width, canvas.height);
		}
	}

	/* select sticker, preview it and save its values */

	function drawSticker(sticker, h_offset, v_offset, width, height) {
		/* enable the take photo button */
		if (sticker.id == "empty.png")
			document.getElementById("take-photo").disabled = true;
		else
			document.getElementById("take-photo").disabled = false;
		/* clear previous sticker from preview screens */
		preview1.getContext('2d').clearRect(0, 0, preview1.width, preview1.height);
		preview2.getContext('2d').clearRect(0, 0, preview2.width, preview2.height);
		/* draw the new sticker */
		preview1.getContext('2d').drawImage(sticker, h_offset, v_offset, width, height);
		preview2.getContext('2d').drawImage(sticker, h_offset, v_offset, width, height);

		document.getElementById("selected_sticker").value = sticker.id+','+h_offset+','+v_offset+','+width+','+height;
	}

	function moveSticker(element, canvas, event) {
		let sticker_values = document.getElementById("selected_sticker").value.split(',');

    	const rect = element.getBoundingClientRect();
    	const x = (event.clientX - rect.left) / (rect.right - rect.left) * 640 - sticker_values[3] / 2;
    	const y = (event.clientY - rect.top) / (rect.bottom - rect.top) * 480 - sticker_values[4] / 2;

		let sticker = document.getElementById(sticker_values[0]);
		drawSticker(sticker, x, y, sticker_values[3], sticker_values[4]);
    	// console.log("x: " + x + " y: " + y)
	}

	preview1.addEventListener('mousedown', function(e) {
		moveSticker(this, canvas, e)
	})

	preview2.addEventListener('mousedown', function(e) {
		moveSticker(this, canvas, e)
	})

	/* add photos to saved photos bar */

	function appendPhotoBar(savedImage) {
	let photoBar = document.getElementById('photo_bar_content');

	let latestPhoto = document.createElement('img');
	latestPhoto.id = 'saved_photo';
	latestPhoto.src = savedImage;

	photoBar.appendChild(latestPhoto);
	}

</script>

</html>