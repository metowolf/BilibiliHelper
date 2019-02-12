const got = require('got')
const chalk = require('chalk')
const config = require('./config')
const CookieStore = require('tough-cookie-file-store')
const CookieJar = require('tough-cookie').CookieJar

const cookieJar = new CookieJar(new CookieStore('./.cookies'))

const _got = got.extend({
  headers: {
    'User-Agent': 'bili-universal/8330 CFNetwork/976 Darwin/18.2.0',
    'Accept': '*/*',
    'Accept-Language': 'zh-cn',
    'Connection': 'keep-alive',
    'Content-Type': 'application/x-www-form-urlencoded',
    'Referer': `https://live.bilibili.com/${config.get('room_id')}`,
  },
  cookieJar,
  timeout: 20000,
  hooks: {
    beforeRequest: [
      options => {
        if (config.get('debug')) console.log(`${chalk.cyan(options.method)} ${chalk.yellow(options.href)}`)
      }
    ],
    afterResponse: [
      response => {
        if (config.get('debug') && response.body.length < 1000) console.log(chalk.gray(response.body))
        return response
      }
    ]
  },
})

module.exports = _got
