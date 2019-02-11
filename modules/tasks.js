const moment = require('moment')

const got = require('../utils/got')
const share = require('../utils/share').tasks
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')

const main = async () => {
  logger.info('检查每日任务')

  let {body} = await got.get('https://api.live.bilibili.com/i/api/taskInfo', {json: true})
  if (body.code) throw new Error('每日任务获取失败')

  share.count = 0
  if (body.data.sign_info) await check_sign_info()
  if (body.data.double_watch_info) await check_double_watch_info(body.data.double_watch_info)

  if (share.count >= 2) {
    let unix = moment().add(1, 'd').startOf('day').add(10, 'm').format('x')
    share.lock = parseInt(unix, 10)
    return
  }
  share.lock = Date.now() + 10 * 60 * 1000
}

const check_double_watch_info = async data => {
  {
    logger.info('检查任务「双端观看直播」')
    if (data.status === 2) {
      logger.notice('「双端观看直播」奖励已经领取')
      share.count += 1
      return
    }
    if (data.mobile_watch !== 1 || data.web_watch !== 1) {
      logger.info('「双端观看直播」未完成，请等待')
      return
    }
  }
  {
    logger.info('领取「双端观看直播」奖励')
    let payload = {
      task_id: 'double_watch_task',
    }
    let {body} = await got.post('https://api.live.bilibili.com/activity/v1/task/receive_award', {
      body: sign(payload),
      form: true,
      json: true,
    })
    if (body.code) throw new Error('「双端观看直播」奖励领取失败')
    logger.notice('「双端观看直播」奖励领取成功')
    for (let item of data.awards) {
      logger.notice(`获得 ${item.name} × ${item.num}`)
    }
  }
}

const check_sign_info = async () => {
  {
    logger.info('检查任务「每日签到」')
    let {body} = await got.get('https://api.live.bilibili.com/sign/GetSignInfo', {json: true})
    if (body.code) throw new Error('任务「每日签到」获取失败')
    if (body.data.status) {
      logger.notice('「每日签到」奖励已经领取')
      share.count += 1
      return
    }
  }
  {
    logger.info('正在尝试网页签到')
    let {body} = await got.get('https://api.live.bilibili.com/sign/doSign', {json: true})
    if (body.code === 0 && body.message === 'OK') {
      logger.info(`签到成功，您已经连续签到 ${body.data.hadSignDays} 天，获得${body.data.text}${body.data.specialText}`)
      return
    }
  }
  await sleep(2000)
  {
    logger.info('正在尝试客户端签到')
    let {body} = await got.get('https://api.live.bilibili.com/appUser/getSignInfo', {query: sign({}), json: true})
    if (body.code === 0 && body.message === 'OK') {
      logger.info(`签到成功，您已经连续签到 ${body.data.hadSignDays} 天，获得${body.data.text}${body.data.specialText}`)
      return
    }
  }
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 10 * 60 * 1000
    })
}
