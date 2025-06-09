<?php if (!isAdmin()) { redirect("index.php?page=home"); } ?>
<h2>Manage Workshops</h2>
<p><a href="index.php?page=admin_add_workshop">Add New Workshop</a></p>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
            <tbody>
        <?php
        // SQL query to fetch all workshops
        $sql = "SELECT id, title, category, workshop_date, price FROM workshops ORDER BY id ASC"; // Or order by workshop_date, title, etc.
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Loop through each workshop and display it
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                    // Format the workshop_date (DATETIME) to just Date part if needed, or full datetime
                    echo "<td>" . htmlspecialchars(date('Y-m-d', strtotime($row['workshop_date']))) . "</td>"; 
                    echo "<td>RM" . htmlspecialchars(number_format($row['price'], 2)) . "</td>";
                    
                    // Placeholder links for Edit and Delete - these will need proper URLs later
                    $edit_url = "index.php?page=admin_edit_workshop&id=" . $row['id']; // Example URL
                    $delete_url = "index.php?page=admin_delete_workshop&id=" . $row['id']; // Example URL

                    echo "<td>";
                    echo "<a href=\"" . htmlspecialchars($edit_url) . "\">Edit</a> | ";
                    echo "<a href=\"" . htmlspecialchars($delete_url) . "\" onclick=\"return confirm('Are you sure you want to delete this workshop?');\">Delete</a> ";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No workshops found.</td></tr>";
            }
            $stmt->close();
        } else {
            // Error in preparing the statement
            echo "<tr><td colspan='6'>Error preparing statement to fetch workshops: " . htmlspecialchars($conn->error) . "</td></tr>";
        }
        ?>
    </tbody>
    </tbody>
</table>