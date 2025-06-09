<?php
// Ensure user is an admin, otherwise redirect
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. You must be an administrator to view this page.";
    redirect("index.php?page=login"); // Or 'home' if you prefer
}

// Page Title (Optional, but good practice if your header uses it)
$page_title = "Transaction Summary";
?>

<h2>Transaction Summary & Reports</h2>

<div class="transactions-table-container">
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>User Name</th>
                <th>Workshop Title</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Booking Date</th>
                <th>Payment Status</th>
                <th>Transaction ID</th>
            </tr>
        </thead>
        <tbody>
                      <?php
            // SQL query to fetch transactions with user and workshop details
            $sql = "SELECT 
                        b.id AS booking_id, 
                        u.full_name AS user_name, 
                        w.title AS workshop_title, 
                        b.quantity, 
                        b.total_price, 
                        b.booking_date, 
                        b.payment_status, 
                        b.transaction_id 
                    FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    JOIN workshops w ON b.workshop_id = w.id
                    ORDER BY b.booking_date DESC";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Loop through each transaction and display it
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['workshop_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                        echo "<td>" . htmlspecialchars(number_format($row['total_price'], 2)) . "</td>"; // Format price
                        echo "<td>" . htmlspecialchars(date('M j, Y g:i A', strtotime($row['booking_date']))) . "</td>"; // Format date
                        echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['transaction_id']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No transactions found.</td></tr>";
                }
                $stmt->close();
            } else {
                // Error in preparing the statement
                echo "<tr><td colspan='8'>Error preparing statement to fetch transactions: " . htmlspecialchars($conn->error) . "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<style>
    /* Optional: Some basic styling for the table */
    .transactions-table-container {
        margin-top: 20px;
        overflow-x: auto; /* For responsive tables if content is wide */
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
</style>