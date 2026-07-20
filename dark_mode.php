<style>
.dark-mode{
    background:#121212 !important;
    color:#fff !important;
}

.dark-mode .card{
    background:#1e1e1e !important;
    color:#fff !important;
}

.dark-mode .table{
    color:#fff !important;
}

.dark-mode .table th,
.dark-mode .table td{
    background:#1e1e1e !important;
    color:#fff !important;
}

.dark-mode .sidebar{
    background:#000 !important;
}

.dark-mode .navbar{
    background:#000 !important;
}

.dark-mode a{
    color:#fff !important;
}

/* Admin panel dark mode overrides */
.dark-mode .header-left h4 { color: #f1f5f9; }
.dark-mode .header-left h4 span { color: #64748b; }
.dark-mode .header-date { background: rgba(255,255,255,0.06); color: #94a3b8; }
.dark-mode .header-admin-badge { background: rgba(37,99,235,0.2); }
.dark-mode .card-modern { background: #1e293b; border-color: rgba(255,255,255,0.08); }
.dark-mode .card-modern .card-header-custom { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
.dark-mode .card-modern .card-header-custom h6 { color: #e2e8f0; }
.dark-mode .table-modern thead th { color: #64748b; border-color: rgba(255,255,255,0.08); }
.dark-mode .table-modern tbody td { color: #cbd5e1; border-color: rgba(255,255,255,0.06); }
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,0.04); }
.dark-mode .sidebar-toggle { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1); color: #94a3b8; }
.dark-mode .sidebar-toggle:hover { background: rgba(255,255,255,0.12); color: white; }
.dark-mode .form-label { color: #cbd5e1; }
.dark-mode .form-control, .dark-mode .form-select { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); color: #e2e8f0; }
.dark-mode .form-control:focus, .dark-mode .form-select:focus { background: rgba(255,255,255,0.08); color: #e2e8f0; }
.dark-mode .stat-card { background: #1e293b; border-color: rgba(255,255,255,0.08); }
.dark-mode .stat-card .stat-value { color: #f1f5f9; }
.dark-mode .stat-card .stat-label { color: #64748b; }
.dark-mode .section-header h5 { color: #e2e8f0; }
</style>

<button class="btn dark-mode-control" id="darkModeToggle"
style="<?php echo !empty($darkModeInTopbar) ? 'position:static;' : 'position:fixed;top:15px;right:20px;z-index:9999;'; ?>background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);color:#fff;border-radius:10px;padding:8px 14px;font-size:13px;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s ease;"
onclick="toggleDarkMode()">
    <span id="darkModeIcon">&#127769;</span>
    <span id="darkModeText">Dark Mode</span>
</button>

<script>
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode");
    const isDark = document.body.classList.contains("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    updateDarkModeButton();
}

function updateDarkModeButton() {
    const isDark = document.body.classList.contains("dark-mode");
    const icon = document.getElementById("darkModeIcon");
    const text = document.getElementById("darkModeText");
    if (icon && text) {
        icon.textContent = isDark ? "\u2600\ufe0f" : "\ud83c\udf19";
        text.textContent = isDark ? "Light Mode" : "Dark Mode";
    }
}

(function() {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") {
        document.body.classList.add("dark-mode");
    }
    updateDarkModeButton();
})();
</script>