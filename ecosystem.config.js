module.exports = {
  apps: [
    {
      name: "BilibiliHelper",
      script: "index.js",
      instances: 1,
      autorestart: true,
      max_memory_restart: "512M",
      out_file: "./log/pm2-out.log",
      error_file: "./log/pm2-error.log",
      log_date_format: "yyyy-MM-DD HH:mm Z"
    }
  ]
};
