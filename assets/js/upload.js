document.addEventListener("DOMContentLoaded", function () {
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("cover_image_input");
    const preview = document.getElementById("cover_preview");
    const coverImageField = document.getElementById("cover_image");
    const form = document.querySelector("form"); // 获取文章表单

    const allowedTypes = ["image/jpeg", "image/png"];
    const maxSize = 3 * 1024 * 1024; // 3MB

    // 全局临时存储文件对象
    let tempCoverFile = null;

    // 仅预览不上传
    function handleFileSelection(file) {
        if (!allowedTypes.includes(file.type)) {
            alert("只允许上传 JPG 或 PNG 图片");
            return false;
        }
        if (file.size > maxSize) {
            alert("文件大小不能超过 3MB");
            return false;
        }

        // 显示预览
        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";
        
        // 存储文件对象供后续使用
        tempCoverFile = file;
        return true;
    }

    // 实际文件上传函数
    async function uploadFile(file) {
        const formData = new FormData();
        formData.append("file", file);

        try {
            const response = await fetch("upload_cover_image.php", {
                method: "POST",
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // 更新隐藏字段值
                coverImageField.value = result.file_name;
                return true;
            } else {
                alert("上传失败: " + (result.error || "未知错误"));
                return false;
            }
        } catch (error) {
            console.error("上传出错:", error);
            alert("网络错误，上传失败");
            return false;
        }
    }

    // 修改表单提交逻辑
    form.addEventListener("submit", async function(e) {
        e.preventDefault(); // 阻止默认提交

        // 如果有封面图片需要上传
        if (tempCoverFile) {
            // 显示上传状态（可选）
            const originalSubmitBtn = form.querySelector('[type="submit"]');
            originalSubmitBtn.disabled = true;
            originalSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 上传中...';

            // 执行上传
            const uploadSuccess = await uploadFile(tempCoverFile);
            
            if (!uploadSuccess) {
                originalSubmitBtn.disabled = false;
                originalSubmitBtn.innerHTML = '<i class="fas fa-save"></i> 发布文章';
                return; // 终止提交
            }
        }

        // 继续提交表单
        this.submit();
    });

    // 修改事件监听（仅预览不上传）
    fileInput.addEventListener("change", function() {
        if (this.files.length > 0) {
            handleFileSelection(this.files[0]);
        }
    });

    // 拖放区域处理
    dropArea.addEventListener("dragover", function(e) {
        e.preventDefault();
        dropArea.classList.add("border-primary");
    });

    dropArea.addEventListener("dragleave", function() {
        dropArea.classList.remove("border-primary");
    });

    dropArea.addEventListener("drop", function(e) {
        e.preventDefault();
        dropArea.classList.remove("border-primary");
        if (e.dataTransfer.files.length > 0) {
            handleFileSelection(e.dataTransfer.files[0]);
        }
    });
});