// 各模块的运行时状态：lock 为下次允许执行的时间戳
module.exports = {
  auth: { lock: 0 },
  tasks: { lock: 0 },
  heart: { lock: 0 },
  dailybag: { lock_web: 0, lock_mobile: 0 },
  group: { lock: 0, count: 0 },
  giftsend: { lock: 0, uid: 0, ruid: 0, roomid: 0 },
  capsule: { lock: 0 },
  silver2coin: { lock: 0 },
}