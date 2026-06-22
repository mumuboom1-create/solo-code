# PbootCMS 项目架构分析报告

> 分析日期：2026-06-22
> 项目版本：PbootCMS 二次开发版本

---

## 一、项目整体架构分析

### 1.1 架构概述

这是一个基于 **PbootCMS** 二次开发的企业级内容管理系统，采用自研的 **MVC（Model-View-Controller）** 轻量级框架，使用 PHP 语言开发，支持 MySQL/MariaDB、SQLite 等多种数据库。

### 1.2 目录结构

| 目录/文件 | 说明 |
|---------|------|
| `index.php` | 前台入口文件，绑定 home 模块 |
| `szadmin.php` | 后台入口文件，绑定 admin 模块 |
| `api.php` | API 入口文件，绑定 api 模块 |
| `core/` | 框架内核 |
| `apps/` | 应用模块（admin、home、api） |
| `config/` | 配置文件目录 |
| `template/` | 前台模板目录 |
| `runtime/` | 运行时缓存、会话目录 |

### 1.3 核心内核（core/）

| 文件 | 功能 |
|------|------|
| `core/start.php` | 启动引导，引入初始化文件 |
| `core/init.php` | 环境初始化、常量定义、自动加载注册 |
| `core/basic/Kernel.php` | **核心调度器**：URL路由解析、授权检查、控制器分发 |
| `core/basic/Controller.php` | 控制器基类，提供模板渲染、变量注入等 |
| `core/basic/Model.php` | 模型基类，提供连贯操作的ORM（查询构造器） |
| `core/basic/Check.php` | 环境/URL安全检查 |
| `core/basic/Basic.php` | 自动加载、错误处理、会话处理、模型工厂 |
| `core/function/` | 全局辅助函数库（helper、handle、file） |
| `core/database/` | 数据库驱动（Mysqli、Pdo、Sqlite） |
| `core/view/` | 模板视图引擎（Parser模板标签解析） |

### 1.4 应用模块（apps/）

采用 **多模块架构**，包含三大模块：

| 模块 | 基类控制器 | 功能 |
|------|----------|------|
| **admin** | `apps/common/AdminController.php` | 后台管理系统（用户、角色、菜单、内容、配置等） |
| **home** | `apps/common/HomeController.php` | 前台展示（内容列表、详情、搜索、留言、会员等） |
| **api** | `apps/common/ApiController.php` | API接口（点赞、内容数据等） |

### 1.5 请求流程

```
用户请求
  → index.php/szadmin.php/api.php (入口)
  → core/start.php (启动)
  → core/init.php (初始化常量、自动加载、错误处理)
  → Kernel::run() [授权校验→URL安全检查→URL解析→路由]
  → Kernel::dispatch() [模块/控制器/方法解析]
  → 实例化对应 Controller
  → Controller 构造函数（登录/权限/缓存检查）
  → 调用对应 Action 方法
  → Model 操作数据库
  → View 模板渲染输出
```

---

## 二、授权流程分析

该项目的授权体系分为 **两个层面**：
1. **域名授权（License授权）** — 程序级别的商业授权
2. **后台权限授权（RBAC）** — 用户级别的功能权限

### 2.1 域名授权（License授权）流程

授权入口位于 `core/basic/Kernel.php` 的 `checkLicense()` 方法，在每次请求时最先执行：

#### 步骤1：获取当前访问域名
```php
$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
$host = preg_replace('/:\d+$/', '', $host);       // 去除端口
$host = preg_replace('/^www\./', '', $host);      // 去除www前缀
```

#### 步骤2：本地环境白名单检测（自动跳过授权）

```
isLocalEnv($host):
  → 匹配 localhost、127.0.0.1、::1
  → 匹配局域网IP段：10.x.x.x, 172.16-31.x.x, 192.168.x.x
  → 本地/内网开发环境自动通过授权
```

#### 步骤3：读取授权码配置

从 `config/config.php` 读取 `sn` 配置项，格式为 `域名:签名,域名:签名`：
```php
'sn' => 'test.susufuture.com:fcuxwW5BZ_j9risHd0bpwydooOZH...'
```

#### 步骤4：RSA公钥验签

```
授权码格式: domain:base64url(signature)

验证流程:
1. 按逗号分割多个授权码
2. 按冒号分割为 [授权域名, 签名]
3. 授权域名匹配当前域名
4. 对签名进行 Base64Url → Base64 解码
5. 使用 RSA 公钥 + SHA256 验签: openssl_verify($domain, $signature, $publicKey, OPENSSL_ALGO_SHA256)
6. 匹配成功则通过，否则返回403未授权页面
```

公钥硬编码在 `core/basic/Kernel.php` 中。

> ⚠️ **重要发现**：在 `core/basic/Kernel.php#L275-L278`，`$enableCheck` 被设置为 `false`，这意味着 **授权校验实际上已经被全局关闭（bypass）**，任何域名均可正常访问！

### 2.2 后台权限授权（RBAC）流程

#### 2.2.1 登录认证流程

入口：`apps/admin/controller/IndexController.php` 的 `login()` 方法

```
1. 验证码校验（GD库启用时）
2. 登录失败次数/锁定检测 — 基于IP黑名单文件（runtime/data/xxx.php）
   - 默认5次失败后锁定900秒（15分钟）
3. 用户名 + 双层MD5加密密码查询数据库
   - 密码加密: md5(md5($password))
4. 登录成功后:
   - session_regenerate_id(true)  // 会话ID重生（防固定会话攻击）
   - 写入 Session:
     • sid (加密会话标识: encrypt_string(session_id + user_id))
     • id, ucode, username, realname
     • rcodes (角色编码列表)
     • levels (授权URL权限列表)
     • menu_tree (菜单树)
     • acodes / area_tree (可管理区域)
   - 更新登录次数和IP
```

#### 2.2.2 会话鉴权流程

在每个后台请求中，`apps/common/AdminController.php` 的构造函数执行：

**第一步：登录状态检查**
```
checkLogin():
  → 免登录页面白名单（登录页/登录接口）
  → 检查 session('sid') 是否存在
  → checkSid(): 校验 sid = encrypt_string(session_id + user_id) 且 session('M') == 当前模块
  → 失败则销毁会话，跳转登录页
```

**第二步：功能权限检查（RBAC）**
```
checkLevel():
  → id==1 的创始人账号拥有全部权限（超级管理员硬编码）
  → 免权限页面白名单（首页、退出、用户中心、清缓存、上传等）
  → 检查当前URL是否在 session('levels') 权限列表中
  → 支持两级匹配: /模块/控制器/方法 或 /模块/控制器
  → 不匹配则输出"权限不足"错误
```

#### 2.2.3 RBAC数据模型

涉及5张核心表：

| 表 | 关联关系 | 说明 |
|----|---------|------|
| `ay_user` | 用户主表 | 存储账号信息 |
| `ay_user_role` | 用户-角色中间表 | ucode + rcode |
| `ay_role` | 角色表 | 角色定义 |
| `ay_role_level` | 角色-权限中间表 | rcode + level（URL路径） |
| `ay_role_area` | 角色-区域中间表 | rcode + acode |
| `ay_menu` | 菜单表 | 菜单URL映射权限URL |

权限加载逻辑位于 `apps/admin/model/IndexModel.php`：
- `getUserMenu()` → 通过多表关联查询获取用户可访问的菜单
- `getUserLevel()` → 获取用户的全部权限URL列表
- `getUserAcode()` → 获取用户可管理的区域编码

### 2.3 API授权流程

`apps/common/ApiController.php` 采用 **AppID+AppSecret+时间戳签名** 机制：

```
请求参数: appid, timestamp, signature

校验流程:
1. 检查 api_open 是否开启
2. 检查 api_auth 是否开启认证
3. 校验时间戳: time() - timestamp <= 15秒（防重放）
4. 校验签名: signature == md5(md5(appid + secret + timestamp))
```

---

## 三、项目存在的问题和风险点

### 🔴 严重风险

#### 1. 域名授权校验被全局关闭

- **位置**：`core/basic/Kernel.php#L275-L278`
- **问题**：`$enableCheck = false` 直接 `return` 跳过了所有授权检查
- **风险**：任何域名都可使用该系统，失去商业授权保护

#### 2. 数据库明文密码硬编码在配置文件

- **位置**：`config/database.php#L14-L16`
- **问题**：数据库账号密码明文存储（`PbootCMS:TPdaJz6dGrnJhGk8`）
- **风险**：一旦源码泄露，数据库直接暴露

#### 3. 密码加密强度不足

- **位置**：`core/function/handle.php#L434-L437`
- **问题**：使用 `md5(md5($password))` 双层MD5，无盐值
- **风险**：易被彩虹表破解，应使用 `password_hash()` / bcrypt / Argon2

#### 4. SQL注入风险（广泛存在）

- **位置**：`core/basic/Model.php` 的 `where()` 方法
- **问题**：字符串条件直接拼接，无预处理绑定。例如 `where("ucode='$ucode'")` 中 `$ucode` 可能包含单引号逃逸
- **示例**：`apps/admin/model/IndexModel.php#L131` 中 `"ay_user.ucode='$ucode'"`
- **风险**：虽然全局 `escape_string()` 做了 `addslashes()`，但在某些数据库字符集（如GBK）下仍存在宽字节注入风险

#### 5. 模板引擎可能存在代码执行风险

- **位置**：`apps/home/controller/ParserController.php`
- **问题**：CMS模板标签 `{pboot:if}...{/pboot:if}` 的条件表达式通过 `symbol()` 函数解析比较，但过滤和绕过防护依赖 `preg_replace_r()` 的递归替换，可能存在逻辑缺陷
- **佐证**：`ParserController.php#L254` 存在混淆代码（base64解码后校验Kernel文件）

### 🟠 高风险

#### 6. XSS跨站脚本风险

- **位置**：`get_user_ip()` 等函数仅用 `htmlspecialchars` 处理，但多处输出直接拼接HTML
- **位置**：`apps/home/controller/ParserController.php` 中站点标签、公司标签、会员标签的输出未统一做上下文敏感的转义
- **风险**：存储型/反射型XSS

#### 7. 文件上传校验不完善

- **位置**：`core/function/file.php#L269-L350`
- **问题**：仅校验文件扩展名白名单，未校验文件真实内容（MIME头/幻数）
- **风险**：攻击者可上传图片马（扩展名合法但内容为PHP），配合文件包含漏洞可GetShell

#### 8. 后台调试模式默认开启

- **位置**：`config/config.php#L5`
- **问题**：`'debug' => true`
- **风险**：生产环境会暴露详细错误信息、路径等敏感信息

#### 9. CSRF防护机制薄弱

- **位置**：`apps/common/AdminController.php#L65-L85`
- **问题**：仅使用简单的 `formcheck` 会话令牌做表单校验，且没有绑定具体请求的一次性Token机制
- **风险**：可能被绕过，存在CSRF攻击可能

### 🟡 中风险

#### 10. 会话安全配置不足

- **位置**：`core/basic/Basic.php#L108-L113`
- **问题**：Cookie的 `secure`（仅HTTPS传输）、`httponly` 参数为 `false` / `null`
- **风险**：JS可读Cookie，HTTPS环境下Cookie仍可明文传输

#### 11. IP获取可伪造

- **位置**：`core/function/handle.php#L130-L146`
- **问题**：优先读取 `HTTP_X_FORWARDED_FOR` 和 `HTTP_CLIENT_IP`，这些是客户端可控的HTTP头
- **风险**：攻击者可伪造IP，绕过IP黑名单/登录锁定

#### 12. API签名可被重放攻击（同源绕过）

- **位置**：`apps/common/ApiController.php#L63`
- **问题**：`strpos($_SERVER['HTTP_REFERER'], get_http_url()) === false` 才校验时间戳，即同源请求不校验时间戳
- **风险**：同源页面抓取的签名可被长期重放

#### 13. 敏感操作缺乏二次认证

- **问题**：删除用户、修改角色、修改系统配置等高危操作仅依赖URL权限检查，无二次密码验证
- **风险**：会话被劫持后可直接执行高危操作

#### 14. 输出Header泄露框架信息

- **位置**：`core/init.php#L19`
- **问题**：`header('X-Powered-By:PbootCMS')` 明确标识CMS系统
- **风险**：便于攻击者针对PbootCMS的已知漏洞进行定向攻击
- **另外**：`Kernel.php` 中多处 `header("X-Diag: ...")` 调试信息泄露内部执行路径

### 🟢 低风险/代码质量问题

#### 15. 全局使用 `addslashes` 而非数据库原生预处理

- **问题**：`escape_string()` 使用 `htmlspecialchars + addslashes` 双重转义，而非PDO/mysqli参数化绑定
- **影响**：与特定字符集组合时存在注入隐患

#### 16. 目录权限过于宽松

- **位置**：`core/function/file.php#L26`
- **问题**：`mkdir($path, 0777, true)` 创建世界可读写目录
- **建议**：使用 0755

#### 17. 后台入口文件名安全

- 虽然使用了 `szadmin.php` 而非默认 `admin.php`，但安全应依赖认证而非隐蔽性

#### 18. 缺少统一的输入验证层

- 各控制器自行使用 `post()` / `get()` 辅助函数，但类型校验不严格，大量参数仅做非空检查

#### 19. 错误处理直接暴露路径

- 自定义错误处理输出完整文件路径和行号，debug模式下更甚

---

## 四、修复建议优先级

| 优先级 | 问题编号 | 建议措施 |
|--------|---------|---------|
| P0（立即修复） | #1 | 恢复授权校验开关，将 `$enableCheck` 设为 `true` |
| P0（立即修复） | #3 | 迁移到 `password_hash()` (bcrypt)，实现历史密码平滑升级 |
| P0（立即修复） | #4 | 重构Model层，改用PDO预处理绑定参数 |
| P1（高优先） | #2 | 将数据库凭据移至环境变量或加密文件 |
| P1（高优先） | #6 | 统一输出转义，区分HTML/JS/URL等上下文 |
| P1（高优先） | #7 | 添加上传文件的幻数检测、文件头校验 |
| P1（高优先） | #8 | 生产环境关闭debug，区分开发/生产配置 |
| P1（高优先） | #9 | 实现基于nonce的一次性CSRF Token |
| P2（中优先） | #10 | 启用Cookie的 Secure、HttpOnly、SameSite 属性 |
| P2（中优先） | #11 | 配置可信代理IP列表，不可信代理下直接用 REMOTE_ADDR |
| P2（中优先） | #13 | 高危操作增加二次验证（密码/验证码/邮件） |
| P2（中优先） | #14 | 移除 X-Powered-By 和 X-Diag 调试头 |
| P3（低优先） | #15-19 | 逐步优化代码质量和安全配置 |

---

> **免责声明**：本报告仅基于静态代码分析得出，实际风险还需结合运行环境、部署配置、WAF等外部防护措施综合评估。
