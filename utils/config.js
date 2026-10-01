require('dotenv').config()

const Conf = require('conf')
const path = require('path')

// 使用可写目录（pkg 打包后 __dirname 是只读快照）
const configDir = process.cwd()
const config = new Conf({
  cwd: configDir,
  configName: '.config',
  fileExtension: '',
})

module.exports = config