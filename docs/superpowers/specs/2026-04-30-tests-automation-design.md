# Tests 目录自动化测试设计

- 日期：2026-04-30
- 适用项目：typecho-markdown（Typecho Markdown 解析插件）
- 状态：待实施

## 目标

为 typecho-markdown 插件建立可一键运行的自动化测试体系，覆盖：

1. `MarkdownParse.php`（解析器层）—— 单元测试，不依赖 Typecho。
2. `Plugin.php`（Typecho 集成层）—— 单元测试，依赖真实 Typecho 源码。
3. 跨 PHP 与 Typecho 多版本验证 —— 通过 CI 矩阵自动覆盖。

不在本次范围内：

- `tests/docker-compose.yml`：保留，仅作手动联调工具，不纳入自动化。
- 静态分析（PHPStan/Psalm 等）。
- 性能/压力测试。
- `release.yml` 改动。

## 选型决策

| 维度         | 决策                              | 理由                                                  |
| ------------ | --------------------------------- | ----------------------------------------------------- |
| 测试范围     | 解析器 + Plugin 集成              | 覆盖核心解析行为，同时验证 Typecho 配置项真正生效     |
| 测试框架     | PHPUnit ^10.5 + Mockery ^1.6      | 行业标准；Mockery 处理 Typecho 链式静态调用更干净     |
| 一键命令     | `composer test`                   | 标准 composer scripts 入口                            |
| CI 集成      | GitHub Actions PR check 矩阵      | 复用现有 `pr-check.yml`                               |
| Typecho 版本 | `v1.2.0`、`v1.3.0`、`master`      | 仅关注次版本号变更；patch 不下钻；master 验证未来兼容 |
| PHP 版本     | 8.2 / 8.3 / 8.4 / 8.5             | CI 实际验证版本（`composer.json` 仍声明支持 ^8.1）    |
| 断言策略     | `assertStringContainsString` 优先 | 避免 commonmark 升级时大量误报                        |

## 架构

```
typecho-markdown/
├── MarkdownParse.php                    # 被测对象（不改动）
├── Plugin.php                           # 被测对象（仅最小改动：phar require 加 file_exists 守护）
├── composer.json                        # 加 require-dev + scripts.test
├── phpunit.xml.dist                     # PHPUnit 配置
├── .gitignore                           # 加 tests/.typecho、.phpunit.result.cache
├── tests/
│   ├── bootstrap.php                    # 加载 vendor、按 TYPECHO_VERSION 拉取 Typecho、require 被测代码
│   ├── Support/
│   │   ├── ResetSingletonsTrait.php     # 反射重置 MarkdownParse 单例与 Typecho widget 池
│   │   └── Fixtures.php                 # fixture 文件加载工具
│   ├── Unit/
│   │   ├── MarkdownParseTest.php        # 解析器单元测试
│   │   └── PluginTest.php               # Plugin.php 集成单元测试
│   ├── fixtures/
│   │   ├── mermaid-flowchart.md
│   │   ├── mathjax-mixed.md
│   │   ├── toc-multi-level.md
│   │   └── ...                          # 复杂场景输入
│   ├── .typecho/                        # 运行时下载的 Typecho 源码（gitignored）
│   │   └── <version>/
│   ├── docker-compose.yml               # 保留：手动联调用
│   └── usr/                             # 删除
└── .github/workflows/pr-check.yml       # 增加 matrix 维度 + PHPUnit 步骤 + Typecho 缓存
```

## 关键组件

### tests/bootstrap.php

职责：

1. `require_once __DIR__ . '/../vendor/autoload.php'`。
2. 解析 `getenv('TYPECHO_VERSION') ?: 'v1.2.0'`。
3. 检查 `tests/.typecho/<version>/var/Typecho/Common.php` 是否存在；不存在则执行：
   ```
   git clone --depth 1 --branch <version> https://github.com/typecho/typecho.git tests/.typecho/<version>
   ```
   失败时输出明确错误（含版本号）并 exit 1。
4. 把 Typecho 的 `var/` 目录加入 PSR-0 autoload。Typecho 自身的 autoloader 注册在 `var/Typecho/Common.php`，按其约定加载。
5. 断言关键类/接口存在：`\Typecho\Plugin\PluginInterface`、`\Widget\Options`、`\Typecho\Widget\Helper\Form`。缺失则 throw `RuntimeException`。
6. `require_once __DIR__ . '/../MarkdownParse.php'`。
7. `require_once __DIR__ . '/../Plugin.php'`。

### tests/Support/ResetSingletonsTrait.php

提供两个 protected 方法：

- `resetMarkdownParse()`：用反射把 `MarkdownParse::$instance` 设回 `null`，确保每个测试独立创建实例。
- `resetTypechoWidgets()`：用反射清空 Typecho 的 widget 容器（属性名以 v1.2.0 实际为准；如果 master 与 v1.2.0 属性名不一致，trait 内做兼容判断，按候选列表尝试）。

两个测试类都 `use` 此 trait，在 `setUp` 中调用。

### tests/Support/Fixtures.php

提供 `Fixtures::load(string $name): string` 静态方法，集中读取 `tests/fixtures/<name>` 文件。

### tests/Unit/MarkdownParseTest.php

覆盖 `MarkdownParse.php` 解析行为。**不依赖 Typecho 状态**。

测试用例分组：

- **基础 CommonMark**：标题、段落、列表、引用、围栏代码块、链接、图片、表格、删除线、任务列表、脚注、定义列表 —— 各自一个用例，断言关键 HTML 标签存在。
- **Mermaid**：` ```mermaid ` 围栏代码块输出 `<code class="mermaid">`；`getIsNeedMermaid()` 变为 `true`；其他语言（python/js）不受影响。
- **MathJax/LaTeX**：
  - 含 `$x$` 或 `$$...$$` 时 `getIsNeedLaTex()` 为 `true`；
  - `$$...$$` 块在 preParse 中被 `<div>` 包裹、postParse 中正确移除；
  - **边界**：包含反引号代码 `` `$x = 1` `` 的文档不应误触发 LaTeX。
- **TOC**：
  - `setIsTocEnable(true)` + `[TOC]` → 渲染目录 ul；
  - `setIsTocEnable(false)` + `[TOC]` → 不渲染（固化当前实现行为：`placeholder` 设为空字符串）。
- **External link**：
  - `internalHosts` 不为空时，外部域名链接带 `target="_blank"` 与 rel；
  - 同域名链接不被加 target。
  - **边界**：`internalHosts` 含正则形式 `/(^|\.)example\.com$/` 时能正常工作。
- **Lazy image**：`![](url)` → `<img loading="lazy" ...>`。
- **Mark 高亮**：`==text==` → `<mark>text</mark>`。
- **HeadingPermalink**：`# H1` → 标题包含 `<a class="heading-permalink" ...>`。

### tests/Unit/PluginTest.php

覆盖 `Plugin.php` Typecho 集成逻辑。

mock 策略：

- 用 Mockery 创建 `Widget\Options` 的 mock，通过 Typecho 真实的 widget 注册机制（`\Typecho\Widget::widget('Widget\\Options', ...)` 或同等 API）将 mock 注入 widget 容器。`setUp` 之前先 `resetTypechoWidgets()` 清空池，确保不被上次测试污染。
- mock 的 `plugin('MarkdownParse')` 返回另一个 mock，按测试期望设置各 magic property（如 `is_available_toc`、`is_available_mermaid`、`cdn_source`、`internal_hosts` 等）。
- mock 的 `siteUrl` magic property 返回 `https://example.com`。

测试用例：

- `parse()` 在 `is_available_toc=1` 时调用 `MarkdownParse::setIsTocEnable(true)` 并返回带 TOC 的 HTML。
- `parse()` 在 `internal_hosts` 为空时回落到 `siteUrl` 的 host。
- `resourceLink()` 在 `is_available_mermaid=FORCE` 时输出 mermaid `<script type="module">` 与 `mermaid.initialize`。
- `resourceLink()` 在 `is_available_mermaid=AUTO` 且解析过 mermaid 内容时也输出。
- `resourceLink()` 在 `is_available_mermaid=DISABLE` 时不输出 mermaid 脚本。
- `resourceLink()` 在 `is_available_mathjax=AUTO` + 内容含 `$...$` 时输出 polyfill + MathJax script。
- 不同 `cdn_source`（jsDelivr / cdnjs / baomitu）映射到不同 CDN URL。
- `resourceLink()` 在所有开关全 disable 时输出空字符串。
- `activate()` 调用不抛异常（smoke test）。
- `config(Form $form)` 调用不抛异常（smoke test，不验证表单文案）。

### tests/fixtures/

存放复杂 markdown 输入和期望片段。命名形如 `mermaid-flowchart.md`、`mathjax-mixed.md`、`toc-multi-level.md`。测试中用 `Fixtures::load()` 读取后调用解析器，再 `assertStringContainsString` 关键片段。

### phpunit.xml.dist

```xml
<?xml version="1.0"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         failOnWarning="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### composer.json 最终形态

```json
{
    "require": {
        "php": "^8.1",
        "league/commonmark": "^2.4",
        "wnx/commonmark-mark-extension": "^1.2",
        "simonvomeyser/commonmark-ext-lazy-image": "^2.0",
        "clue/phar-composer": "^1.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "mockery/mockery": "^1.6"
    },
    "scripts": {
        "test": "phpunit"
    }
}
```

### Plugin.php 最小改动

第 5 行：

```php
require_once 'phar://' . __DIR__ . '/vendor.phar/MarkdownParse.php';
```

改为：

```php
if (file_exists(__DIR__ . '/vendor.phar')) {
    require_once 'phar://' . __DIR__ . '/vendor.phar/MarkdownParse.php';
}
```

测试环境 bootstrap 已经 require 真文件，跳过 phar 不影响；生产环境 phar 文件存在则正常加载。

### .github/workflows/pr-check.yml

在现有 syntax check 基础上：

1. 把 matrix 改成二维：
   ```yaml
   matrix:
     php-version: ['8.2', '8.3', '8.4', '8.5']
     typecho-version: ['v1.2.0', 'v1.3.0', 'master']
     include:
       - typecho-version: master
         continue-on-error: true
   ```
2. 在 install dependencies 之后追加：
   ```yaml
   - name: Cache Typecho source
     uses: actions/cache@v4
     with:
       path: tests/.typecho
       key: typecho-${{ matrix.typecho-version }}
   - name: Run PHPUnit
     env:
       TYPECHO_VERSION: ${{ matrix.typecho-version }}
     run: composer test
   ```
3. job 级别 `continue-on-error: ${{ matrix.continue-on-error || false }}`，让 master 版本 job 失败不阻塞 PR。

### .gitignore

追加：

```
tests/.typecho
.phpunit.cache
.phpunit.result.cache
```

## 错误处理

- bootstrap 的 `git clone` 失败：输出明确错误（含版本号、git 命令），exit 1。CI 使用缓存 + GitHub Actions 网络可靠性，无需重试机制。本地开发者错误信息中给出"也可以手动 clone 到 tests/.typecho/<version>/"的提示。
- Typecho 加载后核心类不存在：bootstrap 末尾断言 `class_exists` / `interface_exists`，缺失抛 `RuntimeException`，避免测试在歧义状态下跑。
- `MarkdownParse` 单例污染：每个测试 `setUp` 通过反射重置实例。
- Mockery 期望未满足：通过 `MockeryPHPUnitIntegration` trait 在 `tearDown` 自动调用 `Mockery::close()` 验证。

## 测试隔离

- `MarkdownParse::$instance` 跨测试共享 → 每个测试反射重置。
- Typecho widget 容器跨测试共享 → 每个测试反射清空。
- fixture 加载集中在 `Fixtures::load()`，避免散落的 `file_get_contents`。

## 一键自动化使用方式

**本地**：

```bash
composer install                                # 首次安装 dev 依赖
composer test                                   # 默认 TYPECHO_VERSION=v1.2.0
TYPECHO_VERSION=master composer test            # 切换 Typecho 版本
./vendor/bin/phpunit --filter MarkdownParseTest # 仅跑某个测试类
```

首次运行时 bootstrap 自动 `git clone` Typecho 到 `tests/.typecho/<version>/`，之后复用。

**CI**：

PR 提交后自动在 PHP × Typecho = 4 × 3 = 12 个 job 上跑测试。`master` Typecho 版本的 job 标记 `continue-on-error: true`，失败仅作预警不阻塞合并。Typecho 源码通过 actions cache 加速。

## 落地任务清单（按依赖顺序）

1. 基础设施：`composer.json` 加 dev 依赖与 `test` 脚本，新增 `phpunit.xml.dist`，更新 `.gitignore`。
2. bootstrap & support：`tests/bootstrap.php`、`tests/Support/ResetSingletonsTrait.php`、`tests/Support/Fixtures.php`。
3. 生产代码最小改动：`Plugin.php` 第 5 行 phar require 加 `file_exists` 守护。
4. 解析器测试：`tests/Unit/MarkdownParseTest.php` + 必要的 `tests/fixtures/*`。
5. Plugin 测试：`tests/Unit/PluginTest.php`。
6. 清理：删除 `tests/test.php`、`tests/usr/`；保留 `tests/docker-compose.yml`。
7. CI 集成：修改 `.github/workflows/pr-check.yml`，加 PHPUnit 步骤、矩阵新维度、Typecho 缓存、`master` 的 `continue-on-error`。

## 风险与待观察项

- **Typecho widget 池内部属性名** 可能在 v1.2.0 / v1.3.0 / master 间不一致 → `ResetSingletonsTrait` 用反射时按候选属性名列表尝试，缺失则 throw 明确错误。
- **`master` 分支频繁变化** → 偶发 CI 失败若来自 Typecho 上游而非本插件，`continue-on-error` 已避免阻塞；但需要团队定期审视 master job 失败日志，区分上游 regression 与本插件兼容问题。
- **`git clone` 在 CI 上需要外网** → GitHub Actions 环境可靠；本地企业网络可能受限时，bootstrap 错误消息中给出手动 clone 提示。
- **PHPUnit 10 的 `failOnWarning`** 严格模式可能让一些次要警告升级为失败 → 实施过程中如遇大量误报再考虑放宽。

## 不变更的内容

- `release.yml` 不动。
- `tests/docker-compose.yml` 不重写。
- `MarkdownParse.php` 不改。
- 不引入静态分析工具。
- 不为现有代码补 docblock/类型注解。
