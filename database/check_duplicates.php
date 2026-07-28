<?php
include(__DIR__ . '/../config/db.php');
$result = mysqli_query($conn, "SELECT employee_id, COUNT(*) as cnt FROM salary_structure GROUP BY employee_id HAVING cnt > 1");
if (mysqli_num_rows($result) > 0) {
    echo "<h3>Duplicates Found:</h3><ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>Employee ID: {$row['employee_id']} - Count: {$row['cnt']}</li>";
    }
    echo "</ul>";
} else {
    echo "<h3>No duplicates found!</h3>";
}
mysqli_close($conn);
?>