const moment = require('moment')

const tomorrow = (m = 0, s = 0) => {
  let unix = moment().add(1, 'd').startOf('day').add(m, 'm').add(s, 's').format('x')
  return parseInt(unix, 10)
}

module.exports = tomorrow
