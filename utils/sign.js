const qs = require('qs')
const md5 = require('md5')
const config = require('./config')

const sign = data => {

  const appkey = '4409e2ce8ffd12b8'
  const appsecret = '59b43e04ad6965f34319062b478f83dd'

  let defaults = {
    access_key: config.get('access_token', ''),
    actionKey: 'appkey',
    appkey,
    build: '8470',
    device: 'phone',
    mobi_app: 'iphone',
    platform: 'ios',
    ts: Math.round(Date.now() / 1000),
    type: 'json',
  }

  data = {
    ...defaults,
    ...data
  }

  let hash = qs.stringify(data, {sort: (a, b) => a.localeCompare(b)})
  hash = md5(hash + appsecret)

  data.sign = hash

  return data
}

module.exports = sign
