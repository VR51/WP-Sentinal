# WP Sentinal

A WordPress MU (Must Use) plugin that monitors plugin activations/deactivations during WordPress updates and sends email alerts.

## Description

WP Sentinal is designed to solve a common WordPress issue: plugins becoming deactivated during WordPress plugin updates. When this happens, site functionality can break without the administrator's knowledge.

This plugin works by:
1. Monitoring which plugins are active before updates occur
2. Checking which plugins remain active after updates complete
3. Sending an email alert with detailed information about any plugins that were deactivated

## Features

- Works with both single site and multisite WordPress installations
- Sends email alerts with detailed information about deactivated plugins
- Configurable email recipient
- Option to enable/disable notifications
- Network admin settings page for easy configuration
- Supports detection of network-activated and site-specific plugin deactivations

## Installation

1. Upload the `wp-sentinal` directory to the `/wp-content/mu-plugins/` directory
2. Unzip the `wp-sentinal` directory
3. Move the content of the `wp-sentinal` directory into the `/wp-content/mu-plugins/` directory then delete the empty wp-sentinal directory.
4. If the `mu-plugins` directory doesn't exist, create it
5. Access the plugin settings via Network Admin > Settings > WP Sentinal (on multisite) or Settings > WP Sentinal (on single site)
6. Configure your notification email address

## Email Alert Format

When plugins are deactivated during an update, you'll receive an email with:

- Subject: "WP Sentinal Report | yourdomain.com | FAIL"
- List of deactivated plugins with their names and file paths
- For multisite installations, information about which sites were affected

If all plugins remain active after an update, you'll receive:
- Subject: "WP Sentinal Report | yourdomain.com | All OK"
- Confirmation that all plugins remain active

## Requirements

- WordPress 4.6 or higher
- PHP 5.6 or higher

## Author

- Lee Hodson (VR51)

## Contributors

- Cascade

## License

This plugin is licensed under the GPL v2 or later.

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
