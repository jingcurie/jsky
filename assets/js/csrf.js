// 生成并设置CSRF令牌
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = generateUUID();
    document.querySelector('meta[name="csrf-token"]').content = csrfToken;
    
    // 也可以存储在cookie中供PHP使用
    document.cookie = `csrf_token=${csrfToken}; path=/; SameSite=Strict`;
});

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}