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
				<video id="video" autoplay playsinline></video>
				<! -- 'playsinline' makes the video play in the canvas in mobile -->
					<canvas id="sticker_preview1" width="640" height="480"></canvas>
					<figcaption id="title_text">Webcam</figcaption>
					<button id="take-photo" disabled>Select Sticker</button>
			</div>
			<div class="capture_preview">
				<canvas id="canvas" width="640" height="480"></canvas>
				<canvas id="sticker_preview2" width="640" height="480"></canvas>
				<figcaption id="title_text">Preview</figcaption>
				<button id="save-photo" disabled>Save photo</button>
			</div>
			<canvas id="locked_preview" width="640" height="480" hidden></canvas>
			<div class="stickerbar">
				<div class="stickerbar_content">
					<img class="sticker" id="empty.png" src="stickers/empty.png" onclick="drawSticker(this,0,0,1,1,'new')">
					<img class="sticker" id="42.png" src="stickers/42.png" onclick="drawSticker(this,30,30,180,110,'new')">
					<img class="sticker" id="fireframe.png" src="stickers/fireframe.png" onclick="drawSticker(this, 0, 0, 640, 480,'new')">
					<img class="sticker" id="crown.png" src="stickers/crown.png" onclick="drawSticker(this, 200, 0, 240, 160,'new')">
					<img class="sticker" id="blackhair.png" src="stickers/blackhair.png" onclick="drawSticker(this, 180, 0, 320, 250,'new')">
					<img class="sticker" id="mario.png" src="stickers/mario.png" onclick="drawSticker(this, 30, 30, 200, 276,'new')">
					<img class="sticker" id="8bitpipe.png" src="stickers/8bitpipe.png" onclick="drawSticker(this, 120, 150, 400, 400,'new')">
					<img class="sticker" id="pinkglasses.png" src="stickers/pinkglasses.png" onclick="drawSticker(this, 180, 0, 300, 215,'new')">
					<img class="sticker" id="moustache.png" src="stickers/moustache.png" onclick="drawSticker(this, 180, 300, 240, 100,'new')">
					<img class="sticker" id="bloodyscar.png" src="stickers/bloodyscar.png" onclick="drawSticker(this, 200, 100, 180, 60,'new')">
					<img class="sticker" id="Skullandbones.png" src="stickers/Skullandbones.png" onclick="drawSticker(this, 90, 0, 480, 480,'new')">
				</div>
			</div>
		</div>
		<div class="previous_photos_bar" id="previous_photos_bar">
			<div class="photo_bar_content" id="photo_bar_content">

			</div>
		</div>
	</div>
	<div class="upload_area">
		<label for="image-upload" class="styled-image-upload">
			<text>Upload Image File</text>
			<input type="file" id="image-upload" accept="image/jpeg, image/png, image/jpg">
		</label>
	</div>
	<form>
		<input type="text" id="selected_sticker" value="" hidden>
		<input type="text" id="locked_stickers" value="" hidden>
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
	let locked_preview = document.getElementById("locked_preview");
	var uploaded = "N";

	/* start webcam when entering the page and output to video element, considering orientation */

	window.onload = async function(){
		if (window.matchMedia("(orientation: portrait)").matches && window.matchMedia("(hover: none)").matches) {
			let videoMode = {aspectRatio: 3/4, facingMode: 'user'};
			let stream = await navigator.mediaDevices.getUserMedia({ video: videoMode, audio: false });
			video.srcObject = stream;
		} else {
			let videoMode = {aspectRatio: 4/3, facingMode: 'user'};
			let stream = await navigator.mediaDevices.getUserMedia({ video: videoMode, audio: false });
			video.srcObject = stream;
		}
	}

	/* draw video frame to canvas and enable the save photo button */

	click_button.addEventListener('click', function() {
		canvas.style = "transform: scaleX(-1);"
		uploaded = "N";
		canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
		document.getElementById("save-photo").disabled = false;
	});

	/* send canvas image to server side with selected sticker */

	save_button.addEventListener('click', function() {
		let image_data_url = canvas.toDataURL('image/jpeg');
		let stickers = document.getElementById("locked_stickers").value;
		stickers += document.getElementById("selected_sticker").value;

		let xml = new XMLHttpRequest();
		xml.open('post', 'merge_images.php', true);
		xml.onload = function() {
			alert("Image saved to Gallery!");
			appendPhotoBar(this.response);
		}
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xml.send('new_image='+image_data_url+'&sticker='+stickers+'&uploaded='+uploaded);
	});

	/* upload and draw image to canvas, considering file size */

	const image_upload = document.getElementById("image-upload");

	image_upload.addEventListener("change", function() {
		if (this.files[0].size > 2097152) {
			alert("File is too big! Maximum size is 2Mb.");
			this.value = "";
		}
		if (this.value !== "") {
			canvas.style = "transform: scaleX(1);"
			uploaded = "Y";
			document.getElementById("save-photo").disabled = false;

			var img = new Image();
			img.src = URL.createObjectURL(this.files[0]);
			img.onload = drawImage;
			this.value = "";
		}
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

	function drawSticker(sticker, h_offset, v_offset, width, height, status) {
		if (status === 'new') {
			/* save the previous stickers to locked canvas */
			locked_preview.getContext('2d').drawImage(preview1, 0, 0, canvas.width, canvas.height);
			locked_preview.getContext('2d').drawImage(preview1, 0, 0, canvas.width, canvas.height);
			document.getElementById("locked_stickers").value += document.getElementById("selected_sticker").value;
		}
		if (sticker.id == "empty.png") {
			/* disable the take photo button */
			document.getElementById("take-photo").disabled = true;
			document.getElementById("take-photo").innerHTML = "Select Sticker";
			/* clear former stickers */
			locked_preview.getContext('2d').clearRect(0, 0, preview1.width, preview1.height);
			document.getElementById("locked_stickers").value = "";
		} else {
			document.getElementById("take-photo").disabled = false;
			document.getElementById("take-photo").innerHTML = "Take Photo";
		}
		/* clear previous sticker from preview screens */
		preview1.getContext('2d').clearRect(0, 0, preview1.width, preview1.height);
		preview2.getContext('2d').clearRect(0, 0, preview2.width, preview2.height);
		/* draw the previously selected stickers */
		preview1.getContext('2d').drawImage(locked_preview, 0, 0, canvas.width, canvas.height);
		preview2.getContext('2d').drawImage(locked_preview, 0, 0, canvas.width, canvas.height);
		/* draw the new sticker */
		preview1.getContext('2d').drawImage(sticker, h_offset, v_offset, width, height);
		preview2.getContext('2d').drawImage(sticker, h_offset, v_offset, width, height);
		/* set values to hidden element */
		document.getElementById("selected_sticker").value = sticker.id+','+h_offset+','+v_offset+','+width+','+height+',';
	}

	/* move the sticker by clicking on either preview window */

	function moveSticker(element, canvas, event) {
		let sticker_values = document.getElementById("selected_sticker").value.split(',');

		const rect = element.getBoundingClientRect();
		const x = (event.clientX - rect.left) / (rect.right - rect.left) * 640 - sticker_values[3] / 2;
		const y = (event.clientY - rect.top) / (rect.bottom - rect.top) * 480 - sticker_values[4] / 2;

		let sticker = document.getElementById(sticker_values[0]);
		drawSticker(sticker, x, y, sticker_values[3], sticker_values[4],'moved');
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