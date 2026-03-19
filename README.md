# BAS3D Tracker

> **Coming Soon:** This project will soon be rebranded as **BAS3D Tracker**.

<p align="center">
    <a href="http://laravel.com"><img src="https://img.shields.io/badge/Laravel-12-f4645f.svg" /></a>
    <a href="https://github.com/coreylad/UNIT3D/blob/master/LICENSE"><img src="https://img.shields.io/badge/License-AGPL%20v3.0-yellow.svg" /></a>
</p>

## 📝 Table of Contents

1. [Introduction](#introduction)
2. [Fork Changes](#fork-changes)
3. [Installation](#installation)
4. [Updating](#updating)
5. [Documentation](#docs)
6. [Contributing](#contributing)
7. [License](#license)


## <a name="introduction"></a> 🧐 Introduction

**BAS3D Tracker** is a fork of [UNIT3D](https://github.com/HDInnovations/UNIT3D) — a modern Private Torrent Tracker software built with Laravel, Livewire and AlpineJS. It offers a feature-rich platform with excellent performance, security and scalability to create and manage a private tracker. It is MySQL Strict Mode Compliant and PHP 8.4 Ready. It uses an MVC Architecture to ensure clarity between logic and presentation.

This fork extends UNIT3D with additional features and customisations tailored for the BAS3D Tracker community.

## <a name="fork-changes"></a> 🔀 Fork Changes

The following features have been added on top of the upstream UNIT3D codebase:

### Category Filter Bar on Torrents Page
A persistent category filter bar has been added to the `/torrents` page, allowing users to quickly filter the torrent list by category without needing to open the full search filters panel.

### Site Settings: Category Filter Bar Toggle
A new option has been added to the Staff **Site Settings** admin panel to enable or disable the category filter bar globally. Admins can control whether the filter bar is visible to users from the admin interface.

## <a name="installation"></a> 🖥️ Installation

The official script is no longer available at this time. A new one will be provided soon.

### Optional: Ocelot Tracker Mode

Linux is the default deployment target for BAS3D.

Fastest production setup (one command):

`bash scripts/ocelot.sh "https://tracker.example.com/announce/{passkey}"`

This updates `.env` (`ANNOUNCE_DRIVER=ocelot`, `OCELOT_ANNOUNCE_URL=...`) and clears Laravel caches.

After switching drivers, re-download `.torrent` files so clients receive the new announce URL.

Optional local container (if needed):

`bash scripts/ocelot.sh --start-container`

To switch back to the internal tracker mode:

`bash scripts/ocelot.sh --internal`

#### Detailed Linux Deployment Instructions (Recommended)

Use this when you want a predictable production rollout with validation and rollback.

##### 1) Prerequisites

- Linux host with `bash`, `php`, and a configured BAS3D `.env`
- Application dependencies installed (`composer install` already completed)
- Queue worker and scheduler process manager in place (for example `systemd` or `supervisor`)
- If using local Ocelot container: Docker Engine + Docker Compose plugin

##### 2) Choose Your Ocelot Endpoint

You have two deployment patterns:

- **Remote/existing Ocelot service**
    - Use a URL such as:
        - `https://tracker.example.com/announce/{passkey}`
- **Local Ocelot sidecar on the same host**
    - Use:
        - `http://127.0.0.1:3400/announce/{passkey}`

##### 3) Switch BAS3D to Ocelot Driver

Run one of the following:

- Remote/existing Ocelot:
    - `bash scripts/ocelot.sh "https://tracker.example.com/announce/{passkey}"`
- Local sidecar URL:
    - `bash scripts/ocelot.sh "http://127.0.0.1:3400/announce/{passkey}"`

This writes:

- `ANNOUNCE_DRIVER=ocelot`
- `OCELOT_ANNOUNCE_URL=...`
- `TRACKER_EXTERNAL_ENABLED=false`

##### 4) (Optional) Start Local Ocelot Container

If you are running Ocelot on the same server via Docker:

- `bash scripts/ocelot.sh --start-container`

Compose file used:

- `docker-compose.yml`
- `docker-compose.ocelot.yml`

##### 5) Reload Runtime Processes

After switching tracker mode, reload long-running Laravel processes so they pick up new env/config:

- Restart queue workers (example):
    - `php artisan queue:restart`
- Restart your process manager units (examples):
    - `sudo systemctl restart php8.4-fpm`
    - `sudo systemctl restart nginx`
    - restart scheduler/worker services used in your stack

##### 6) Client Migration Step (Required)

Existing `.torrent` files already downloaded by users still contain old announce URLs.

- Re-download `.torrent` files from BAS3D after switching.
- Inform users they must refresh torrent files in their clients.

##### 7) Verify the Deployment

Use these checks:

- Confirm env values:
    - `grep -E "^(ANNOUNCE_DRIVER|OCELOT_ANNOUNCE_URL|TRACKER_EXTERNAL_ENABLED)=" .env`
- Confirm Ocelot health (adjust host/port):
    - `curl -I http://127.0.0.1:3400`
- Download one torrent and verify announce URL contains Ocelot endpoint + passkey.

##### 8) Rollback to Internal Tracker

If needed, revert immediately:

- `bash scripts/ocelot.sh --internal`

Then restart workers/services and re-download `.torrent` files again.

## <a name="updating"></a> 🖥️ Updating

To update your installation to the latest version, run the following command. This will pull the latest changes from the repository and update your instance:

`sudo php artisan git:update`

## <a name="docs"></a> 📚 Documentation

For upstream UNIT3D documentation, see: https://hdinnovations.github.io/UNIT3D

## <a name="contributing"></a> 🤝 Contributing

Please read [CONTRIBUTING.md](https://github.com/coreylad/UNIT3D/blob/master/CONTRIBUTING.md) for details on the code of conduct and the process for submitting pull requests. A massive thank you to all of the upstream <a href="https://github.com/HDInnovations/UNIT3D/graphs/contributors">UNIT3D contributors</a>.

## <a name="license"></a> 📜 License

This project is licensed under the AGPL v3.0 License. See the [LICENSE](https://github.com/coreylad/UNIT3D/blob/master/LICENSE) file for details.


