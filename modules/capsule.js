const got = require('../utils/got')
const share = require('../utils/share').capsule
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')

const getCoin = async () => {
  logger.info('正在查询扭蛋币余额')
  const { body } = await got.get('https://api.live.bilibili.com/xlive/web-ucenter/v1/capsule/get_detail', { json: true })
  if (body.code) throw new Error('扭蛋币余额查询异常')
  const coin = (body.data && body.data.normal && body.data.normal.coin) || 0 // 没币时 normal 为 null
  logger.info(`当前还有 ${coin} 枚扭蛋币`)
  return coin
}

// 按 100/10/1 的档位开箱，返回剩余扭蛋币
const openCapsule = async count => {
  const csrf = got.getCsrf()
  const { body } = await got.post('https://api.live.bilibili.com/xlive/web-ucenter/v1/capsule/open_capsule', {
    body: { csrf, csrf_token: csrf, count, type: 'normal', platform: 'h5' },
    form: true,
    json: true,
  })
  if (body.code) throw new Error('扭蛋失败，稍后重试')
  for (const item of body.data.awards) {
    logger.notice(`扭蛋成功，获得 ${item.num} 个 ${item.name}`)
  }
  return body.data.coin || 0
}

const main = async () => {
  let coin = await getCoin()
  for (let step = 100; step >= 1 && coin; step = Math.floor(step / 10)) {
    while (coin >= step) {
      coin = await openCapsule(step)
      await sleep(2000)
    }
  }
}

module.exports = () => {
  if (process.env.DISABLE_CAPSULE === 'true') return
  if (share.lock > Date.now()) return
  return main()
    .then(() => { share.lock = Date.now() + 60 * 60 * 1000 })
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 60 * 60 * 1000
    })
}