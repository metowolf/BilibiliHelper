# BilibiliHelper
B 站自动领瓜子、直播挂机脚本

## 功能
 - 每日签到
 - 每 5 分钟自动发送心跳包
 - 每日自动领宝箱（瓜子）

## 安装
 1. 下载 `index.php`, `Bilibili.php` 两个文件，并放置在同一个目录下
 2. 修改 `index.php`, 替换 cookie 为 B 站直播间的 cookie
 3. 键入命令 `php index.php`, 试运行（可选）
 4. 在 `crontab` 中设置 `3 0 * * * php [path_to_yourfile]/index.php > log.txt`

## 注意事项
 1. 虽然脚本为 PHP，但由于需要长时间运行，因此不能通过访问网页来使用
 2. 需要额外安装 php-gd、php-curl 组件

## FAQ

Q: 如何同时挂多个帐号？
A: 可以复制 `index.php` 为 `index1.php`, 在 `crontab` 多添加一条记录

## License
BilibiliHelper is under the MIT license.
