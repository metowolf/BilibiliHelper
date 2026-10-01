const got = require('../utils/got')
const share = require('../utils/share').silver2coin
const logger = require('../utils/logger')
const tomorrow = require('../utils/tomorrow')

// 每天最多用银瓜子兑换 1 个硬币
const main = async () => {
  const csrf = got.getCsrf()
  if (!csrf) return logger.warning('csrf 未获取，跳过银瓜子兑换')

  const { body: status } = await got.get('https://api.live.bilibili.com/pay/v1/Exchange/getStatus', { json: true })
  if (status.code) throw new Error('硬币兑换状态获取失败')
  if (!status.data.silver_2_coin_left) {
    logger.warning('今日兑换额度已用完（每天限 1 个硬币）')
    share.lock = tomorrow(20)
    return
  }

  const { body } = await got.post('https://api.live.bilibili.com/pay/v1/Exchange/silver2coin', {
    body: { num: 1, csrf, csrf_token: csrf },
    form: true,
    json: true,
  })
  if (body.code) throw new Error('硬币兑换失败: ' + body.message)
  logger.notice('硬币兑换成功')
  share.lock = tomorrow(20)
}

module.exports = () => {
  if (process.env.DISABLE_SILVER2CION === 'true') return // 环境变量名沿用上游拼写
  if (share.lock > Date.now()) return
  return main().catch(e => {
    logger.error(e.message)
    share.lock = Date.now() + 60 * 60 * 1000
  })
}