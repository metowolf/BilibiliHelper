// 返回明天 00:00 再加 minutes 分 / seconds 秒的时间戳（如 tomorrow(10) = 明天 00:10）
module.exports = (minutes = 0, seconds = 0) => {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  d.setHours(0, 0, 0, 0)
  d.setMinutes(minutes, seconds, 0)
  return d.getTime()
}