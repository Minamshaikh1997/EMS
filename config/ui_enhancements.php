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

        if (($_SESSION['admin_role'] ?? '') === 'Super Admin'
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="access_control.php"')) {
            $accessLink = '<a href="access_control.php" class="sidebar-link"><i class="fa fa-sliders"></i> Access Control</a>';
            $html = preg_replace('/(<a href="change_password\.php" class="sidebar-link")/i', $accessLink . '$1', $html, 1) ?? $html;
        }

        if (basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'requisitions.php') {
            $employmentOptions = '<option selected>Permanent</option><option>Full Time</option><option>Part Time</option><option>Contract</option><option>Probation</option><option>Internship</option><option>Consultant</option>';
            $html = preg_replace('/(<select name="employment_type" class="form-select">).*?(<\/select>)/is', '$1' . $employmentOptions . '$2', $html, 1) ?? $html;
        }

        // Keep the Requisitions module visible in every admin sidebar, including
        // older pages that still contain their own copied sidebar markup.
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if (str_contains($scriptName, '/admin/')
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="requisitions.php"')) {
            $requisitionLink = '<a href="requisitions.php" class="sidebar-link"><i class="fa fa-file-circle-plus"></i> Requisitions</a>';
            $html = preg_replace('/(<a href="leave_requests\.php" class="sidebar-link[^\"]*")/i', $requisitionLink . '$1', $html, 1) ?? $html;
            if (!str_contains($html, 'href="requisitions.php"')) {
                $html = preg_replace('/(<a href="change_password\.php" class="sidebar-link")/i', $requisitionLink . '$1', $html, 1) ?? $html;
            }
        }

        // Keep the standalone MIS Adjustment page visible in every admin sidebar.
        if (str_contains($scriptName, '/admin/')
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="mis_adjustment.php"')) {
            $misActive = str_ends_with($scriptName, '/admin/mis_adjustment.php') ? ' active' : '';
            $misLink = '<a href="mis_adjustment.php" class="sidebar-link' . $misActive . '"><i class="fa fa-sliders"></i> MIS Adjustments</a>';
            $html = preg_replace('/(<a href="admin_adjustments\.php" class="sidebar-link[^\"]*"[^>]*>.*?<\/a>)/is', '$1' . $misLink, $html, 1) ?? $html;
            if (!str_contains($html, 'href="mis_adjustment.php"')) {
                $html = preg_replace('/(<a href="change_password\.php" class="sidebar-link")/i', $misLink . '$1', $html, 1) ?? $html;
            }
        }

        // Add a personal KPI/performance link to every employee sidebar.
        if (str_contains($scriptName, '/employee/')
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="my_performance.php"')) {
            $performanceLink = '<a href="my_performance.php" class="sidebar-link"><i class="fa fa-chart-line"></i> My Performance</a>';
            $html = preg_replace('/(<a href="edit_profile\.php" class="sidebar-link[^\"]*")/i', $performanceLink . '$1', $html, 1) ?? $html;
            if (!str_contains($html, 'href="my_performance.php"')) {
                $html = preg_replace('/(<a href="logout\.php" class="sidebar-link")/i', $performanceLink . '$1', $html, 1) ?? $html;
            }
        }

        // MIS Adjustment is available to every employee role.
        if (str_contains($scriptName, '/employee/')
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="mis_adjustments.php"')) {
            $misEmployeeActive = str_ends_with($scriptName, '/employee/mis_adjustments.php') ? ' active' : '';
            $misEmployeeLink = '<a href="mis_adjustments.php" class="sidebar-link' . $misEmployeeActive . '"><i class="fa fa-sliders"></i> MIS Adjustments</a>';
            $html = preg_replace('/(<a href="my_adjustments\.php" class="sidebar-link[^\"]*"[^>]*>.*?<\/a>)/is', '$1' . $misEmployeeLink, $html, 1) ?? $html;
            if (!str_contains($html, 'href="mis_adjustments.php"')) {
                $html = preg_replace('/(<a href="edit_profile\.php" class="sidebar-link[^\"]*")/i', $misEmployeeLink . '$1', $html, 1) ?? $html;
            }
            if (!str_contains($html, 'href="mis_adjustments.php"')) {
                $html = preg_replace('/(<a href="logout\.php" class="sidebar-link")/i', $misEmployeeLink . '$1', $html, 1) ?? $html;
            }
        }

        // Keep the PSM/KPI module visible in every admin sidebar.
        if (str_contains($scriptName, '/admin/')
            && str_contains($html, 'class="sidebar-link"')
            && !str_contains($html, 'href="psm_kpi.php"')) {
            $psmKpiLink = '<a href="psm_kpi.php" class="sidebar-link"><i class="fa fa-bullseye"></i> PSM/KPI</a>';
            $html = preg_replace('/(<a href="reports\.php" class="sidebar-link[^\"]*")/i', $psmKpiLink . '$1', $html, 1) ?? $html;
            if (!str_contains($html, 'href="psm_kpi.php"')) {
                $html = preg_replace('/(<a href="change_password\.php" class="sidebar-link")/i', $psmKpiLink . '$1', $html, 1) ?? $html;
            }
        }

        // Super Admin-only destructive control for employee requisitions.
        if (($_SESSION['admin_role'] ?? '') === 'Super Admin'
            && str_ends_with($scriptName, '/admin/requisitions.php')
            && str_contains($html, 'id="decisionFooter"')) {
            $deleteScript = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const footer = document.getElementById('decisionFooter');
    const requisitionId = document.getElementById('decisionId');
    const csrfInput = document.querySelector('#decision input[name="csrf_token"]');
    if (!footer || !requisitionId || !csrfInput || document.getElementById('deleteRequisitionBtn')) return;
    const button = document.createElement('button');
    button.type = 'button';
    button.id = 'deleteRequisitionBtn';
    button.className = 'btn btn-danger me-auto';
    button.innerHTML = '<i class="fa fa-trash me-1"></i> Delete Requisition';
    button.addEventListener('click', function () {
        if (!requisitionId.value || !confirm('Are you sure you want to permanently delete this requisition?')) return;
        const form = document.createElement('form');
        form.method = 'post';
        form.action = 'requisitions.php';
        [['csrf_token', csrfInput.value], ['action', 'delete'], ['id', requisitionId.value]].forEach(function (pair) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = pair[0]; input.value = pair[1]; form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    });
    footer.prepend(button);
});
</script>
HTML;
            $html = preg_replace('/<\/body>/i', $deleteScript . '</body>', $html, 1) ?? $html;
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
