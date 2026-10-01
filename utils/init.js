const config = require('./config')
const pkg = require('../package.json')

// 版本号变化时用默认值重置配置
module.exports = () => {
  if (config.get('version') !== pkg.version) {
    config.store = {
      version: pkg.version,
      debug: process.env.DEBUG === 'true',
      access_token: process.env.ACCESS_TOKEN || '',
      refresh_token: process.env.REFRESH_TOKEN || '',
      room_id: process.env.ROOM_ID || '3746256',
    }
  }
}