require('dotenv').config()

const Conf = require('conf')
const config = new Conf()
const package = require('../package.json')

const init = () => {
  if (config.get('version') !== package.version) {
    config.store = {
      version: package.version,
      debug: process.env.DEBUG || true,
      username: process.env.USERNAME || '',
      password: process.env.PASSWORD || '',
      access_token: process.env.ACCESS_TOKEN || '',
      refresh_token: process.env.REFRESH_TOKEN || '',
      room_id: process.env.ROOM_ID || '3746256',
    }
  }
  console.log(config.store)
}

module.exports = init
