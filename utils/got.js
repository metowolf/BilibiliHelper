const got = require('got')
const chalk = require('chalk')
const config = require('./config')
const CookieStore = require('tough-cookie-file-store')
const CookieJar = require('tough-cookie').CookieJar

const cookieJar = new CookieJar(new CookieStore('./.cookies'))

// bili_jct 即 csrf token，依次从常用域名的 cookie 中取
const getCsrf = () => {
  for (const url of ['https://www.bilibili.com/', 'https://api.bilibili.com/', 'https://live.bilibili.com/']) {
    try {
      for (const cookie of cookieJar.getCookiesSync(url)) {
        if (cookie.key === 'bili_jct') {
          config.set('csrf', cookie.value)
          return cookie.value
        }
      }
    } catch (e) {}
  }
  return ''
}

const http = got.extend({
  headers: {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
    'Accept': 'application/json, text/plain, */*',
    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
    'Referer': 'https://www.bilibili.com',
    'Origin': 'https://www.bilibili.com',
  },
  cookieJar,
  timeout: 20000,
  hooks: {
    beforeRequest: [
      options => {
        // POST 请求自动补 csrf
        if (options.method === 'POST' && options.body && typeof options.body === 'object' && !options.body.csrf) {
          options.body.csrf = getCsrf()
        }
        if (config.get('debug')) console.log(chalk.cyan(options.method), chalk.yellow(options.href))
      },
    ],
    afterResponse: [
      response => {
        if (config.get('debug') && response.body.length < 1000) console.log(chalk.gray(response.body))
        return response
      },
    ],
  },
})

http.getCsrf = getCsrf

module.exports = http