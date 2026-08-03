<?php
function ems_enable_ui_enhancements(): void
{
    static $enabled = false;
    if ($enabled || PHP_SAPI === 'cli') return;
    $enabled = true;
    ob_start(static function (string $html): string {
        if (stripos($html, '</body>') === false) return $html;

        if (($_SESSION['admin_role'] ?? '') === 'Super Admin'
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="company_details.php"')) {
            $companyLink = '<a href="company_details.php" class="sidebar-link"><i class="fa fa-building-circle-check"></i> Company Details</a>';
            $html = preg_replace('/(<a href="change_password\.php" class="sidebar-link")/i', $companyLink . '$1', $html, 1) ?? $html;
        }

        if (!str_contains($html, 'language_switcher.js')) {
            $script = '<script src="' . ems_language_asset_url() . '" defer></script>';
            $html = preg_replace('/<\/body>/i', $script . '</body>', $html, 1) ?? $html;
        }
        return $html;
    });
}

function ems_language_asset_url(): string
{
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $projectBase = preg_replace('#/(admin|employee)/[^/]+$#', '', $scriptName);
    if ($projectBase === $scriptName) $projectBase = rtrim(dirname($scriptName), '/.');
    return ($projectBase === '' ? '' : $projectBase) . '/assets/language_switcher.js';
}
