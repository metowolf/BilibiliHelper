# -*- coding: utf-8 -*-
"""BilibiliHelper 图形化登录：tkinter 扫码登录 + 一键启动挂机脚本"""

import json
import os
import sys
import time
import shutil
import subprocess
import threading
from datetime import datetime, timezone
from io import BytesIO

import tkinter as tk
from tkinter import messagebox
import requests
import qrcode
from PIL import Image, ImageTk, ImageDraw

# Windows 高 DPI 适配，避免界面模糊
if sys.platform == 'win32':
    try:
        import ctypes
        ctypes.windll.shcore.SetProcessDpiAwareness(1)
    except Exception:
        pass

# PyInstaller 打包后文件在 exe 同目录，否则在脚本目录
BASE_DIR = os.path.dirname(sys.executable) if getattr(sys, 'frozen', False) \
    else os.path.dirname(os.path.abspath(__file__))

COOKIES_FILE = os.path.join(BASE_DIR, '.cookies')
CONFIG_FILE = os.path.join(BASE_DIR, '.config')
INDEX_JS = os.path.join(BASE_DIR, 'index.js')
PKG_EXE = os.path.join(BASE_DIR, 'bilibili-helper.exe')

QR_GENERATE_URL = 'https://passport.bilibili.com/x/passport-login/web/qrcode/generate'
QR_POLL_URL = 'https://passport.bilibili.com/x/passport-login/web/qrcode/poll'

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
    'Accept': 'application/json, text/plain, */*',
    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
    'Referer': 'https://www.bilibili.com',
    'Origin': 'https://www.bilibili.com',
}

BG = '#f5f5f5'
FONT = 'Microsoft YaHei'
PRIMARY = '#00a1d6'
DANGER = '#ff5252'
SUCCESS = '#00c853'


def generate_qr_image(url, size=300):
    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,
        border=2,
    )
    qr.add_data(url)
    qr.make(fit=True)
    return qr.make_image(fill_color='black', back_color='white').convert('RGB').resize(
        (size, size), Image.LANCZOS)


def get_qrcode():
    data = requests.get(QR_GENERATE_URL, headers=HEADERS, timeout=15).json()
    if data['code'] != 0:
        raise Exception('获取二维码失败: ' + data.get('message', ''))
    return data['data']


def poll_qrcode(qrcode_key):
    r = requests.get(QR_POLL_URL, params={'qrcode_key': qrcode_key}, headers=HEADERS, timeout=15)
    return r.json()['data']


def save_cookies(cookies):
    # 写成 tough-cookie-file-store 的 JSON 格式，供 Node 端直接读取
    now = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%S.%f')[:-3] + 'Z'
    store = {}
    for c in cookies:
        domain = c['domain'].lstrip('.')
        path = c.get('path', '/')
        expires = None
        if c.get('expires'):
            try:
                expires = datetime.fromtimestamp(c['expires'], tz=timezone.utc).strftime(
                    '%Y-%m-%dT%H:%M:%S.000Z')
            except Exception:
                expires = None
        store.setdefault(domain, {}).setdefault(path, {})[c['name']] = {
            'key': c['name'],
            'value': c['value'],
            'expires': expires,
            'domain': c['domain'],
            'path': path,
            'secure': c.get('secure', True),
            'httpOnly': c.get('httpOnly', False),
            'extensions': ['SameSite=None'],
            'hostOnly': False,
            'creation': now,
            'lastAccessed': now,
        }
    with open(COOKIES_FILE, 'w', encoding='utf-8') as f:
        json.dump(store, f, ensure_ascii=False)


def save_config(login_data):
    config = {}
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
                config = json.load(f)
        except Exception:
            pass

    config['uid'] = login_data['mid']
    config['access_token'] = login_data['token_info']['access_token']
    config['refresh_token'] = login_data['token_info']['refresh_token']
    for c in login_data['cookie_info']['cookies']:
        if c['name'] == 'bili_jct':
            config['csrf'] = c['value']
            break

    with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
        json.dump(config, f, ensure_ascii=False, indent=2)


def check_existing_login():
    """用本地 cookie 请求 nav 接口，已登录返回用户信息，否则 None"""
    if not os.path.exists(COOKIES_FILE):
        return None
    try:
        with open(COOKIES_FILE, 'r', encoding='utf-8') as f:
            store = json.load(f)
        parts = [f"{c['key']}={c['value']}"
                 for paths in store.values()
                 for cookies in paths.values()
                 for c in cookies.values()]
        headers = dict(HEADERS, Cookie='; '.join(parts))
        data = requests.get('https://api.bilibili.com/x/web-interface/nav', headers=headers, timeout=15).json()
        if data['code'] == 0 and data['data'].get('isLogin'):
            return data['data']
    except Exception:
        pass
    return None


class LoginWindow:
    def __init__(self, root):
        self.root = root
        self.root.title('BilibiliHelper - 扫码登录')
        self.root.geometry('420x560')
        self.root.minsize(420, 560)
        self.root.configure(bg=BG)

        self.qrcode_key = None
        self.polling = False
        self.node_process = None
        self.qr_img_ref = None   # 保留图片引用防止被 GC
        self.avatar_ref = None

        self.show_loading()
        threading.Thread(target=self._check_login_thread, daemon=True).start()

    def clear(self):
        for widget in self.root.winfo_children():
            widget.destroy()

    def button(self, parent, text, command, bg=PRIMARY, **kw):
        return tk.Button(parent, text=text, font=(FONT, 10), command=command,
                         bg=bg, fg='white', activebackground=bg, activeforeground='white',
                         relief='flat', padx=20, pady=5, cursor='hand2', **kw)

    def show_loading(self):
        self.clear()
        frame = tk.Frame(self.root, bg=BG)
        frame.pack(expand=True, fill='both')
        tk.Label(frame, text='正在加载...', font=(FONT, 14), bg=BG).pack(pady=20)
        self.loading_label = tk.Label(frame, text='', font=(FONT, 10), bg=BG, fg='#666')
        self.loading_label.pack(pady=10)

    def _check_login_thread(self):
        self.root.after(0, lambda: self._on_login_checked(check_existing_login()))

    def _on_login_checked(self, user_info):
        self.show_logged_in(user_info) if user_info else self.show_login()

    def show_login(self):
        self.clear()
        self.root.geometry('420x560')

        tk.Label(self.root, text='BilibiliHelper', font=(FONT, 18, 'bold'),
                 bg=BG, fg=PRIMARY).pack(pady=(20, 5))
        tk.Label(self.root, text='扫码登录', font=(FONT, 12), bg=BG, fg='#666').pack(pady=(0, 10))

        self.status_var = tk.StringVar(value='正在获取二维码...')
        tk.Label(self.root, textvariable=self.status_var, font=(FONT, 11), bg=BG).pack(pady=5)

        # 固定大小容器，防止二维码加载后布局跳动
        self.qr_frame = tk.Frame(self.root, width=320, height=320, bg='white', relief='solid', bd=1)
        self.qr_frame.pack(pady=10)
        self.qr_frame.pack_propagate(False)
        self.qr_label = tk.Label(self.qr_frame, bg='white')
        self.qr_label.pack(expand=True)

        tk.Label(self.root, text='请使用哔哩哔哩手机APP扫码登录', font=(FONT, 10),
                 bg=BG, fg='#999').pack(pady=5)
        self.button(self.root, '刷新二维码', self.refresh_qrcode).pack(pady=10)

        threading.Thread(target=self._start_login_thread, daemon=True).start()

    def show_logged_in(self, user_info):
        self.clear()
        self.root.geometry('420x480')

        tk.Label(self.root, text='✓ 已登录', font=(FONT, 18, 'bold'),
                 bg=BG, fg=SUCCESS).pack(pady=(20, 15))

        if user_info.get('face'):
            self._show_avatar(user_info['face'])

        info = tk.Frame(self.root, bg=BG)
        info.pack(pady=10, padx=40, fill='x')
        tk.Label(info, text=f"UID: {user_info['mid']}", font=(FONT, 12), bg=BG).pack(anchor='w', pady=3)
        tk.Label(info, text=f"用户名: {user_info.get('uname', '未知')}",
                 font=(FONT, 12), bg=BG).pack(anchor='w', pady=3)
        tk.Label(info, text=f"硬币: {user_info.get('money', 0)}", font=(FONT, 11),
                 bg=BG, fg='#ff9800').pack(anchor='w', pady=3)

        btns = tk.Frame(self.root, bg=BG)
        btns.pack(pady=20)
        start = self.button(btns, '启动挂机脚本', self.start_node_script, padx=25, pady=8)
        start.config(font=(FONT, 11, 'bold'))
        start.pack(side='left', padx=8)
        self.button(btns, '退出登录', self.logout, bg=DANGER,
                    activebackground='#ff6b6b', padx=25, pady=8).pack(side='left', padx=8)

    def _show_avatar(self, url):
        try:
            img = Image.open(BytesIO(requests.get(url, headers=HEADERS, timeout=10).content)).convert('RGB')
            img = img.resize((90, 90), Image.LANCZOS)
            mask = Image.new('L', (90, 90), 0)
            ImageDraw.Draw(mask).ellipse((0, 0, 90, 90), fill=255)
            output = Image.new('RGBA', (90, 90), (0, 0, 0, 0))
            output.paste(img, (0, 0), mask)
            self.avatar_ref = ImageTk.PhotoImage(output)
            tk.Label(self.root, image=self.avatar_ref, bg=BG).pack(pady=10)
        except Exception:
            pass

    def _start_login_thread(self):
        try:
            data = get_qrcode()
            self.qrcode_key = data['qrcode_key']
            self.qr_img_ref = ImageTk.PhotoImage(generate_qr_image(data['url']))
            self.root.after(0, self._update_qr_image)
        except Exception as e:
            self.root.after(0, lambda: self.status_var.set(f'获取二维码失败: {e}'))

    def _update_qr_image(self):
        if self.qr_img_ref:
            self.qr_label.config(image=self.qr_img_ref)
            self.status_var.set('请扫码登录')
            self.polling = True
            threading.Thread(target=self._poll_thread, daemon=True).start()

    def _poll_thread(self):
        while self.polling:
            try:
                data = poll_qrcode(self.qrcode_key)
                code = data['code']
                if code == 0:
                    self.polling = False
                    self.root.after(0, lambda: self.status_var.set('登录成功！'))
                    save_cookies(data['cookie_info']['cookies'])
                    save_config(data)
                    # 网络校验放后台线程，避免卡住界面
                    threading.Thread(target=self._check_login_thread, daemon=True).start()
                    return
                if code == 86038:
                    self.polling = False
                    self.root.after(0, lambda: self.status_var.set('二维码已过期，请刷新'))
                    return
                msg = {86090: '已扫码，请在手机上确认', 86101: '等待扫码...'}.get(
                    code, data.get('message', '未知状态'))
                self.root.after(0, lambda m=msg: self.status_var.set(m))
            except Exception as e:
                self.root.after(0, lambda: self.status_var.set(f'轮询错误: {e}'))
            time.sleep(2)

    def refresh_qrcode(self):
        self.polling = False
        self.status_var.set('正在获取新二维码...')
        threading.Thread(target=self._start_login_thread, daemon=True).start()

    def logout(self):
        if not messagebox.askyesno('确认', '确定要退出登录吗？'):
            return
        if os.path.exists(COOKIES_FILE):
            os.remove(COOKIES_FILE)
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
                    config = json.load(f)
                config.update(uid=None, csrf='', access_token='', refresh_token='')
                with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
                    json.dump(config, f, ensure_ascii=False, indent=2)
            except Exception:
                pass
        self.show_login()

    def find_node(self):
        """优先用同目录打包好的 exe，其次查找系统安装的 Node.js"""
        if os.path.exists(PKG_EXE):
            return PKG_EXE
        for candidate in (
            r'C:\Program Files\nodejs\node.exe',
            r'C:\Program Files (x86)\nodejs\node.exe',
            os.path.expanduser(r'~\AppData\Roaming\npm\node.exe'),
        ):
            if os.path.exists(candidate):
                return candidate
        if shutil.which('node'):
            return 'node'
        return None

    def start_node_script(self):
        if self.node_process and self.node_process.poll() is None:
            messagebox.showinfo('提示', '脚本已经在运行中')
            return
        node_path = self.find_node()
        if not node_path:
            messagebox.showerror('错误', '找不到 Node.js，请安装 Node.js 或确保 bilibili-helper.exe 在同一目录')
            return
        try:
            cmd = [node_path] if node_path == PKG_EXE else [node_path, INDEX_JS]
            self.node_process = subprocess.Popen(
                cmd, cwd=BASE_DIR,
                creationflags=subprocess.CREATE_NEW_CONSOLE if sys.platform == 'win32' else 0)
            messagebox.showinfo('成功', '挂机脚本已启动！\n（会在新窗口中运行）')
        except Exception as e:
            messagebox.showerror('错误', f'启动脚本失败: {e}')


def main():
    root = tk.Tk()
    root.tk.call('tk', 'scaling', 1.0)
    LoginWindow(root)
    root.mainloop()


if __name__ == '__main__':
    main()