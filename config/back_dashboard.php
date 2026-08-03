<?php
if (!isset($backDashboardHref)) {
    $backDashboardHref = 'dashboard.php';
}
?>
<style>
.ems-back-dashboard {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 1040;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    background: #2563eb;
    color: #fff !important;
    text-decoration: none !important;
    font: 600 13px/1.2 Inter, Arial, sans-serif;
    box-shadow: 0 8px 24px rgba(37, 99, 235, .3);
}
.ems-back-dashboard:hover { background: #1d4ed8; transform: translateY(-1px); }
@media (max-width: 600px) {
    .ems-back-dashboard { right: 14px; bottom: 14px; padding: 9px 13px; }
}
</style>
<a class="ems-back-dashboard" href="<?php echo htmlspecialchars($backDashboardHref, ENT_QUOTES); ?>" aria-label="Back to Dashboard">
    <span aria-hidden="true">&#8592;</span> Back to Dashboard
</a>
