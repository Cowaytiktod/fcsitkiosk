<?php if (!isAdmin()) { redirect("index.php?page=home"); } ?>
<h2>Manage Registered Members (Read Actions)</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Registered On</th>
        </tr>
    </thead>
    <tbody>
        <!-- TODO: Fetch and list users from DB (read only details for admin) -->
           <?php
            // Corrected SQL query and display
            $stmt = $conn->prepare("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                        // Corrected to use 'created_at'
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No users found.</td></tr>";
                }
                $stmt->close();
            } else {
               
                 echo "<tr><td colspan='5'>Error preparing statement to fetch users: " . htmlspecialchars($conn->error) . "</td></tr>";
            }
        ?>
      
    </tbody>
</table>