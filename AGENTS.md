# ShanYing 开发助手规则

本文件指导编程助手维护主题，不是站点 AI 的系统提示词。

## 目录

- WordPress 模板入口保留在主题根目录；通用辅助功能集中在 `functions.php`，前台功能与共享服务保留在 `inc/`，后台设置与管理功能集中在 `admin/`，显示片段在 `template-parts/`。
- 颜色、间距、圆角等设计变量集中在 `tools/polar-assets/source/assets/css/tokens.css`；组件使用语义变量，不在组件规则中重复定义固定色值。
- 使用与开发说明放在 `docs/`；AI 角色与提示词组织见 `ai/README.md`。
- 字体、模型等资源的来源和许可文件保留在对应资源目录。
- 移动文档时同步修改相对链接，不将运行数据、凭据或用户对话写进文档。

## 当前工作区构建

主题的 CSS、JavaScript 源文件位于仓库根目录 `tools/polar-assets/`。修改源文件后运行 `python3 tools/polar-assets/build.py`；不要仅编辑主题内生成的 `assets/css/main.css`、`assets/js/main.js`。

## 验证

PHP 变更检查语法；界面变更验证对应页面和交互。保持 WordPress 权限、nonce、密码保护和现有数据处理规则。用户本次明确要求优先于文档中的历史设计。
