const got = require('../utils/got')
const got_unsafe = require('got')
const chalk = require('chalk')
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
  const list = await getGuardLocal()
  // const list = await getGuardList(uid)

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
        logger.notice(`guard: ${originRoomid} 舰长经验领取成功，${result.data.message}`)
        continue
      }

      if (result.code === 400 && result.msg.includes('领取过')) {
        logger.notice(`guard: ${originRoomid} 舰长经验已经领取过`)
        continue
      }

      if (result.code === 400 && result.msg.includes('早点')) {
        logger.notice(`guard: ${originRoomid} 舰长经验已过期`)
        continue
      }

      if (result.code) {
        throw new Error('guard: 舰长经验领取失败，稍后重试')
      }
    }

    await sleep(5 * 1000 + Math.random() * 60 * 1000)
  }
}

async function getLiveList(page) {
  // 获取房间列表，每页30个房间
  try {
    const response = await got_unsafe.get('https://api.live.bilibili.com/room/v3/Area/getRoomList', {
      headers: {
        'User-Agent': `Mozilla/5.0 BiliDroid/5.45.2 (bbcallen@gmail.com) os/android model/google Pixel 2 mobi_app/android build/5452100 channel/yingyongbao innerVer/5452100 osVer/5.1.1 network/2`
      },
      body: {
        "page": page,
        "page_size": 30
      },
      json: true
    });
    return response.body;
  } catch (error) {
    console.log(error.response.body);
    return false;
  }
}

async function checkLottery(rid) {
  // 检查礼物
  try {
    const response = await got_unsafe.get('https://api.live.bilibili.com/xlive/lottery-interface/v1/lottery/getLotteryInfo?roomid=' + rid, {
      headers: {
        'User-Agent': `Mozilla/5.0 BiliDroid/5.45.2 (bbcallen@gmail.com) os/android model/google Pixel 2 mobi_app/android build/5452100 channel/yingyongbao innerVer/5452100 osVer/5.1.1 network/2`
      },
      json: true
    });
    return response.body;
  } catch (error) {
    console.log(error.response.body);
    return false;
  }
}

async function getGuardLocal() {
  // 本地获取舰长列表，不清楚是否会触发bili的安全风险，例如封锁ip，但是所有的内容可以匿名获取，应该不会影响到账号
  // 会大量占用网络资源，但是会比原始方法可能来的更加快速及准确，漏领几率低
  logger.notice(`guard: 使用本地方法拉取舰长列表中`)

  // 初始化计数，临时抓取一份列表读取总数
  const tmpBody = await getLiveList(0);
  let count = (tmpBody && tmpBody.code === 0) ? (tmpBody.data.count / 30) + 1 : -1;
  if (count === -1) return false;

  // 初始化异步数组，for数组用于forEach异步，flag用于异步完成的标记
  let forArr = [];
  let flagMain = 0;
  for (let i = 0; i < count; ++i)forArr[i] = i;

  // 初始化返回值
  let retArr = [];

  // 循环拉取信息，利用异步函数快速抓取
  forArr.forEach(async (i) => {
    const asyncBody = await getLiveList(i);

    // 当拉取成功
    if (asyncBody && asyncBody.code === 0) {

      // 声明异步子状态
      let flagAsync = 0;

      // 获取该页房间列表
      const backList = asyncBody.data.list;
      backList.forEach(async (eachRoom) => {

        // web_pendent有内容时，房间内有活动状态，大概率有舰长
        if (eachRoom.web_pendent) {

          // 获取房间的礼物信息
          const lotteryBody = await checkLottery(eachRoom.roomid);

          // 拉取成功
          if (lotteryBody && lotteryBody.code === 0) {

            // 声明异步子状态
            let flagLottery = 0;

            // 检查舰长信息
            const guardInfo = lotteryBody.data.guard;
            guardInfo.forEach(eachGuard => {
              // 将舰长信息推入返回值
              let tmp = { "GuardId": eachGuard.id, "OriginRoomId": eachRoom.roomid };
              retArr.push(tmp);
              ++flagLottery;
            })

            // 等待异步完成
            while (flagLottery != guardInfo.length) {
              await sleep(100);
            }
          }
          else {
            if (config.get('debug') && lotteryBody) console.log(lotteryBody.msg);
          }
        }

        ++flagAsync;
      });

      // 等待异步完成
      while (flagAsync != backList.length) {
        await sleep(100);
      }
    } else {
      if (config.get('debug') && asyncBody) console.log(asyncBody.msg);
    }

    // 增加一次flag，表示该次异步已完成
    ++flagMain;
  });

  // 等待异步完成
  while (flagMain != forArr.length) {
    await sleep(500);
  }

  if (config.get('debug')) console.log(chalk.gray(JSON.stringify(retArr)))
  logger.notice(`guard: 拉取完成`)
  return retArr;
}

async function getGuardList(uid) {
  let { body } = await got_unsafe.get('http://118.25.108.153:8080/guard', {
    headers: {
      'User-Agent': `bilibili-live-tools/${uid}`
    },
    timeout: 60000,
    json: true,
    hooks: {
      beforeRequest: [
        options => {
          if (config.get('debug')) console.log(`${chalk.cyan('GET')} ${chalk.yellow('http://118.25.108.153:8080/guard')}`)
        }
      ],
      afterResponse: [
        response => {
          if (config.get('debug')) console.log(chalk.gray(response.body))
          return response
        }
      ]
    }
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
      share.lock = Date.now() + 5 * 60 * 1000
      // share.lock = Date.now() + 60 * 60 * 1000
    })
}
