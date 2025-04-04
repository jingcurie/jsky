<footer>
    <div>
        <div>
            <img src="/assets/images/QR Code1.jpg" alt="QR Code 1">
            <p>锦山客运<br>闪乘巴士小程序</p>
        </div>
        <div>
            <img src="/assets/images/QR Code2.jpg" alt="QR Code 2">
            <p>锦山客运<br>微信公众号</p>
        </div>
    </div>
    <div>
        <p>&copy;版权所有：上海锦山汽车客运有限公司</p>
        <p>备案/许可证编号：沪ICP备2025117102号-2</p>
    </div>
</footer>

<script>
    AOS.init({
        duration: 1000
    });

    function toggleMenu() {
        console.log(0);
        const menu_icon = document.querySelector('#menu-icon');
        const menu = document.querySelector('.menu');
        menu.classList.toggle('active');
        menu_icon.classList.toggle('active');
        if (menu_icon.classList.contains('active')) {
            menu_icon.innerHTML = '&#10005;'; // 叉叉符号
            menu_icon.style.color = '#FFFFFF';
        } else {
            menu_icon.innerHTML = '&#9776;'; // 汉堡包符号
            menu_icon.style.color = '#000000';
        }
    }

    function toggleDropdown(element) {
        element.classList.toggle('active');
    }

    // 传递Banner数据给JavaScript
    const bannerData = <?= json_encode(array_map(function($banner) {
        return [
            'image' => '/assets/images/uploads/banners/' . $banner['image_path'],
            'title' => $banner['title'],
            'desc' => $banner['description'],
            // 'url' => $banner['url'] ?? '#',
            // 'target' => $banner['target'] ?? '_self'
        ];
    }, $banners)) ?>;

    const bannerImage = document.getElementById("banner-image");
    const bannerDesc = document.querySelector(".banner-desc");
    let currentIndex = 0;

    async function changeBanner() {
        // 1. 淡出当前内容（0.5s）
        bannerImage.classList.add("fade-out");
        bannerDesc.classList.add("fade-out");

        // 2. 等待淡出动画完成（减少到 0.5s）
        await new Promise(resolve => setTimeout(resolve, 800));

        // 3. 更新内容（立刻切换，不等待）
        const {
            image,
            title,
            desc
        } = bannerData[currentIndex];
        bannerImage.src = image;
        bannerDesc.innerHTML = `
        <h2 data-aos="fade-down">${title}</h2>
        <p data-aos="fade-right">${desc}</p>
    `;

        // 4. 立刻淡入新内容（不延迟）
        bannerImage.classList.remove("fade-out");
        bannerDesc.classList.remove("fade-out");

        // 5. 更新索引（循环）
        currentIndex = (currentIndex + 1) % bannerData.length;
    }

    // 初始加载
    changeBanner();

    // 自动轮播（每 4 秒切换一次）
    setInterval(changeBanner, 6000);
</script>

<script>
    // 搜索功能脚本
    document.addEventListener('DOMContentLoaded', function() {
        const searchBtn = document.querySelector('.search-btn');
        const searchBox = document.querySelector('.search-box');

        if (searchBtn && searchBox) {
            searchBtn.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    searchBox.classList.toggle('active');
                    if (searchBox.classList.contains('active')) {
                        const input = searchBox.querySelector('input');
                        if (input) {
                            input.focus();
                        }
                    }
                }
            });
        }

        // 点击页面其他区域关闭搜索框
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && searchBox && !e.target.closest('.search-box')) {
                searchBox.classList.remove('active');
            }
        });

    });
</script>
</body>

</html>