require('dotenv').config()

const Conf = require('conf')
const config = new Conf({
  cwd: `${__dirname}/../`,
  configName: '.config',
  fileExtension: '',
})

module.exports = config
