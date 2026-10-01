/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 *
 * Copyright 2019, metowolf
 * Released under the MIT license
 */

const auth = require('./modules/auth')
const tasks = require('./modules/tasks')
const heart = require('./modules/heart')
const group = require('./modules/group')
const capsule = require('./modules/capsule')
const giftsend = require('./modules/giftsend')
const dailybag = require('./modules/dailybag')
const silver2coin = require('./modules/silver2coin')

const init = require('./utils/init')
const sleep = require('./utils/sleep')

// 免费宝箱(silver)、舰长亲密度(guard)对应的服务已下线，不再加载
;(async () => {
  init()
  while (true) {
    await auth()
    await tasks()
    await heart()
    await group()
    await capsule()
    await giftsend()
    await dailybag()
    await silver2coin()
    await sleep(1000)
  }
})()