const got = require('../utils/got')
const share = require('../utils/share').group
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const sleep = require('../utils/sleep')
const tomorrow = require('../utils/tomorrow')

const main = async () => {
  share.count = 0
  let list = await getList()
  for (let item of list) {
    await signGroup(item)
    await sleep(1000)
  }
  if (share.count === list.length) share.lock = tomorrow(10)
  else share.lock = share.lock = Date.now() + 60 * 60 * 1000
}

const signGroup = async data => {
  let payload = {
    group_id: data.group_id,
    owner_id: data.owner_uid,
  }
  let {body} = await got.post('https://api.vc.bilibili.com/link_setting/v1/link_setting/sign_in', {
    body: sign(payload),
    form: true,
    json: true,
  })
  if (body.code) throw new Error(`应援团 ${data.group_name} 签到异常`)
  if (body.data.status) logger.info(`应援团 ${data.group_name} 已经签到过了`)
  else logger.info(`应援团 ${data.group_name} 签到成功，增加 ${body.data.add_num} 点亲密度`)
  share.count += 1
}

const getList = async () => {
  let {body} = await got.post('https://api.vc.bilibili.com/link_group/v1/member/my_groups', {
    body: sign({}),
    form: true,
    json: true,
  })
  if (body.code) throw new Error('应援团列表拉取异常')
  return body.data.list
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 10 * 60 * 1000
    })
}
