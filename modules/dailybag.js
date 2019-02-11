const moment = require('moment')

const got = require('../utils/got')
const share = require('../utils/share').dailybag
const sign = require('../utils/sign')
const logger = require('../utils/logger')

const main = async () => {
  if (share.lock_web < Date.now()) await web()
  if (share.lock_mobile < Date.now()) await mobile()
}

const web = async () => {
  {
    let {body} = await got.get('https://api.live.bilibili.com/gift/v2/live/receive_daily_bag', {json: true})
    if (body.code) throw new Error('每日礼包领取失败 (web)')
  }
  {
    let unix = moment().add(1, 'd').startOf('day').add(10, 'm').format('x')
    share.lock_web = parseInt(unix, 10)
  }
}

const mobile = async () => {
  {
    let {body} = await got.get('https://api.live.bilibili.com/AppBag/sendDaily', {query: sign({}), json: true})
    if (body.code) throw new Error('每日礼包领取失败 (app)')
  }
  {
    let unix = moment().add(1, 'd').startOf('day').add(10, 'm').format('x')
    share.lock_mobile = parseInt(unix, 10)
  }
}

module.exports = () => {
  return main()
    .catch(e => {
      logger.error(e.message)
      share.lock_web = Date.now() + 10 * 60 * 1000
      share.lock_mobile = Date.now() + 10 * 60 * 1000
    })
}
