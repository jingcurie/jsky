
document.addEventListener("DOMContentLoaded", function () {
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("cover_image_input");
    const progress = document.getElementById("uploadProgress");
    const preview = document.getElementById("cover_preview");
    const coverImageField = document.getElementById("cover_image");

    const allowedTypes = ["image/jpeg", "image/png"];
    const maxSize = 3 * 1024 * 1024; // 3MB

    function uploadFile(file) {
        if (!allowedTypes.includes(file.type)) {
            alert("只允许上传 JPG 或 PNG 图片");
            return;
        }
        if (file.size > maxSize) {
            alert("文件大小不能超过 3MB");
            return;
        }

        const formData = new FormData();
        formData.append("file", file);

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "upload_cover_image.php", true);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                progress.style.display = "block";
                progress.value = (e.loaded / e.total) * 100;
            }
        };

        xhr.onload = function () {
            progress.style.display = "none";
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    preview.src = "/assets/images/uploads/" + response.file_name;
                    preview.style.display = "block";
                    coverImageField.value = response.file_name;
                } else {
                    alert("上传失败: " + response.error);
                }
            } else {
                alert("上传错误");
            }
        };

        xhr.onerror = function () {
            alert("网络错误，上传失败");
        };

        xhr.send(formData);
    }

    fileInput.addEventListener("change", function () {
        if (this.files.length > 0) {
            uploadFile(this.files[0]);
        }
    });

    dropArea.addEventListener("dragover", function (e) {
        e.preventDefault();
        dropArea.classList.add("border-primary");
    });

    dropArea.addEventListener("dragleave", function () {
        dropArea.classList.remove("border-primary");
    });

    dropArea.addEventListener("drop", function (e) {
        e.preventDefault();
        dropArea.classList.remove("border-primary");
        if (e.dataTransfer.files.length > 0) {
            uploadFile(e.dataTransfer.files[0]);
        }
    });
});
