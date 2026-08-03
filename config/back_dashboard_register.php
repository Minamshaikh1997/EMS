<?php
if (!defined('EMS_BACK_DASHBOARD_REGISTERED')) {
    define('EMS_BACK_DASHBOARD_REGISTERED', true);
    register_shutdown_function(static function (): void {
        include __DIR__ . '/back_dashboard.php';
    });
}
