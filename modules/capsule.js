const moment = require('moment')

const got = require('../utils/got')
const share = require('../utils/share').capsule
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')

const getCsrf = async () => {
  let cookies = got.defaults.options.cookieJar.getCookiesSync('https://api.bilibili.com/')
  for (let cookie of cookies) {
    let found = `${cookie}`.match(/bili_jct=([0-9a-f]*)/i)
    if (found) return found[1]
  }
  throw new Error('csrf 提取失败')
}

const main = async () => {
  let coin = await getCoin()
  let step = 100
  while (coin && step) {
    while (coin >= step) {
      coin = await openCapsule(step)
      await sleep(2000)
    }
    step = Math.floor(step / 10)
  }
}

const openCapsule = async step => {
  let csrf = await getCsrf()
  let payload = {
    csrf,
    csrf_token: csrf,
    count: step,
    type: 'normal',
    platform: 'h5',
  }
  let {body} = await got.post('https://api.live.bilibili.com/xlive/web-ucenter/v1/capsule/open_capsule', {
    body: payload,
    form: true,
    json: true,
  })
  if (body.code) throw new Error('扭蛋失败，稍后重试')
  for (let item of body.data.awards) {
    logger.notice(`扭蛋成功，获得 ${item.num} 个 ${item.name}`)
  }
  return body.data.coin || 0
}

const getCoin = async () => {
  logger.info('正在查询扭蛋币余额')
  let {body} = await got.get('https://api.live.bilibili.com/xlive/web-ucenter/v1/capsule/get_detail', {json: true})
  if (body.code) throw new Error('扭蛋币余额查询异常')
  logger.info(`当前还有 ${body.data.normal.coin} 枚扭蛋币`)
  return body.data.normal.coin
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .then(() => {
      share.lock = Date.now() + 60 * 60 * 1000
    })
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 60 * 60 * 1000
    })
}
