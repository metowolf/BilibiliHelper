const got = require('../utils/got')
const share = require('../utils/share').group
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')
const tomorrow = require('../utils/tomorrow')

// 应援团签到：旧版 POST 已改 GET
const signGroup = async group => {
  const { body } = await got.get('https://api.vc.bilibili.com/link_setting/v1/link_setting/sign_in', {
    query: {
      group_id: group.group_id,
      owner_id: group.owner_uid,
      csrf: got.getCsrf(),
    },
    json: true,
    headers: { Referer: 'https://link.bilibili.com/' },
  })
  if (body.code) throw new Error(`应援团 ${group.group_name} 签到异常: ${body.message}`)
  if (body.data.status) logger.info(`应援团 ${group.group_name} 已经签到过了`)
  else logger.info(`应援团 ${group.group_name} 签到成功，增加 ${body.data.add_num} 点亲密度`)
  share.count++
}

const getGroups = async () => {
  const { body } = await got.get('https://api.vc.bilibili.com/link_group/v1/member/my_groups', {
    json: true,
    headers: { Referer: 'https://link.bilibili.com/' },
  })
  if (body.code) throw new Error('应援团列表拉取异常: ' + body.message)
  return body.data && body.data.list || []
}

const main = async () => {
  share.count = 0
  const list = await getGroups()
  if (list.length === 0) {
    logger.info('没有加入应援团，跳过签到')
    share.lock = tomorrow(10)
    return
  }
  for (const group of list) {
    await signGroup(group)
    await sleep(1000)
  }
  // 全部成功则明天再跑，否则 1 小时后重试未签到的团
  share.lock = share.count === list.length ? tomorrow(10) : Date.now() + 60 * 60 * 1000
}

module.exports = () => {
  if (process.env.DISABLE_GROUP === 'true') return
  if (share.lock > Date.now()) return
  return main().catch(e => {
    logger.error(e.message)
    share.lock = Date.now() + 10 * 60 * 1000
  })
}