const crypto = require('crypto')

const got = require('../utils/got')
const share = require('../utils/share').auth
const sign = require('../utils/sign')
const logger = require('../utils/logger')
const config = require('../utils/config')

const getPublicKey = async () => {
  logger.info('正在获取公钥')

  let payload = {}
  let {body} = await got.post('https://passport.bilibili.com/api/oauth2/getKey', {
    body: sign(payload),
    form: true,
    json: true,
  })

  if (body.code) throw new Error('公钥获取失败')
  logger.notice('公钥获取成功')
  return body.data
}

const loginPassword = async () => {
  let data = await getPublicKey()

  logger.info('正在尝试使用用户名、密码登录')

  let username = config.get('username')
  let password = crypto.publicEncrypt(
    {
      key: data.key,
      padding: 1,
    },
    Buffer.from(`${data.hash}${config.get('password')}`) // eslint-disable-line
  ).toString('base64')

  let payload = {
    seccode: '',
    validate: '',
    subid: 1,
    permission: 'ALL',
    username,
    password,
    captcha: '',
    challenge: '',
  }

  let {body} = await got.post('https://passport.bilibili.com/api/v3/oauth2/login', {
    body: sign(payload),
    form: true,
    json: true,
  })

  if (body.code || body.data.status) throw new Error('登录失败')
  logger.notice('登录成功')

  config.set('access_token', body.data.token_info.access_token)
  config.set('refresh_token', body.data.token_info.refresh_token)
}

const refreshToken = async () => {

  if (config.get('refresh_token', '') === '') return false

  logger.info('正在刷新 Token')

  let payload = {
    access_token: config.get('access_token'),
    refresh_token: config.get('refresh_token'),
  }
  let {body} = await got.post('https://passport.bilibili.com/api/oauth2/refreshToken', {
    body: sign(payload),
    form: true,
    json: true,
  })

  if (body.code) {
    config.set('access_token', '')
    config.set('refresh_token', '')
    return false
  }
  logger.notice('Token 刷新成功')
  config.set('access_token', body.data.access_token)
  config.set('refresh_token', body.data.refresh_token)
}

const checkCookie = async () => {
  logger.info('检查 Cookie 是否过期')

  const body = await getUserInfo()

  if (body.code !== 'REPONSE_OK') {
    logger.warning('检测到 Cookie 已经过期')
    logger.info('正在刷新 Cookie')
    await got.get('https://passport.bilibili.com/api/login/sso', {query: sign({})})
    logger.notice('Cookie 刷新成功')
    await getUserInfo() // 获取UID，舰长经验检测有用到
  }
}

const getUserInfo = async () => {
  const { body } = await got.get('https://api.live.bilibili.com/User/getUserInfo', {
    query: {
      ts: Math.round(Date.now() / 1000)
    },
    json: true,
  })

  // 获取UID
  if (body.code === 'REPONSE_OK') config.set('uid', body.data.uid)

  return body
}

const checkToken = async () => {
  logger.info('检查 Token 是否过期')

  let payload = {
    access_token: config.get('access_token', ''),
  }
  let {body} = await got.get('https://passport.bilibili.com/api/v2/oauth2/info', {
    query: sign(payload),
    json: true,
  })

  if (body.code || body.data.expires_in < 14400) {
    logger.warning('检测到 Token 需要更新')

    let status = await refreshToken()
    if (!status) await loginPassword()
  }
}

const main = async () => {
  await checkToken()
  await checkCookie()
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
