const module_auth = require('./modules/auth')
const module_tasks = require('./modules/tasks')
const module_heart = require('./modules/heart')
const module_silver = require('./modules/silver')
const module_group = require('./modules/group')
const module_giftsend = require('./modules/giftsend')
const module_dailybag = require('./modules/dailybag')

const init = require('./utils/config')
const sleep = require('./utils/sleep')

const app = async () => {
  init()
  while (true) {
    await module_auth()
    await module_tasks()
    await module_heart()
    await module_silver()
    await module_group()
    await module_giftsend()
    await module_dailybag()
    await sleep(1000)
  }
}

app()
