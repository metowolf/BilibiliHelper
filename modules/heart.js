const got = require('../utils/got')
const share = require('../utils/share').heart
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const config = require('../utils/config')
let lastInterval = 0

const main = async () => {
  await heart_web()
  await heart_mobile()
}

const heart_web = async () => {
  {
    let baseStr = Buffer.from(`${lastInterval}|${config.get('room_id')}|1|0`).toString('base64')
    let {body} = await got.get('https://live-trace.bilibili.com/xlive/rdata-interface/v1/heartbeat/webHeartBeat?pf=web&hb=' + baseStr, {json: true})
    if (body.code) throw new Error('直播间心跳异常 (web)')
    lastInterval = body.data.next_interval
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
  if (process.env.DISABLE_HEART === 'true') return
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
