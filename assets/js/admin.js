function openDeleteModal(name, deleteUrl) {
    const modalElement = document.getElementById('deleteModal');
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('confirmDeleteLink').href = deleteUrl;
    // 显示模态框
    const deleteModal = new bootstrap.Modal(modalElement);
    deleteModal.show();

}

// 批量删除功能
document.addEventListener('DOMContentLoaded', function() {
    // 工具提示
    new bootstrap.Tooltip(document.body, {
        selector: '[data-bs-toggle="tooltip"]'
    });

    // 全选/取消全选
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.article-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkDeleteButton();
    });

    // 单个复选框事件
    document.querySelectorAll('.article-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkDeleteButton);
    });

    function updateBulkDeleteButton() {
        const checked = document.querySelectorAll('.article-checkbox:checked');
        document.getElementById('bulkDeleteBtn').disabled = checked.length === 0;
        document.getElementById('selectedCount').textContent = checked.length;
        document.getElementById('confirmSelectedCount').textContent = checked.length;
    }
});


// 分页跳转
function changePage() {
    var selectedPage = document.getElementById('pageSelect').value;
    window.location.href = `?page=${selectedPage}&sort=<?= $sort_column ?>&order=<?= $sort_order ?>`;
}