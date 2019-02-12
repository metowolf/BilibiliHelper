const got = require('../utils/got')
const share = require('../utils/share').heart
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const config = require('../utils/config')

const main = async () => {
  await heart_web()
  await heart_mobile()
}

const heart_web = async () => {
  {
    let {body} = await got.get('https://api.live.bilibili.com/User/userOnlineHeart', {json: true})
    if (body.code) throw new Error('直播间心跳异常 (web)')
  }
}

const heart_mobile = async () => {
  {
    let payload = {
      room_id: config.get('room_id'),
    }
    let {body} = await got.post('https://api.live.bilibili.com/mobile/userOnlineHeart', {
      body: sign(payload),
      form: true,
      json: true,
    })
    if (body.code) throw new Error('直播间心跳异常 (app)')
  }
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .then(() => {
      share.lock = Date.now() + 5 * 60 * 1000
    })
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 5 * 60 * 1000
    })
}
