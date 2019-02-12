const got = require('../utils/got')
const share = require('../utils/share').giftsend
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')
const config = require('../utils/config')

const main = async () => {
  if (!share.ruid) await getRoomInfo()
  let data = await getBagList()
  for (let item of data.list) {
    if (item.expire_at >= data.time && item.expire_at <= data.time + 3600) {
      await giftSend(item)
      await sleep(2000)
    }
  }
}

const giftSend = async (data) => {
  let payload = {
    coin_type: 'silver',
    gift_id: data.gift_id,
    ruid: share.ruid,
    uid: share.uid,
    biz_id: share.roomid,
    gift_num: data.gift_num,
    data_source_id: '',
    data_behavior_id: '',
    bag_id: data.bag_id,
  }
  let {body} = await got.post('https://api.live.bilibili.com/gift/v2/live/bag_send', {
    body: sign(payload),
    form: true,
    json: true,
  })
  if (body.code) logger.error(`尝试向直播间投喂 ${data.gift_name} 失败`)
  else logger.notice(`成功向直播间 ${payload.biz_id} 投喂了 ${data.gift_num} 个 ${data.gift_name}`)
}

const getBagList = async () => {
  let {body} = await got.get('https://api.live.bilibili.com/gift/v2/gift/bag_list', {query: sign({}), json: true})
  if (body.code) throw new Error('背包查看失败')
  return body.data
}

const getRoomInfo = async () => {
  logger.info('正在补全用户信息')
  {
    let {body} = await got.get('https://account.bilibili.com/api/myinfo/v2', {query: sign({}), json: true})
    if (body.code) throw new Error('获取用户信息失败')
    share.uid = body.mid
  }
  logger.info('正在补全直播间信息')
  {
    let payload = {
      id: config.get('room_id'),
    }
    let {body} = await got.get('https://api.live.bilibili.com/room/v1/Room/get_info', {query: sign(payload), json: true})
    if (body.code) throw new Error('获取用户信息失败')
    share.ruid = body.data.uid
    share.roomid = body.data.room_id
  }
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .then(() => {
      share.lock = Date.now() + 60 * 60 * 1000
    })
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 10 * 60 * 1000
    })
}
