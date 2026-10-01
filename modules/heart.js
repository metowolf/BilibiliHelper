const got = require('../utils/got')
const share = require('../utils/share').heart
const logger = require('../utils/logger')
const config = require('../utils/config')

// 直播间短号 -> 真实 room_id 的映射结果与心跳序号
const room = { id: 0, seq: 0 }

const initRoom = async roomId => {
  try {
    const { body } = await got.get(`https://api.live.bilibili.com/room/v1/Room/room_init?id=${roomId}`, { json: true })
    if (body.code === 0 && body.data.room_id) {
      room.id = body.data.room_id
      room.seq = 1
    }
  } catch (e) {
    logger.warning('获取直播间信息失败: ' + e.message)
  }
}

const heartbeat = async () => {
  const roomId = Number(config.get('room_id'))
  const uid = config.get('uid')
  if (!roomId) return logger.warning('未配置 ROOM_ID，跳过心跳')
  if (!uid) return logger.warning('未获取到 UID，跳过心跳')

  if (room.id !== roomId) await initRoom(roomId)
  room.seq++

  // 网页端心跳已下线，统一走移动端
  const csrf = got.getCsrf()
  const { body } = await got.post('https://api.live.bilibili.com/mobile/userOnlineHeart', {
    body: { room_id: room.id, uid, csrf, csrf_token: csrf },
    form: true,
    json: true,
  })
  if (body.code !== 0) throw new Error(body.message || '心跳失败')
  logger.info('心跳发送成功')
}

module.exports = () => {
  if (process.env.DISABLE_HEART === 'true') return
  if (share.lock > Date.now()) return
  return heartbeat()
    .then(() => { share.lock = Date.now() + 5 * 60 * 1000 }) // 每 5 分钟一次
    .catch(e => {
      logger.error('心跳失败: ' + e.message)
      share.lock = Date.now() + 5 * 60 * 1000
    })
}