<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hook callbacks implementation for User Tour Styler.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tourstyler;

use core\hook\output\before_standard_head_html_generation;

/**
 * Hook callbacks helper class.
 *
 * @package    local_tourstyler
 * @copyright  2026 AbdelrhmanBoghdady support@learn-ix.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Hook callback for injecting custom CSS, JS, and variables into head HTML.
     *
     * @param before_standard_head_html_generation $hook
     * @return void
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ): void {
        global $PAGE;

        if (!get_config('local_tourstyler', 'enabled')) {
            return;
        }

        $colormode = (string)(get_config('local_tourstyler', 'colormode') ?: 'auto');
        if (!in_array($colormode, ['auto', 'light', 'dark'], true)) {
            $colormode = 'auto';
        }

        $preset = (string)(get_config('local_tourstyler', 'preset') ?: 'saas');
        if (!in_array($preset, ['saas', 'glass', 'minimal'], true)) {
            $preset = 'saas';
        }

        $primary = self::sanitize_color(
            get_config('local_tourstyler', 'primarycolor'),
            '#6366f1'
        );
        $headerbg = self::sanitize_color(
            get_config('local_tourstyler', 'headerbg'),
            '#ffffff'
        );
        $titlecolor = self::sanitize_color(
            get_config('local_tourstyler', 'titlecolor'),
            '#0f172a'
        );
        $footerbg = self::sanitize_color(
            get_config('local_tourstyler', 'footerbg'),
            '#f8fafc'
        );
        $textcolor = self::sanitize_color(
            get_config('local_tourstyler', 'textcolor'),
            '#475569'
        );

        $nextbtnbg = self::sanitize_color(
            get_config('local_tourstyler', 'nextbtnbg'),
            $primary
        );
        $nextbtntext = self::sanitize_color(
            get_config('local_tourstyler', 'nextbtntext'),
            '#ffffff'
        );
        $endbtnbg = self::sanitize_color(
            get_config('local_tourstyler', 'endbtnbg'),
            '#ffffff'
        );
        $endbtntext = self::sanitize_color(
            get_config('local_tourstyler', 'endbtntext'),
            '#475569'
        );

        $rawradius = get_config('local_tourstyler', 'borderradius');
        $radius = ($rawradius === false || $rawradius === '') ? 20 : max(0, min(48, (int)$rawradius));

        $rawbtnradius = get_config('local_tourstyler', 'btnradius');
        $btnradius = ($rawbtnradius === false || $rawbtnradius === '') ? 10 : max(0, min(30, (int)$rawbtnradius));

        $accentstyle = (string)(get_config('local_tourstyler', 'accentstyle') ?: 'gradient_animated');
        if (!in_array($accentstyle, ['gradient_animated', 'gradient_static', 'solid', 'hidden'], true)) {
            $accentstyle = 'gradient_animated';
        }

        $accentcolor1 = self::sanitize_color(
            get_config('local_tourstyler', 'accentcolor1'),
            '#6366f1'
        );
        $accentcolor2 = self::sanitize_color(
            get_config('local_tourstyler', 'accentcolor2'),
            '#8b5cf6'
        );
        $accentcolor3 = self::sanitize_color(
            get_config('local_tourstyler', 'accentcolor3'),
            '#0ea5e9'
        );
        $spotlightcolor = self::sanitize_color(
            get_config('local_tourstyler', 'spotlightcolor'),
            $primary
        );

        $rawaccentheight = get_config('local_tourstyler', 'accentheight');
        $accentheight = ($rawaccentheight === false || $rawaccentheight === '') ?
            5 : max(0, min(20, (int)$rawaccentheight));

        $backdropmode = (string)(get_config('local_tourstyler', 'backdropmode') ?: 'blur');
        if (!in_array($backdropmode, ['blur', 'solid', 'transparent', 'none'], true)) {
            $backdropmode = 'blur';
        }
        $backdropcolor = self::sanitize_color(
            get_config('local_tourstyler', 'backdropcolor'),
            '#0f172a'
        );
        $rawbdopacity = get_config('local_tourstyler', 'backdropopacity');
        $backdropopacity = ($rawbdopacity === false || $rawbdopacity === '') ?
            58 : max(0, min(95, (int)$rawbdopacity));
        $rawbdblur = get_config('local_tourstyler', 'backdropblur');
        $backdropblur = ($rawbdblur === false || $rawbdblur === '') ?
            4 : max(0, min(20, (int)$rawbdblur));

        $headericon = trim((string)(get_config('local_tourstyler', 'headericon') ?: 'fa fa-star'));
        $headericon = preg_replace('/[^a-zA-Z0-9\-\s_]/', '', $headericon) ?: 'fa fa-star';

        $titleanim = (string)(get_config('local_tourstyler', 'titleanim') ?: 'typing');
        if (!in_array($titleanim, ['typing', 'fade_up', 'blur_in', 'none'], true)) {
            $titleanim = 'typing';
        }

        $rawspeed = get_config('local_tourstyler', 'typingspeed');
        $typingspeed = ($rawspeed === false || $rawspeed === '') ?
            35 : max(15, min(90, (int)$rawspeed));

        $bodyanim = (string)(get_config('local_tourstyler', 'bodyanim') ?: 'fade_up');
        if (!in_array($bodyanim, ['fade_up', 'blur_in', 'slide_in', 'none'], true)) {
            $bodyanim = 'fade_up';
        }

        [$cardbg, $cardblur, $cardshadow, $darkcardbg] = match ($preset) {
            'glass' => [
                'rgba(255, 255, 255, 0.78)',
                'blur(20px)',
                '0 24px 48px -12px rgba(15, 23, 42, 0.22)',
                'rgba(15, 23, 42, 0.8)',
            ],
            'minimal' => [
                '#ffffff',
                'none',
                '0 10px 25px rgba(15, 23, 42, 0.12)',
                '#0f172a',
            ],
            default => [
                'rgba(255, 255, 255, 0.96)',
                'blur(16px)',
                '0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(99, 102, 241, 0.08)',
                'rgba(15, 23, 42, 0.95)',
            ],
        };

        [$accentdisplay, $accentbgcolor, $accentbgimage, $accentbgsize, $accentanim] = match ($accentstyle) {
            'hidden' => ['none', 'transparent', 'none', '100% 100%', 'none'],
            'solid' => ['block', $accentcolor1, 'none', '100% 100%', 'none'],
            'gradient_static' => [
                'block',
                'transparent',
                "linear-gradient(90deg, {$accentcolor1}, {$accentcolor2}, {$accentcolor3})",
                '100% 100%',
                'none',
            ],
            default => [
                'block',
                'transparent',
                "linear-gradient(90deg, {$accentcolor1}, {$accentcolor2}, {$accentcolor3}, {$accentcolor1})",
                '300% 100%',
                'lxTourGradientFlow 3s ease infinite',
            ],
        };

        if ($accentheight === 0) {
            $accentdisplay = 'none';
        }

        $bdrgba = self::hex_to_rgba($backdropcolor, $backdropopacity / 100);
        [$bddisplay, $bdbg, $bdfilter] = match ($backdropmode) {
            'none' => ['none', 'transparent', 'none'],
            'transparent' => ['block', 'transparent', 'none'],
            'solid' => ['block', $bdrgba, 'none'],
            default => ['block', $bdrgba, "blur({$backdropblur}px)"],
        };

        $titleanimval = match ($titleanim) {
            'fade_up' => 'lxTourFadeUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both',
            'blur_in' => 'lxTourBlurIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both',
            default => 'none',
        };

        $bodyanimval = match ($bodyanim) {
            'blur_in' => 'lxTourBlurIn 0.52s cubic-bezier(0.16, 1, 0.3, 1) 0.14s both',
            'slide_in' => 'lxTourSlideIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) 0.14s both',
            'none' => 'none',
            default => 'lxTourFadeUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) 0.14s both',
        };

        $rawcustomcss = (string)(get_config('local_tourstyler', 'customcss') ?: '');
        $customcss = str_ireplace('</style>', '', $rawcustomcss);

        $rawoverrides = (string)(get_config('local_tourstyler', 'stepoverrides') ?: '{}');
        $decodedoverrides = json_decode($rawoverrides, true);
        if (!is_array($decodedoverrides)) {
            $decodedoverrides = [];
        }

        $PAGE->requires->js_call_amd('local_tourstyler/tour', 'init', [[
            'headericon' => $headericon,
            'titleanim' => $titleanim,
            'typingspeed' => $typingspeed,
            'colormode' => $colormode,
            'spotlightcolor' => $spotlightcolor,
            'stepOverrides' => $decodedoverrides,
        ]]);

        $html = <<<HTML
<style id="local-tourstyler-vars">
:root {
    --lx-tour-primary: {$primary};
    --lx-tour-header-bg: {$headerbg};
    --lx-tour-title-color: {$titlecolor};
    --lx-tour-footer-bg: {$footerbg};
    --lx-tour-text: {$textcolor};
    --lx-tour-radius: {$radius}px;
    --lx-tour-next-bg: {$nextbtnbg};
    --lx-tour-next-text: {$nextbtntext};
    --lx-tour-end-bg: {$endbtnbg};
    --lx-tour-end-text: {$endbtntext};
    --lx-tour-btn-radius: {$btnradius}px;
    --lx-tour-card-bg: {$cardbg};
    --lx-tour-card-blur: {$cardblur};
    --lx-tour-card-shadow: {$cardshadow};
    --lx-tour-dark-card-bg: {$darkcardbg};
    --lx-spotlight-color: {$spotlightcolor};
    --lx-accent-display: {$accentdisplay};
    --lx-accent-height: {$accentheight}px;
    --lx-accent-bg-color: {$accentbgcolor};
    --lx-accent-bg-image: {$accentbgimage};
    --lx-accent-bg-size: {$accentbgsize};
    --lx-accent-anim: {$accentanim};
    --lx-backdrop-display: {$bddisplay};
    --lx-backdrop-bg: {$bdbg};
    --lx-backdrop-filter: {$bdfilter};
    --lx-title-anim: {$titleanimval};
    --lx-body-anim: {$bodyanimval};
}
{$customcss}
</style>
HTML;

        $hook->add_html($html);
    }

    /**
     * Sanitize color string.
     *
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    private static function sanitize_color(mixed $value, string $fallback): string {
        if (!is_string($value)) {
            return $fallback;
        }
        $trimmed = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $trimmed)) {
            return $trimmed;
        }
        return $fallback;
    }

    /**
     * Convert hex color to rgba string.
     *
     * @param string $hex
     * @param float $alpha
     * @return string
     */
    private static function hex_to_rgba(string $hex, float $alpha): string {
        $clean = ltrim($hex, '#');
        if (strlen($clean) === 3) {
            $clean = $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2];
        }
        if (strlen($clean) < 6) {
            return 'rgba(15, 23, 42, ' . $alpha . ')';
        }
        $r = hexdec(substr($clean, 0, 2));
        $g = hexdec(substr($clean, 2, 2));
        $b = hexdec(substr($clean, 4, 2));
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }
}