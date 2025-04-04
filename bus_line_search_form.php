<?php
$current_page_css = "/assets/css/line_search_style.css"; // 该页面独有的 CSS
include 'header.php';
?>

<main class="article-main">
  <?php
  // 获取 "出行指南" 的 ID
  $parentMenu = query($conn, "SELECT id FROM menu WHERE name = '出行指南' AND is_active = 1 LIMIT 1");

  if (!empty($parentMenu)) {
    $parentId = $parentMenu[0]['id'];

    // 获取 "出行指南" 的子菜单
    $subMenus = query($conn, "SELECT * FROM menu WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order", [$parentId]);
  } else {
    $subMenus = []; // 如果找不到，返回空数组
  }
  ?>

  <aside class="article-sidebar">
    <h2>出行指南</h2>
    <?php if (!empty($subMenus)): ?>
      <ul>
        <?php foreach ($subMenus as $menu): ?>
          <li class="<?= ($menu['article_category_id'] == $article_category_id) ? 'active' : '' ?>">
            <?php if ($menu['menu_type'] === 'article'): ?>
              <a href="showSubMenuPage.php?menu_id=<?= urlencode($parentId) ?>&article_category_id=<?= urlencode($menu['article_category_id']) ?>">
                <?= htmlspecialchars($menu['name']) ?>
              </a>
            <?php else: ?>
              <a href="<?= $menu['url'] ?>">
                <?= htmlspecialchars($menu['name']) ?>
              </a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </aside>

  

  <div class="article-container">
    <div class="featureImageSection"><img src="/assets/images/bus_route_search.jpg" alt="公交线路查询图" width="100%" style="border-radius:10px;"></div>
    <section class="route-search">
      <h2>公交线路查询</h2>



      <div class="input-container">
        <label>路线：</label>
        <select id="lineSelect">
          <option value="" disabled selected>请选择线路</option>
        </select>

        <label>方向：</label>
        <select id="directionSelect">
          <option value="up">上行</option>
          <option value="down">下行</option>
        </select>

        <button id="searchBtn">查询</button>
      </div>

      <h3 id="lineTitle">请选择线路</h3>
      <div id="lineMap">
        <div class="line" id="stations"></div>
      </div>
    </section>
  </div>


  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const links = document.querySelectorAll(".menu-link");
      const mainContent = document.querySelector(".article-container");

      links.forEach(link => {
        link.addEventListener("click", function(e) {
          e.preventDefault(); // 阻止默认跳转

          const url = this.getAttribute("data-url");
          const menuType = this.getAttribute("data-menu-type");
          const categoryId = this.getAttribute("data-article-category-id");

          // 移除所有 li 的 active 类
          document.querySelectorAll(".article-sidebar li").forEach(li => {
            li.classList.remove("active");
          });

          // 给当前点击的 li 父元素添加 active 类
          this.parentElement.classList.add("active");

          if (menuType === "url") {
            window.location.href = url; // 直接跳转
          } else if (menuType === "single_article") {
            fetch(`get_articles.php?type=single&category_id=${categoryId}`)
              .then(response => response.text())
              .then(html => {
                mainContent.innerHTML = html;
              })
              .catch(error => {
                console.error("加载失败:", error);
                mainContent.innerHTML = "<p>加载失败，请重试。</p>";
              });
          } else if (menuType === "multiple_article") {
            fetch(`get_articles.php?type=multiple&category_id=${categoryId}`)
              .then(response => response.text())
              .then(html => {
                mainContent.innerHTML = html;
              })
              .catch(error => {
                console.error("加载失败:", error);
                mainContent.innerHTML = "<p>加载失败，请重试。</p>";
              });
          }
        });
      });
    });
  </script>
  <script>
    $(document).ready(function() {
      let allLines = [];

      console.log("页面加载完成，准备获取线路数据...");

      // 获取线路
      $.ajax({
        url: "busRouteSearch/bus_line_search.php?action=getLines",
        method: "GET",
        dataType: "json",
        success: function(data) {
          allLines = data;
          let lineSelect = $("#lineSelect");
          lineSelect.empty();

          // 添加默认“请选择线路”选项
          lineSelect.append(
            '<option value="" disabled selected>请选择线路</option>'
          );

          data.forEach((line) => {
            lineSelect.append(`<option value="${line}">${line}</option>`);
          });
        },
        error: function() {
          console.error("获取线路列表失败");
        },
      });

      // 查询按钮
      $("#searchBtn").click(function() {
        let line = $("#lineSelect").val();
        let direction = $("#directionSelect").val();

        if (!line) {
          alert("请选择线路");
          return;
        }

        let directionText = direction === "up" ? "上行" : "下行";
        $("#lineTitle").text(`${line} (${directionText}) 线路图`);

        $.post("busRouteSearch/bus_line_search.php", {
            action: "getStations",
            line: line,
            direction: direction,
          })
          .done(function(data) {
            generateLineImage(JSON.parse(data), direction);
          })
          .fail(function() {
            console.error("查询站点失败");
          });
      });
    });

    function generateLineImage(data, direction) {
      let stationsDiv = $("#stations");
      stationsDiv.empty(); // 清空旧数据

      let stations = direction === "up" ? data.upStations : data.downStations;

      if (!stations || stations.length === 0) {
        stationsDiv.append("<p>未找到站点数据</p>");
        return;
      }

      console.log("绘制方向:", direction, "站点数据：", stations);

      // 创建线路图的容器
      let lineContainer = $("<div>").css({
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
        position: "relative",
        width: "100%",
        padding: "20px 40px",
      });

      let stationElements = [];

      // 依次添加站点
      stations.forEach((station, index) => {
        let stationWrapper = $("<div>").css({
          display: "flex",
          alignItems: "center",
          // marginBottom: "25px", // 调整间距
          position: "relative",
        });

        // 站点左侧黄圈（**空心黄圆 + 粗边框**）
        let circle = $("<div>").css({
          width: "12px",
          height: "12px",
          border: "4px solid #f1c40f", // 黄色边框
          backgroundColor: "white", // 白色背景
          borderRadius: "50%",
          position: "absolute",
          left: "-21px", // 让它紧贴黄线
        });

        // 站点名称（带白色背景）
        let stationDiv = $("<div>").addClass("station").text(station).css({
          backgroundColor: "white", // 白色背景
          color: "#333",
          padding: "8px 15px",
          margin: "0.5rem 0",
          borderRadius: "5px",
          fontSize: "16px",
          fontWeight: "bold",
          // border: "2px solid transparent", // 让站名更突出
          marginLeft: "0px", // 让站名对齐黄圈
          textAlign: "left",
          width: "auto",
        });

        stationWrapper.append(circle);
        stationWrapper.append(stationDiv);
        lineContainer.append(stationWrapper);
        stationElements.push(stationWrapper);
      });

      stationsDiv.append(lineContainer);

      // **动态调整黄线，使其完美包裹住所有站点**
      let firstStation = stationElements[0].position().top + 20;
      let lastStation =
        stationElements[stationElements.length - 1].position().top;

      let line = $("<div>").css({
        width: "6px",
        backgroundColor: "#f1c40f",
        position: "absolute",
        left: "26px", // 调整到靠近黄圈
        top: firstStation + "px", // **起点对齐第一个站点**
        height: lastStation - firstStation + 22 + "px", // **终点对齐最后一个站点**
      });

      lineContainer.prepend(line);
    }
  </script>
</main>



<?php include "footer.php" ?>