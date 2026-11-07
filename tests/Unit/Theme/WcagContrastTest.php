<?php

namespace Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WcagContrastTest extends TestCase
{
    /**
     * Calculate WCAG 2.1 relative luminance for a hex color.
     */
    public function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = ($r <= 0.04045) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.04045) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.04045) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculate WCAG 2.1 contrast ratio between two hex colors.
     */
    public function contrastRatio(string $color1, string $color2): float
    {
        $l1 = $this->luminance($color1);
        $l2 = $this->luminance($color2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Test active color pairs in Samudra, Marun, and Senja themes.
     */
    #[DataProvider('themeColorPairsProvider')]
    public function test_theme_color_contrast_meets_wcag_standard(
        string $theme,
        string $context,
        string $foreground,
        string $background,
        float $minRequiredRatio
    ): void {
        $ratio = $this->contrastRatio($foreground, $background);

        $this->assertGreaterThanOrEqual(
            $minRequiredRatio,
            $ratio,
            sprintf(
                "Theme '%s' - Context '%s': Contrast between %s and %s is %.2f:1, which is below the required %.1f:1",
                $theme,
                $context,
                $foreground,
                $background,
                $ratio,
                $minRequiredRatio
            )
        );
    }

    public static function themeColorPairsProvider(): array
    {
        return [
            // Samudra Theme
            ['samudra', 'Main text on surface (#fdf0d5)', '#003049', '#fdf0d5', 4.5],
            ['samudra', 'Main text on white card (#ffffff)', '#003049', '#ffffff', 4.5],
            ['samudra', 'Muted text on surface (#fdf0d5)', '#3d5a73', '#fdf0d5', 4.5],
            ['samudra', 'Danger text on surface (#fdf0d5)', '#c1121f', '#fdf0d5', 4.5],
            ['samudra', 'Sidebar text on sidebar bg (#003049)', '#fdf0d5', '#003049', 4.5],
            ['samudra', 'White text on primary button (#003049)', '#ffffff', '#003049', 4.5],
            ['samudra', 'Contrast text on accent badge (#669bbc)', '#003049', '#669bbc', 4.5],
            ['samudra', 'Active link text on active sidebar bg (#669bbc)', '#003049', '#669bbc', 4.5],

            // Marun Theme
            ['marun', 'Main text on surface (#fdf0d5)', '#3d0000', '#fdf0d5', 4.5],
            ['marun', 'Danger text on surface (#fdf0d5)', '#c1121f', '#fdf0d5', 4.5],
            ['marun', 'Sidebar text on sidebar bg (#780000)', '#fdf0d5', '#780000', 4.5],
            ['marun', 'White text on primary button (#780000)', '#ffffff', '#780000', 4.5],
            ['marun', 'White text on accent/active (#c1121f)', '#ffffff', '#c1121f', 4.5],

            // Senja Theme
            ['senja', 'Main text on surface (#fdf0d5)', '#003049', '#fdf0d5', 4.5],
            ['senja', 'Danger text on surface (#fdf0d5)', '#c1121f', '#fdf0d5', 4.5],
            ['senja', 'Sidebar text on sidebar bg (#003049)', '#fdf0d5', '#003049', 4.5],
            ['senja', 'White text on primary button (#003049)', '#ffffff', '#003049', 4.5],
            ['senja', 'White text on accent/active (#c1121f)', '#ffffff', '#c1121f', 4.5],
        ];
    }

    /**
     * Prove that accent #669bbc FAILS as normal text on #fdf0d5 surface,
     * justifying why primary #003049 is used instead for text & links on surface.
     */
    public function test_accent_669bbc_on_surface_fdf0d5_fails_normal_text(): void
    {
        $ratio = $this->contrastRatio('#669bbc', '#fdf0d5');

        // Ratio is ~2.65:1, well below WCAG AA normal text threshold 4.5:1
        $this->assertLessThan(
            4.5,
            $ratio,
            "Accent #669bbc on #fdf0d5 is expected to fail normal text contrast (ratio is {$ratio}:1)"
        );
        $this->assertGreaterThan(2.5, $ratio);
    }
}
