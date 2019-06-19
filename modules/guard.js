const got = require('../utils/got')
const got_unsafe = require('got')
const config = require('../utils/config')
const logger = require('../utils/logger')
const share = require('../utils/share').guard
const sleep = require('../utils/sleep')

let csrfToken
let list_cache = []

const getCsrf = () => {
  const cookies = got.defaults.options.cookieJar.getCookiesSync('https://api.bilibili.com/')
  for (const cookie of cookies) {
    const found = `${cookie}`.match(/bili_jct=([0-9a-f]*)/i)
    if (found) return found[1]
  }
  throw new Error('guard: csrf 提取失败')
}

const main = async () => {

  // 锁定流程，防止重复执行
  share.lock = Date.now() + 24 * 60 * 60 * 1000

  csrfToken = getCsrf()

  const uid = config.get('uid', '')
  if (uid === '') throw new Error('uid获取失败')

  // 获取列表
  const list = await getGuardList(uid)

  const originList = list.filter(item => !list_cache.includes(item.GuardId))
  if (list_cache.length > 10000) list_cache.splice(0, 9000)

  for (const currentItem of originList) {
    const guardId = currentItem.GuardId
    const originRoomid = currentItem.OriginRoomId

    // 记录已经检查过的 GuardId
    list_cache.push(guardId)

    // 非特定时间跳过领取
    const guardHours = config.get('guard.hours', [])
    if (!guardHours.includes((new Date).getHours())) {
      logger.debug('guard：非特定时间跳过领取')
      continue
    }

    // 概率性跳过领取
    const guardPercent = config.get('guard.percent', 100)
    if (Math.random() * 100 >= guardPercent) {
      logger.debug('guard：概率性跳过领取')
      continue
    }

    // 检测是否是真实存在的room
    const isTrueRoom = await checkTrueRoom(originRoomid)
    if (isTrueRoom) {

      // 如果已经在这个房间就不用再进一遍
      if (share.lastGuardRoom !== originRoomid) {
        await goToRoom(originRoomid)
        await sleep(2000 + Math.random() * 2000)
        share.lastGuardRoom = originRoomid
      }

      const result = await getLottery(originRoomid, guardId)

      if (result.code === 0) {
        logger.notice(`guard: ${originRoomid} 舰长经验领取成功，${result.msg}`)
        continue
      }

      if (result.code === 400 && result.msg.includes('领取过')) {
        logger.notice(`guard: ${originRoomid} 舰长经验已经领取过`)
        continue
      }

      if (result.code) {
        throw new Error('guard: 舰长经验领取失败，稍后重试')
      }
    }

    await sleep(5 * 1000)
  }
}

async function getGuardList(uid) {
  const { body } = await got_unsafe.get('http://118.25.108.153:8080/guard', {
    headers: {
      'User-Agent': `bilibili-live-tools/${uid}`
    },
    timeout: 20000,
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
    logger.warning('guard: 获取房间信息失败')
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
  if (process.env.DISABLE_GUARD === 'true') return
  if (share.lock > Date.now()) return
  return main()
    .then(() => {
      share.lock = Date.now() + 5 * 60 * 1000
    })
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 60 * 60 * 1000
    })
}
