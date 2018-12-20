# Release Notes

## v0.9.4 (2018-12-20)

### Fixed
- 修复扭蛋失败的问题

## v0.9.2 (2018-09-30)

### Removed
- 移除小电视抽奖相关

### Fixed
- 修复 composer.lock 因 BootCDN 停止维护的影响

## v0.9.1 (2018-05-09)

### Added
- 添加应援团签到功能
- 添加扭蛋机功能（暂时支持普通扭蛋币）

### Changed
- [dev] 当配置文件为空时跳过写入
- 当回调地址为空时跳过

### Fixed
- 兼容 PHP 5.6 版本测试 ([#37](https://github.com/metowolf/BilibiliHelper/issues/37))
- 修复部分接口逻辑问题


## v0.9.0-pre (2018-05-07)

**该版本为非兼容更新，从 0.8.x 升级需要重新覆盖配置文件**

### Added
- 项目重构，为多帐号准备
- 弹幕监听采用 websocket 接口 (https://github.com/varspool/Wrench)
- 更换 guzzle 库，支持 CookieJar (https://github.com/guzzle/guzzle)
- 添加网络代理支持

### Changed
- 修改插件逻辑

### Fixed
- 修复配置文件检测问题
- 修复 debug 信息递归黑洞问题


## v0.8.0 (2018-05-04)

**该版本为非兼容更新，从 0.7.x 升级需要重新覆盖配置文件**

### Added
- 新增小电视抽奖功能 ([#18](https://github.com/metowolf/BilibiliHelper/issues/18))
- 新增配置项检测

### Changed
- 修改部分时间锁逻辑
- 修改部分日志提示

## v0.7.3 (2018-04-28)

### Added
- 新增帐号别名参数

### Changed
- 更新模拟客户端版本 (iOS 6670)
- 修正环境变量刷新逻辑

### Fixed
- 修复部分语法错误
- 修复错误信息回调函数逻辑错误


## v0.7.2 (2018-04-22)

### Added
- 新增令牌刷新机制
- 新增日志通知级别设置

### Changed
- 调整部分日志文案
- 修正 README 的错误 ([#29](https://github.com/metowolf/BilibiliHelper/pull/29))

### Fixed
- 修复每日任务无法领取的问题
- 修复部分逻辑错误


## v0.7.1 (2018-04-21)

### Changed
- 调整部分通知为警告级别

### Fixed
- 修复过早领取宝箱的问题


## v0.7.0 (2018-04-20)

### Added
- 项目重构，拥抱 composer
- 全面更换客户端 API
- 添加用户登录机制 ([#22](https://github.com/metowolf/BilibiliHelper/issues/22))

### Changed
- Require PHP 5.4.0 or newer

### Fixed
- 修复宝箱验证码问题 ([#27](https://github.com/metowolf/BilibiliHelper/issues/27))
