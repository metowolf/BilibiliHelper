const qs = require('qs')
const md5 = require('md5')
const config = require('./config')

// 旧版 APP 接口签名：参数排序后拼接 appsecret 求 md5
module.exports = data => {
  const params = {
    access_key: config.get('access_token', ''),
    actionKey: 'appkey',
    appkey: '4409e2ce8ffd12b8',
    build: '8470',
    device: 'phone',
    mobi_app: 'iphone',
    platform: 'ios',
    ts: Math.round(Date.now() / 1000),
    type: 'json',
    ...data,
  }
  params.sign = md5(qs.stringify(params, { sort: (a, b) => a.localeCompare(b) }) + '59b43e04ad6965f34319062b478f83dd')
  return params
}