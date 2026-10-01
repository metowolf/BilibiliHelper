const got = require('../utils/got')
const share = require('../utils/share').auth
const logger = require('../utils/logger')
const config = require('../utils/config')

const QR_API = 'https://passport.bilibili.com/x/passport-login/web/qrcode'
const SCAN_TIMEOUT = 180 * 1000 // 扫码有效期 3 分钟

const fetchQrCode = async () => {
  const { body } = await got.get(QR_API + '/generate', { json: true })
  if (body.code !== 0) throw new Error('获取二维码失败: ' + body.message)
  return body.data
}

const pollScan = async qrcodeKey => {
  const { body } = await got.get(QR_API + '/poll', {
    query: { qrcode_key: qrcodeKey },
    json: true,
  })
  return body.data
}

// 轮询直到扫码成功 / 二维码过期 / 超时
const waitForScan = async qrcodeKey => {
  const deadline = Date.now() + SCAN_TIMEOUT
  let lastCode = -1

  while (Date.now() < deadline) {
    const data = await pollScan(qrcodeKey)
    if (data.code !== lastCode) {
      lastCode = data.code
      switch (data.code) {
        case 0:
          logger.notice('扫码登录成功！')
          return data
        case 86038:
          throw new Error('二维码过期，请重新运行脚本')
        case 86090:
          logger.info('已扫码，请在手机上确认登录')
          break
        case 86101:
          break // 等待扫码
        default:
          logger.info('扫码状态: ' + data.message)
      }
    }
    await new Promise(r => setTimeout(r, 2000))
  }
  throw new Error('扫码超时，请重新运行脚本')
}

// 写入 cookieJar，顺便提取 bili_jct 作为 csrf
const syncCookies = cookies => {
  for (const cookie of cookies) {
    got.defaults.options.cookieJar.setCookieSync(
      `${cookie.name}=${cookie.value}; Domain=${cookie.domain}; Path=/`,
      'https://' + cookie.domain
    )
    if (cookie.name === 'bili_jct') config.set('csrf', cookie.value)
  }
}

const saveLoginData = loginData => {
  config.set('access_token', loginData.token_info.access_token)
  config.set('refresh_token', loginData.token_info.refresh_token)
  syncCookies(loginData.cookie_info.cookies)
  if (loginData.mid) config.set('uid', loginData.mid)
  logger.notice(`登录成功，UID: ${loginData.mid || '未知'}`)
}

// cookie 是否仍有效
const checkLogin = async () => {
  try {
    const { body } = await got.get('https://api.bilibili.com/x/web-interface/nav', { json: true })
    if (body.code === 0 && body.data.isLogin) {
      config.set('uid', body.data.mid)
      got.getCsrf()
      return true
    }
  } catch (e) {}
  return false
}

// 输出 qrcode.png 供手机相册扫码（终端二维码 / 链接不受影响，失败可忽略）
const writeQrImage = async url => {
  try {
    const qrPath = require('path').join(process.cwd(), 'qrcode.png')
    await require('qrcode').toFile(qrPath, url, { width: 400, margin: 2 })
    logger.notice('二维码已保存到: ' + qrPath)
  } catch (e) {}
}

const loginByQrCode = async () => {
  logger.info('正在获取登录二维码...')
  const { url, qrcode_key } = await fetchQrCode()

  try {
    require('qrcode-terminal').generate(url, { small: true })
  } catch (e) {}
  await writeQrImage(url)
  logger.notice('请使用哔哩哔哩手机APP扫码登录（也可在 PC 浏览器打开以下链接）：')
  logger.info(url)

  saveLoginData(await waitForScan(qrcode_key))
}

const refreshToken = async () => {
  const refresh_token = config.get('refresh_token', '')
  if (!refresh_token) return false

  logger.info('正在刷新 Token')
  const { body } = await got.post('https://passport.bilibili.com/x/passport-login/oauth2/refresh_token', {
    body: { access_token: config.get('access_token'), refresh_token },
    form: true,
    json: true,
  })

  if (body.code) {
    config.set('access_token', '')
    config.set('refresh_token', '')
    return false
  }

  config.set('access_token', body.data.token_info.access_token)
  config.set('refresh_token', body.data.token_info.refresh_token)
  syncCookies(body.data.cookie_info.cookies)
  logger.notice('Token 刷新成功')
  return true
}

// access_token 失效时尝试刷新，仍失败则需要重新扫码
const checkToken = async () => {
  const accessToken = config.get('access_token', '')
  if (!accessToken) return false

  try {
    const { body } = await got.get('https://passport.bilibili.com/x/passport-login/oauth2/info', {
      query: { access_token: accessToken },
      json: true,
    })
    if (body.code === 0 && body.data.expires_in > 14400) return true
  } catch (e) {}

  if (await refreshToken()) return true
  logger.warning('Token 已过期，需要重新扫码登录')
  return false
}

const main = async () => {
  if (await checkLogin()) {
    logger.info('当前已登录，无需重新登录')
    return
  }
  if (await checkToken()) {
    logger.info('Token 有效，无需重新登录')
    return
  }
  await loginByQrCode()
}

module.exports = () => {
  if (share.lock > Date.now()) return
  return main()
    .then(() => { share.lock = Date.now() + 60 * 60 * 1000 })   // 1 小时内不再检查
    .catch(e => {
      logger.error(e.message)
      share.lock = Date.now() + 10 * 60 * 1000               // 失败 10 分钟后重试
    })
}