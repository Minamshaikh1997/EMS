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

</style>

<button class="btn btn-dark"
style="position:fixed;top:15px;right:20px;z-index:9999;"
onclick="toggleDarkMode()">
🌙 Dark Mode
</button>

<script>

function toggleDarkMode(){

document.body.classList.toggle("dark-mode");

localStorage.setItem(
"theme",
document.body.classList.contains("dark-mode")
);

}

if(localStorage.getItem("theme")=="true")
{
document.body.classList.add("dark-mode");
}

</script>