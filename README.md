
<p align="center"><img width="300px" src="https://i.loli.net/2018/04/20/5ad97bd395912.jpeg"></p>

<p align="center">
<img src="https://img.shields.io/badge/version-0.10.0-green.svg?longCache=true&style=for-the-badge">
<img src="https://img.shields.io/badge/license-mit-blue.svg?longCache=true&style=for-the-badge">
</p>


# BilibiliHelper
B 站挂机实用脚本，[>> 点此返回 PHP 旧版](https://github.com/metowolf/BilibiliHelper/tree/0.9x)

## 功能组件

|plugin      |version  |description   |
|------------|---------|--------------|
|Auth        |19.02.11 |帐号登录组件    |
|Capsule     |19.02.12 |扭蛋机(普通)    |
|DailyBag    |19.02.11 |每日礼包领取    |
|Group       |19.02.11 |应援团签到     |
|Heart       |19.02.11 |双端直播间心跳  |
|Silver      |19.02.11 |免费宝箱领取    |
|Silver2Coin |19.02.12 |银瓜子兑换硬币  |
|SmallTV     |开发中    |小电视抽奖     |
|Task        |19.02.11 |每日任务       |


## 未完成功能
|待续|
|-------|
|总督检测|
|节奏风暴|


## 环境依赖
|Requirement|
|-------|
|Node.js (>=8.x)|


## 搭建指南 (Docker)

[Docker 安装脚本](https://get.docker.com)

[Docker Image](https://hub.docker.com/r/metowolf/bilibilihelper)

下方示范中各个功能的命令行参数可互相配合使用，更多信息请查阅[Docker 官方手册](https://docs.docker.com/engine/reference/commandline/docker/)

**注意**：Docker日志默认不会定期删除，长期运行容器可能会导致日志文件较大。运行容器是请注意加入限制条件（下方基础使用`log-opt`部分）

### 使用

基础使用
```bash
docker run -d \ 
           --restart=unless-stopped \
           --log-driver json-file \
           --log-opt max-size=10m \
           --log-opt max-file=10 \
           -e USERNAME={用户名} \
           -e PASSWORD={密码} \
           metowolf/bilibilihelper
```

指定赠送礼物的房间 (默认房间ID:3746256)
```bash
docker run -d --restart=unless-stopped -e USERNAME={用户名} -e PASSWORD={密码} -e ROOM_ID={房间ID} metowolf/bilibilihelper
```

更多参数指定请查看[Dockerfile](/Dockerfile)

#### 多用户

重复上述指令，替换对应变量即可

建议开启多个用户实例的用户给对应容器命名，方便检查容器日志：
```bash
docker run -d --restart=unless-stopped -e USERNAME={用户名} -e PASSWORD={密码} --name={容器名字} metowolf/bilibilihelper
```

### 查找/管理(查看日志，停止服务，etc.)

找到所有容器(第一列为容器ID)
```bash
docker ps -a --filter "ancestor=metowolf/bilibilihelper
```

查看容器日志
```bash
docker logs {容器ID}
```

停止服务
```bash
docker stop {容器ID}
```

```重启服务
docker restart {容器ID}
```

## 搭建指南 (Node.js)
施工中

## License 许可证

本项目基于 MIT 协议发布，并增加了 SATA 协议。

当你使用了使用 SATA 的开源软件或文档的时候，在遵守基础许可证的前提下，你必须马不停蹄地给你所使用的开源项目 “点赞” ，比如在 GitHub 上 star，然后你必须感谢这个帮助了你的开源项目的作者，作者信息可以在许可证头部的版权声明部分找到。

本项目的所有代码文件、配置项，除另有说明外，均基于上述介绍的协议发布，具体请看分支下的 LICENSE。

此处的文字仅用于说明，条款以 LICENSE 文件中的内容为准。
