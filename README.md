# BilibiliHelper — 2026 Adapted Version

A fork of [metowolf/BilibiliHelper](https://github.com/metowolf/BilibiliHelper), adapted for the 2026 Bilibili API with a new graphical login interface.

## What's Changed

### API Adaptation (for 2026 Bilibili API changes)

| Module | Change | Status |
|---|---|---|
| Login | Password login -> QR code login (password login blocked by Bilibili) | Working |
| Daily Tasks | Removed deprecated live check-in, kept main site check-in | Working |
| Live Heartbeat | Web heartbeat API deprecated -> switched to mobile heartbeat API | Working |
| Group Sign-in | POST -> GET (API method changed) | Working |
| User Info | `account.bilibili.com/api/myinfo/v2` -> `x/web-interface/nav` | Working |
| Config Storage | Fixed `conf` module unable to create directory in pkg snapshot | Working |

### New Features

- **GUI Login** (`gui_login.py`): Python tkinter interface, QR code login, one-click script launch
- **Standalone exe packaging**: No Python / Node.js installation required, runs directly on new computers
- **Auto csrf extraction**: Extracts `bili_jct` from multiple domain cookies to avoid csrf validation failures
- **Graceful degradation**: Automatically skips unavailable external services without affecting other features

### Removed (Bilibili deprecated)

- Free treasure box (`silver.js`)
- Guard intimacy (`guard.js`, dependent third-party service no longer available)

> **Note:** The "watch live stream for 5 minutes to earn silver seeds" task API has been deprecated by Bilibili. Silver seeds can currently only be obtained through live stream gifts or events.

---

## Working Features

| Feature | Description | Status |
|---|---|---|
| QR code login | Scan with Bilibili app, no password needed | Working |
| Daily check-in | Main site check-in + coin exp query | Working |
| Live room heartbeat | Every 5 minutes, keeps you online | Working |
| Group sign-in | Daily sign-in for joined groups | Working |
| Silver seeds to coin | Auto-exchange 1 coin per day (requires >=3000 silver seeds) | Working |
| Daily gift bag | Auto-claim live daily gift bag | Working |
| Capsule machine | Query capsule coin balance | Working |
| Backpack gift sending | Auto-send expiring gifts to configured live room | Working |

---

## Usage

### Option 1: Run exe directly (Recommended, no environment needed)

1. Download both exe files from the `bin/` directory:
   - `BilibiliHelper.exe` — GUI login interface
   - `bilibili-helper.exe` — Background script
2. **Place both exe files in the same directory**
3. Double-click `BilibiliHelper.exe`
4. Scan QR code to log in -> click "Start Script"

### Option 2: Run from source

**Requirements:** Node.js >= 14, Python >= 3.8

```bash
# Install Node dependencies
npm install

# Run the script (terminal QR code login)
node index.js

# Or run the GUI
pip install requests qrcode pillow
python gui_login.py
```

### Option 3: Build exe yourself

#### Using GitHub Actions (Recommended)

1. Push a version tag (e.g., `v1.0.0`) to your fork
2. GitHub Actions will automatically build both exe files
3. Download the artifacts from the Actions tab or the Release page

```bash
git tag v1.0.0
git push origin v1.0.0
```

#### Building locally

**Requirements:** Node.js >= 14, Python >= 3.8

```bash
# Install Node dependencies
npm install

# Package Node.js script
npm install -g pkg
pkg index.js --targets node18-win-x64 --output bin/bilibili-helper.exe

# Install Python dependencies
pip install requests qrcode pillow pyinstaller

# Package Python GUI
pyinstaller --onefile --windowed --name BilibiliHelper gui_login.py

# Move GUI exe to bin/
mv dist/BilibiliHelper.exe bin/BilibiliHelper.exe
```

The workflow file `.github/workflows/build.yml` handles this automatically on CI.

---

## Configuration

Copy `.env.example` to `.env`:

```env
# Live room ID (for heartbeat and gift sending)
ROOM_ID=3746256

# Debug mode
DEBUG=true

# Optional: disable specific features
# DISABLE_TASKS=true
# DISABLE_HEART=true
# DISABLE_GROUP=true
# DISABLE_CAPSULE=true
# DISABLE_GIFTSEND=true
# DISABLE_DAILYBAG=true
# DISABLE_SILVER2CION=true
```

---

## Compatibility with New Bilibili Version

This project is adapted for the **2026 Bilibili API**:

- QR code login API (`passport.bilibili.com/x/passport-login/web/qrcode`)
- Main site check-in API (`x/web-interface/signing`)
- Live room mobile heartbeat (`api.live.bilibili.com/mobile/userOnlineHeart`)
- Silver seeds to coin exchange (`api.live.bilibili.com/pay/v1/Exchange/silver2coin`)
- Group sign-in (`api.vc.bilibili.com/link_setting/v1/link_setting/sign_in`, GET method)

If you encounter any API failures, feel free to open an issue.

---

## Project Structure

```
BilibiliHelper/
├── bin/                    # Packaged exe (not in git)
│   ├── BilibiliHelper.exe  # GUI
│   └── bilibili-helper.exe # Script
├── modules/                # Feature modules
│   ├── auth.js             # QR code login
│   ├── tasks.js            # Daily tasks
│   ├── heart.js            # Live room heartbeat
│   ├── group.js            # Group sign-in
│   ├── silver2coin.js      # Silver seeds to coin
│   ├── dailybag.js         # Daily gift bag
│   ├── capsule.js          # Capsule machine
│   └── giftsend.js         # Backpack gift sending
├── utils/                  # Utility functions
├── gui_login.py            # GUI login interface
├── index.js                # Main entry
├── package.json
└── .env.example            # Config template
```

---

## Disclaimer

This project is for learning and communication purposes only. Using third-party scripts may violate Bilibili's Terms of Service. Use at your own risk. Testing with an alternate account is recommended.

## License

MIT