<!-- 删除确认模态框 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">确认删除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                确定要删除 <strong id="deleteItemName"></strong> 吗？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <a id="confirmDeleteLink" class="btn btn-danger">删除</a>
            </div>
        </div>
    </div>
</div>

<!-- 新增：批量删除确认模态框 -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> 确认批量删除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>您确定要删除选中的 <strong id="confirmSelectedCount">0</strong> 篇文章吗？</p>
                <p class="text-danger"><strong>此操作不可撤销！</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" name="bulk_delete" form="bulkActionForm" class="btn btn-danger">
                    <i class="fas fa-trash"></i> 确认删除
                </button>
            </div>
        </div>
    </div>
</div>