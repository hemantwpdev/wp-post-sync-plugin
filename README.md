# WP Post Sync & Translate

Automatically sync blog posts from Host site to Target sites in real-time using REST API.

**Version:** 1.0.0 | **Requires:** WordPress 5.0+, PHP 7.4+

---

## Installation & Setup

### Host Site Setup

1. Upload plugin to `/wp-content/plugins/wp-post-sync-translate/`
2. Activate plugin: **Plugins → Activate**
3. Go to **Settings → Post Sync & Translate**
4. Select **"Host"** mode
5. Add Target URL and click **Add Target** (auto-generates key)
6. **Copy the key** to paste on Target site
7. Save Settings

### Target Site Setup

1. Upload same plugin to `/wp-content/plugins/wp-post-sync-translate/`
2. Activate plugin: **Plugins → Activate**
3. Go to **Settings → Post Sync & Translate**
4. Select **"Target"** mode
5. Paste the **key** from Host
6. Select **Language** (English/French/Spanish/Hindi)
7. **(Optional)** Add ChatGPT API key for translation
8. Save Settings

---

## Settings Explanation

### Host Mode
| Field | What to do |
|-------|-----------|
| Mode | Select "Host" |
| Add Target | Enter target site URL and click Add |
| Copy Key | Click to copy key, paste on Target site |

### Target Mode
| Field | What to do |
|-------|-----------|
| Mode | Select "Target" |
| Shared Key | Paste key from Host site |
| Language | Choose: English / Français / Español / हिन्दी |
| ChatGPT Key | (Optional) For translation feature |

---

## Functionality Status

### ✅ COMPLETED & WORKING
- ✅ Host mode configuration (stores target URLs & keys)
- ✅ Target mode configuration (stores shared key & language)
- ✅ Add target sites (with auto-generated 48-char keys)
- ✅ Remove target sites
- ✅ Real-time post push (triggers on publish/update)
- ✅ Audit logging (shows in settings page)
- ✅ Admin settings interface
- ✅ REST API endpoints (`/sync`)
- ✅ AJAX handlers for settings save/add/remove
- ✅ Security validation & sanitization

### ⏳ Pending
- **Translation Feature and Post Generation Feature is remaining** - 

Due to time constraints, the translation and target-side post creation features are still pending. While implementing the post receive flow on the Target site, issues were encountered when processing incoming post data, and these could not be fully resolved within the allotted time.

### ⚠️ KNOWN ISSUES & LIMITATIONS

| Issue | Status | Details |
|-------|--------|---------|
| Post Generation and Sync Procedure | Pending | Getting Error During Sync Procedure, Bug Fixing is remaining |

---

## Screenshots

Host Settings Page: https://prnt.sc/LxH6tlrfhzFK

Target Settings Page: https://prnt.sc/4pBOiHjYEPxh

Logs Section: https://prnt.sc/2KW2DHDQpYdo


### Host Settings Page
```
Location: Settings → Post Sync & Translate

[Host Mode Selected]
├─ Add Target Site URL
├─ Generate & Copy Key
└─ Table: URL | Key | Copy Button | Remove Button
```

### Target Settings Page
```
Location: Settings → Post Sync & Translate

[Target Mode Selected]
├─ Paste Shared Key
├─ Select Language (Dropdown)
├─ ChatGPT API Key (Optional)
├─ Save Settings Button
└─ Recent Sync Logs (Last 50 operations)
```

### Sync Log Example
```
Shows: Timestamp | Action | Status | IDs | Duration | Message
Example: 2026-02-10 09:15:23 | sync | success | Host: 1845 | Target: 2301 | 1250ms | Post synced successfully
```

---

## Demo Video

**https://www.loom.com/share/648c0b1ba3c2462fb0985b1cff6c788c**

---