<p align="center"><img src="assets/images/brand/polar-icon.png" width="96" alt="ShanYing 山映"></p>
<h1 align="center">山映 · ShanYing</h1>
<p align="center">记录生活，自有光芒。</p>
<p align="center">
  <a href="https://github.com/gentpan/ShanYing/blob/main/style.css"><img src="https://img.shields.io/badge/ShanYing-0.5.2-0755e9?style=flat-square" alt="ShanYing 0.5.2"></a>
  <a href="https://wordpress.org/"><img src="https://img.shields.io/badge/WordPress-6.6%2B-21759B?style=flat-square&amp;logo=wordpress&amp;logoColor=white" alt="WordPress 6.6+"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&amp;logo=php&amp;logoColor=white" alt="PHP 7.4+"></a>
  <a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html"><img src="https://img.shields.io/badge/License-GPL--2.0--or--later-3b805a?style=flat-square" alt="License GPL-2.0-or-later"></a>
  <a href="https://xifeng.net"><img src="https://img.shields.io/badge/Website-xifeng.net-0755e9?style=flat-square" alt="Website xifeng.net"></a>
  <a href="https://github.com/gentpan/ShanYing/stargazers"><img src="https://img.shields.io/github/stars/gentpan/ShanYing?style=flat-square&amp;logo=github" alt="GitHub stars"></a>
</p>

<p align="center"><a href="https://xifeng.net">作者网站 · xifeng.net</a></p>

山映是一款以山水光影与生活记录为灵感的 WordPress 个人博客主题，融合随访客时间与天气变化的首页、文章分类浏览、说说、旅行足迹、博友互动与头像游戏。支持深浅配色和自定义后台外观，基于 PHP、JavaScript 与 CSS，局部交互使用 React、Motion 和 Lottie。

> 当前版本：0.5.2，持续开发中。请在测试站验证后再用于生产站点。

## 主题预览

以下图片截取自本地运行的山映主题，展示实际页面。首页背景会随时间和天气变化；浅色截图保留了拍摄时的夜景 Hero。

### 首页 · 深色

![山映首页深色实截图](assets/images/previews/home-dark.png)

### 首页 · 浅色

![山映首页浅色实截图](assets/images/previews/home-light.png)

### 存档

![山映存档页实截图](assets/images/previews/archives.png)

## 功能

- **首页内容聚合**：Hero 欢迎区域、站点动态热力图、分类切换、文章排序与分页、悬浮特色图预览。
- **文章阅读**：阅读辅助、文章信息与版权、代码高亮，以及统一的搜索、分类和关键词列表。
- **说说**：独立便签式记录页面，管理员发布、编辑与图片管理。
- **旅行足迹**：文章独立勾选收录，地点坐标与旅行日期；全屏地图、平面 / 立体 / 地球视图、多种 Mapbox 风格与地球背景设置。
- **朋友与评论**：友链动态、评论等级、系统与浏览器标识、评论回复与限时自助编辑。
- **个性化互动**：山水天气 Hero、真实月相、音乐播放器、宠物陪伴、自定义右键菜单、可配置 AI 功能。
- **主题设置**：后台集中配置，原生 WordPress 菜单、分类、标签和媒体库管理。

## 技术栈

| 层级 | 技术 |
| --- | --- |
| 主题主体 | WordPress 经典主题、PHP、theme.json |
| 页面交互 | JavaScript、CSS、局部 AJAX / 页面切换 |
| 局部动画组件 | React 19、Motion、Lottie、Lucide、Font Awesome |
| 地图 | Mapbox GL JS；可选 Google Maps / 高德 |
| 资源构建 | Node.js、esbuild、Python |
| 数据存储 | WordPress 文章、分类、标签、元数据和选项 |

主题可关闭或按需配置外部服务。地图、天气、AI 等功能可能依赖网络与服务商凭据；仓库不提供共享密钥。

## 安装与配置

1. 将主题目录放到 WordPress 的 `wp-content/themes/shanying/`。
2. 在「外观 → 主题」启用 **山映 · ShanYing**。
3. 在「外观 → ShanYing 设置」配置主题；在「外观 → 菜单」配置导航。
4. 创建名为「足迹」的页面，选择「足迹地图」模板，并加入导航菜单。
5. 在文章编辑页的「旅行足迹」中勾选收录，填写地点或坐标、旅行日期。
6. 如使用地图与 AI 等服务，在后台填写自己的服务配置。

主题声明的最低要求为 WordPress 6.6、PHP 7.4；最低版本兼容性仍需完整验证。安装已构建资源无需运行 Node.js。

## 目录

```text
shanying/
├── assets/          已构建的前后台样式、脚本与品牌资源
├── admin/           主题设置、管理面板与后台资源入口
├── inc/             前台功能与共享服务模块
├── pages/           自定义页面模板
├── template-parts/  可复用页面片段
├── ai/              AI 目录说明
├── docs/            功能文档
├── style.css        WordPress 主题元数据
└── screenshot.png   WordPress 后台主题预览
```

开发源文件位于主题仓库的 `tools/polar-assets/`。设计变量集中在 `source/assets/css/tokens.css`，组件规则使用这些变量。不要仅修改生成后的 `main.css`、`admin.css`、`main.js`、`admin.js`。在主题根目录运行：

```sh
python3 tools/polar-assets/build.py
python3 tools/polar-assets/audit.py
```

## 作者与许可

由 [西风](https://xifeng.net) 设计与开发。主题 style.css 声明 GPL-2.0-or-later；第三方字体、图标、动画和其他资源遵循各自许可证，相关说明保留于资源目录。主题预览与品牌图标由 AI 辅助生成。
