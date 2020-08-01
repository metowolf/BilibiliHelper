const chalk = require('chalk')
require('log-timestamp');

module.exports = {
  debug: message => console.log(`${chalk.bgCyan('DEBUG')} ${message}`),
  info: message => console.log(`${chalk.bgCyan('INFO')} ${message}`),
  notice: message => console.log(`${chalk.bgGreen('NOTICE')} ${message}`),
  warning: message => console.log(`${chalk.bgYellow('WARNING')} ${message}`),
  error: message => console.log(`${chalk.bgRed('ERROR')} ${message}`),
}
