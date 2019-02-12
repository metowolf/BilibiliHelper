const got = require('../utils/got')
const share = require('../utils/share').silver2coin
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const tomorrow = require('../utils/tomorrow')

const main = async () => {
  await silver2coin()
}

const silver2coin = async () => {
  {
    logger.info('查询硬币兑换状态')
    let {body} = await got.get('https://api.live.bilibili.com/pay/v1/Exchange/getStatus', {query: sign({}), json: true})
    if (body.code) throw new Error('硬币兑换状态获取失败')
    if (!body.data.silver_2_coin_left) {
      logger.warning('每天最多只能兑换 1 个硬币')
      share.lock = tomorrow(20)
      return
    }
  }
  {
    logger.info('正在兑换硬币')
    let payload = {
      num: 1,
    }
    let {body} = await got.post('https://api.live.bilibili.com/pay/v1/Exchange/silver2coin', {
      body: sign(payload),
      form: true,
      json: true,
    })
    if (body.code) throw new Error('硬币兑换失败')
    logger.notice('硬币兑换成功')
    share.lock = tomorrow(20)
  }
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 60 * 60 * 1000
    })
}
