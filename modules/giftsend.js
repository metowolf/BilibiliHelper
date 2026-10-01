const got = require('../utils/got')
const share = require('../utils/share').giftsend
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')
const config = require('../utils/config')

// 补全投喂所需的 uid / 主播 ruid / 真实房间号
const initRoomInfo = async () => {
  const { body: nav } = await got.get('https://api.bilibili.com/x/web-interface/nav', { json: true })
  if (nav.code !== 0 || !nav.data.isLogin) throw new Error('获取用户信息失败')
  share.uid = nav.data.mid

  const { body: room } = await got.get('https://api.live.bilibili.com/room/v1/Room/get_info', {
    query: { id: config.get('room_id') },
    json: true,
  })
  if (room.code) throw new Error('获取直播间信息失败')
  share.ruid = room.data.uid
  share.roomid = room.data.room_id
}

const getBagList = async () => {
  const { body } = await got.get('https://api.live.bilibili.com/gift/v2/gift/bag_list', {
    query: sign({}),
    json: true,
  })
  if (body.code) throw new Error('背包查看失败')
  return body.data
}

// 投喂 1 小时内到期的礼物
const sendGift = async item => {
  const { body } = await got.post('https://api.live.bilibili.com/gift/v2/live/bag_send', {
    body: sign({
      coin_type: 'silver',
      gift_id: item.gift_id,
      ruid: share.ruid,
      uid: share.uid,
      biz_id: share.roomid,
      gift_num: item.gift_num,
      data_source_id: '',
      data_behavior_id: '',
      bag_id: item.bag_id,
    }),
    form: true,
    json: true,
  })
  if (body.code) logger.error(`尝试向直播间投喂 ${item.gift_name} 失败`)
  else logger.notice(`成功向直播间 ${share.roomid} 投喂了 ${item.gift_num} 个 ${item.gift_name}`)
}

const main = async () => {
  if (!share.ruid) await initRoomInfo()
  const bag = await getBagList()
  for (const item of bag.list) {
    if (item.expire_at >= bag.time && item.expire_at <= bag.time + 3600) {
      await sendGift(item)
      await sleep(2000)
    }
  }
}

module.exports = () => {
  if (process.env.DISABLE_GIFTSEND === 'true') return
  if (share.lock > Date.now()) return
  return main()
    .then(() => { share.lock = Date.now() + 60 * 60 * 1000 })
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 10 * 60 * 1000
    })
}