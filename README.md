# WP Sentinal

[![WordPress Compatible](https://img.shields.io/badge/WordPress-4.6%2B-blue.svg)](https://wordpress.org/)
[![PHP Compatible](https://img.shields.io/badge/PHP-5.6%2B-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A WordPress MU (Must Use) plugin that monitors plugin activations/deactivations during WordPress updates and sends email alerts.

## 📋 Description

WP Sentinal is designed to solve a common WordPress issue: plugins becoming deactivated during WordPress plugin updates. When this happens, site functionality can break without the administrator's knowledge.

### How It Works

This plugin works by:
1. 🔍 Monitoring which plugins are active before updates occur
2. ✅ Checking which plugins remain active after updates complete
3. 📧 Sending an email alert with detailed information about any plugins that were deactivated

## ✨ Features

### Core Functionality
- ✅ Works with both single site and multisite WordPress installations
- 📧 Sends email alerts with detailed information about deactivated plugins
- 🔄 Supports detection of network-activated and site-specific plugin deactivations
- 🎛️ Network admin settings page for easy configuration
- 🔘 Option to enable/disable notifications

### Email Customization
- 👤 Configurable email sender name and email address
- 📝 Customizable email subject lines with variable support
- 👥 Support for multiple recipients
- 🔤 HTML or plain text email formats

### Detailed Reporting
- 📊 Plugin comparison table (with upgraded plugins highlighted)
- 🌐 Multisite-specific display options:
  - Combined table with site information for each plugin
  - Separate tables for each site in the network
- 📋 List of upgraded plugins
- 🕒 Upgrade timestamp

## 🔧 Installation

### Manual Installation

1. Upload the `wp-sentinal` directory to the `/wp-content/mu-plugins/` directory
2. Unzip the `wp-sentinal` directory
3. Move the content of the `wp-sentinal` directory into the `/wp-content/mu-plugins/` directory then delete the empty wp-sentinal directory
4. If the `mu-plugins` directory doesn't exist, create it

### Configuration

1. Access the plugin settings via:
   - **Multisite**: Network Admin > Settings > WP Sentinal
   - **Single site**: Settings > WP Sentinal

2. Configure your notification settings:

   | Setting | Description |
   |---------|-------------|
   | Primary recipient | Main email address to receive notifications |
   | Additional recipients | One email per line |
   | Sender name | Name that appears in the From field |
   | Sender email | Email address that appears in the From field |
   | Email subjects | Customizable with variables |
   | Comparison table | Enable/disable and format options |
   | Multisite display | Combined or separate tables |
   | Upgraded plugins list | Show/hide list of upgraded plugins |
   | Upgrade timestamp | Show/hide when the upgrade occurred |

## 📧 Email Alert Format

WP Sentinal provides highly customizable email alerts with both HTML and plain text formats.

### 🚫 When Plugins Are Deactivated (Failure Report)

```
Subject: WP Sentinal Report | yourdomain.com | FAIL
```

**Contents:**
- ❌ List of deactivated plugins with their names and file paths
- 🌐 For multisite: Information about which sites were affected
- 📊 Optional plugin comparison table (with upgraded plugins highlighted)
  - For multisite: Choose between a combined table with site information or separate tables for each site
- 📋 Optional list of upgraded plugins
- 🕒 Optional upgrade timestamp

### ✅ When All Plugins Remain Active (Success Report)

```
Subject: WP Sentinal Report | yourdomain.com | All OK
```

**Contents:**
- ✅ Confirmation that all plugins remain active
- 📊 Optional plugin comparison table (with upgraded plugins highlighted)
  - For multisite: Choose between a combined table with site information or separate tables for each site
- 📋 Optional list of upgraded plugins
- 🕒 Optional upgrade timestamp

### 🔄 Email Customization Variables

The following variables can be used in email subject lines:

| Variable | Description |
|----------|-------------|
| `%host%` | The domain name of your site |
| `%site_name%` | The name of your WordPress site |
| `%date%` | The current date |
| `%time%` | The current time |

## 🔧 Requirements

- WordPress 4.6 or higher
- PHP 5.6 or higher

## 👨‍💻 Author & Contributors

**Author:**
- [Lee Hodson (VR51)](https://vr51.com)

**Contributors:**
- Cascade

## 💰 Support

If you find this plugin useful, consider supporting its development:

- [Donate via PayPal](https://paypal.me/vr51)

## 📜 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```
