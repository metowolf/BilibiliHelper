const got = require('../utils/got')
const share = require('../utils/share').tasks
const logger = require('../utils/logger')
const tomorrow = require('../utils/tomorrow')

// 用 nav 接口确认登录态
const getUserInfo = async () => {
  try {
    const { body } = await got.get('https://api.bilibili.com/x/web-interface/nav', { json: true })
    if (body.code === 0 && body.data.isLogin) return body.data
  } catch (e) {}
  return null
}

// 主站每日签到（旧接口已失效，today/exp 作为“今天是否已拿满经验”的判断）
const checkSign = async () => {
  try {
    const { body } = await got.get('https://api.bilibili.com/x/web-interface/signing', { json: true })
    if (body.code === 0 && body.data.sign_in_days > 0) {
      logger.notice(`主站已签到，连续签到 ${body.data.sign_in_days} 天`)
      return true
    }
  } catch (e) {}

  try {
    const { body } = await got.get('https://api.bilibili.com/x/web-interface/coin/today/exp', { json: true })
    if (body.code === 0) {
      logger.notice(`今日投币经验: ${body.data}`)
      return true
    }
  } catch (e) {}

  return false
}

const main = async () => {
  logger.info('检查每日任务')
  if (!(await getUserInfo())) {
    logger.warning('未登录，跳过每日任务')
    return
  }

  if (await checkSign()) {
    share.lock = tomorrow(8 * 60) // 明早 8 点再检查
    logger.notice('今日任务完成，下次检查: ' + new Date(share.lock).toLocaleString())
  } else {
    share.lock = Date.now() + 10 * 60 * 1000
  }
}

module.exports = () => {
  if (process.env.DISABLE_TASKS === 'true') return
  if (share.lock > Date.now()) return
  return main().catch(e => {
    logger.error(e.message)
    share.lock = Date.now() + 10 * 60 * 1000
  })
}