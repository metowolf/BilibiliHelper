const moment = require('moment')

const got = require('../utils/got')
const share = require('../utils/share').silver
const sign = require('../utils/sign')
const logger = require('../utils/logger')

const main = async () => {
  if (share.task === 0) await getSilver()
  else await openSilver()
}

const openSilver = async () => {
  let data
  {
    let {body} = await got.get('https://api.live.bilibili.com/mobile/freeSilverAward', {query: sign({}), json: true})
    if (body.code) throw new Error('银瓜子宝箱开启失败')
    data = body.data
  }

  logger.notice(`宝箱开启成功，瓜子 ${data.silver}(+${data.awardSilver})`)

  share.lock = Date.now() + 10 * 1000
  share.task = 0
}

const getSilver = async () => {
  let data
  {
    let {body} = await got.get('https://api.live.bilibili.com/lottery/v1/SilverBox/getCurrentTask', {query: sign({}), json: true})
    if (body.code === -10017) {
      logger.notice(body.message)
      let unix = moment().add(1, 'd').startOf('day').add(10, 'm').format('x')
      share.lock = parseInt(unix, 10)
      return
    }
    if (body.code) throw new Error('银瓜子宝箱领取失败')
    data = body.data
  }
  logger.notice(`领取宝箱成功，内含 ${data.silver} 个瓜子`)
  logger.info(`等待 ${data.minute} 分钟后打开宝箱`)

  share.task = data.time_start
  share.lock = Date.now() + data.minute * 60 * 1000 + 10 * 1000
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 5 * 60 * 1000
    })
}
