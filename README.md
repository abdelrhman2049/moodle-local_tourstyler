## User Tour Styler ##

### Short Description ###
A modern Moodle local plugin designed to transform Moodle's default User Tours into sleek, interactive, and fully customizable onboarding experiences. It provides a real-time visual studio directly inside Site Administration to style cards, animations, dark mode, backdrops, and individual tour steps without writing code.

### Detailed Description ###
User Tour Styler elevates Moodle's built-in `tool_usertours` with a polished SaaS-grade User Experience (UX). Built for Moodle 5.x using native ES Modules (AMD) and Bootstrap 5, it allows administrators to live-preview every design tweak, match their platform's brand colors in one click, and customize specific icons, colors, and animations for each individual tour step.

## Key Features: ##

**Live Interactive Studio:** A side-by-side visual builder inside plugin settings with instant live preview before saving changes.

**Global Presets & Brand Sync:** Choose between Modern SaaS, Glassmorphism, and Minimal Clean presets, with one-click **Match Platform Colors** and **Reset to Default** actions.

**Per-Tour & Per-Step Customization:** Automatically fetches all Active and Inactive Moodle User Tours and their steps, allowing custom FontAwesome icons, header colors, spotlight rings, and animations per step.

**Light, Dark & Auto Mode:** Full adaptive support for Light Mode, Dark Mode, or automatic detection based on the active theme or user OS preference.

**Animated Accent Bar & Text Effects:** Configurable 3-color animated gradient top bar, JavaScript Typewriter effect for titles, and smooth Fade/Blur/Slide animations for step content.

**Backdrop & Spotlight Control:** Customize the page overlay (Blurred, Solid, Transparent, or Hidden) and highlight target elements clearly in both light and dark themes.

**One-Click Template Deployment:** Safely deploy or remove the `tourstep.mustache` override in the active theme directly from the settings page.

**Custom CSS & Reference:** Built-in Custom CSS editor accompanied by a quick reference table of all available CSS selectors and `:root` variables.

## Installing via uploaded ZIP file ##
Log in to your Moodle site as an admin and go to *Site administration > Plugins > Install plugins*.

Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.

Check the plugin validation report and finish the installation.

## Installing manually ##
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

## Post-Installation Setup ##
1. Navigate to *Site administration > Appearance > User Tour Styler*.
2. Click **Deploy Template** to install the Mustache override into your active theme.
3. Customize your global style or individual tour steps using the Live Studio and click **Save changes**.

## License ##
2026 AbdelrhmanBoghdady support@learn-ix.com

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.