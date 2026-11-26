## User Tour Styler

### Short Description

A modern Moodle local plugin designed to transform Moodle's default User Tours into sleek, interactive, and fully customizable onboarding experiences. It provides a real-time visual studio directly inside Site Administration to style cards, animations, dark mode, backdrops, and individual tour steps without writing code.

### Detailed Description

User Tour Styler elevates Moodle's built-in `tool_usertours` with a polished SaaS-grade User Experience (UX). Built for Moodle 4.5+ and 5.x using native ES Modules (AMD) and Bootstrap 5, it allows administrators to live-preview every design tweak, match their platform's brand colors in one click, and customize specific icons, colors, and animations for each individual tour step.

## Key Features:

**Live Interactive Studio:** A side-by-side visual builder inside plugin settings with instant live preview before saving changes.

**Global Presets & Brand Sync:** Choose between Modern SaaS, Glassmorphism, and Minimal Clean presets, with one-click **Match Platform Colors** and **Reset to Default** actions.

**Per-Tour & Per-Step Customization:** Automatically fetches all Active and Inactive Moodle User Tours and their steps, allowing custom FontAwesome icons, header colors, spotlight rings, and animations per step.

**Light, Dark & Auto Mode:** Full adaptive support for Light Mode, Dark Mode, or automatic detection based on the active theme or user OS preference.

**Animated Accent Bar & Text Effects:** Configurable 3-color animated gradient top bar, JavaScript Typewriter effect for titles, and smooth Fade/Blur/Slide animations for step content.

**Backdrop & Spotlight Control:** Customize the page overlay (Blurred, Solid, Transparent, or Hidden) and highlight target elements clearly in both light and dark themes.

**One-Click Template Deployment:** Safely deploy or remove the `tourstep.mustache` override in the active theme directly from the settings page.

**Custom CSS & Reference:** Built-in Custom CSS editor accompanied by a quick reference table of all available CSS selectors and `:root` variables.

## Requirements & Compatibility

* **Moodle:** 4.5+ / 5.x
* **Databases:** Fully compatible with MySQL, MariaDB, and PostgreSQL via Moodle DML API.
* **Dependencies:** None (works out of the box with no Composer or external services required).

## Installing via uploaded ZIP file

Log in to your Moodle site as an admin and go to *Site administration > Plugins > Install plugins*.

Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.

Check the plugin validation report and finish the installation.

## Installing manually

The plugin can also be installed by putting the contents of this directory into:

```text
{your/moodle/dirroot}/local/tourstyler
```

Afterwards, log in to your Moodle site as an admin and go to *Site administration > Notifications* to complete the installation.

Alternatively, you can run:

```bash
php admin/cli/upgrade.php
```

to complete the installation from the command line.

## Post-Installation Setup

1. Navigate to *Site administration > Appearance > User Tour Styler*.
2. Click **Deploy template** to install the Mustache override into your active theme.
3. Customize your global style or individual tour steps using the Live Studio and click **Save changes**.

## Manual Template Deployment (Read-Only Servers)

In hardened production environments (such as Docker, Kubernetes, or read-only webroots) where the web server user does not have write permissions to `dirroot`, you can deploy the template override manually without granting write permissions:

1. Create the following file inside your active theme directory:

```text
{your/moodle/dirroot}/theme/{your_active_theme}/templates/tool_usertours/tourstep.mustache
```

2. Paste the following Mustache template into that file:

```mustache
<div data-role="flexitour-step" class="lx-tour-step">
    <div data-role="arrow" class="lx-tour-arrow"></div>
    <div class="modal-dialog lx-tour-dialog" role="document">
        <div class="modal-content lx-tour-card">
            <div class="lx-tour-accent" aria-hidden="true"></div>
            <div class="modal-header lx-tour-header">
                <div class="lx-tour-title-wrap">
                    <span class="lx-tour-icon" aria-hidden="true">
                        <i class="fa fa-star"></i>
                    </span>
                    <h5 class="modal-title lx-tour-title" data-placeholder="title"></h5>
                </div>
                <button type="button" class="lx-tour-close" data-lx-action="close"
                        aria-label="{{#str}} close, core {{/str}}">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body lx-tour-body" data-placeholder="body"></div>
            <div class="modal-footer lx-tour-footer">
                <button type="button" class="btn lx-tour-btn lx-tour-btn-end" data-role="end">
                    {{#str}} endtour, tool_usertours {{/str}}
                </button>
                <button type="button" class="btn lx-tour-btn lx-tour-btn-primary lx-tour-btn-next" data-role="next">
                    {{#str}} nextstep, tool_usertours {{/str}}
                </button>
            </div>
        </div>
    </div>
</div>
```

3. Purge Moodle caches from *Site administration > Development > Purge caches* or via CLI:

```bash
php admin/cli/purge_caches.php
```

To restore Moodle's default tour layout at any time, delete that `tourstep.mustache` file and purge caches.

## Support & Issue Tracker

Report bugs, request features, or submit feedback via the public GitHub issue tracker:
https://github.com/abdelrhman2049/moodle-local_tourstyler/issues

## License

2026 AbdelrhmanBoghdady support@learn-ix.com

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.