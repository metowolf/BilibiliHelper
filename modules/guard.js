const got = require('../utils/got')
const config = require('../utils/config')
const logger = require('../utils/logger')
const share = require('../utils/share').guard
const sleep = require('../utils/sleep')

let csrfToken
const has_list = []
let totalAddSum = 0

const getCsrf = () => {
  const cookies = got.defaults.options.cookieJar.getCookiesSync(
    'https://api.bilibili.com/'
  )
  for (const cookie of cookies) {
    const found = `${cookie}`.match(/bili_jct=([0-9a-f]*)/i)
    if (found) return found[1]
  }
  throw new Error('csrf 提取失败')
}

const main = async () => {
  if (csrfToken == null) csrfToken = getCsrf()
  const uid = config.get('uid', '')
  if (uid === '') throw new Error('uid获取失败')
  const list = await getGuardList(uid)

  // has_list 大于 10000 的时候清理前 9000 条
  if (has_list.length > 10000) has_list.splice(0, 9000);

  const originList = list.filter(item => !has_list.includes(item.GuardId))

  for (const currentItem of originList) {
    const { GuardId, OriginRoomId } = currentItem
    // 检测是否是真实存在的room
    const isTrueRoom = await checkTrueRoom(OriginRoomId)
    if (isTrueRoom) {
      // 如果已经在这个房间就不用再进一遍
      if (share.lastGuardRoom !== OriginRoomId) {
        await goToRoom(OriginRoomId)
        await sleep(1200 + Math.random() * 800);
        share.lastGuardRoom = OriginRoomId
      }

      // 记录已经领过的 item
      has_list.push(GuardId)

      const result = await getLottery(OriginRoomId, GuardId)
      if (result.code === 0) {
        const msg = result.data.message;
        const addSum = msg.match(/\+(\d+)~/)[1] || 0;
        totalAddSum += +addSum;
        logger.notice(`【${OriginRoomId}】 ${result.data.message}, 累计增加 ${totalAddSum} 点`)
      } else if (result.code === 400 && result.msg.includes('领取过')) {
        logger.info(`【${OriginRoomId}】 ${result.msg}`)
      } else if (result.code === 400 && result.msg.includes('被拒绝')) {
        logger.warning(`【${OriginRoomId}】 ${result.data.message}`)
      } else {
        logger.error(`【${OriginRoomId}】 领取出错`)
      }
    } else logger.warning(`【${OriginRoomId}】 检测到钓鱼直播`)
    await sleep(1800 + Math.random() * 1000);
  }
}

async function getGuardList(uid) {
  const { body } = await got.get('http://118.25.108.153:8080/guard', {
    headers: {
      'User-Agent': `bilibili-live-tools/${uid}`
    },
    json: true
  })
  return body
}

async function checkTrueRoom(roomId) {
  const { body } = await got.get(
    `https://api.live.bilibili.com/room/v1/Room/room_init?id=${roomId}`,
    { json: true }
  )
  if (body.code === 0) {
    const { is_hidden, is_locked, encrypted } = body.data
    return !(is_hidden || is_locked || encrypted)
  } else {
    logger.warning('获取房间信息失败')
    return false
  }
}

async function goToRoom(roomId) {
  const { body } = await got.post(
    'https://api.live.bilibili.com/room/v1/Room/room_entry_action',
    {
      body: {
        room_id: roomId,
        csrf_token: csrfToken
      },
      form: true,
      json: true
    }
  )
  return body
}

async function getLottery(roomId, guardId) {
  const { body } = await got.post(
    'https://api.live.bilibili.com/lottery/v2/lottery/join',
    {
      body: {
        roomid: roomId,
        id: guardId,
        type: 'guard',
        csrf_token: csrfToken
      },
      form: true,
      json: true
    }
  )
  return body
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
