<?php
include_once '../config/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['add_collection'])) {
    $catalog_item = $_POST['catalog_item'];
    $status = $_POST['status'];

    $query = "INSERT INTO collections (user_id, catalog_item, status, added_at) VALUES (?, ?, ?, CURDATE())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $user_id, $catalog_item, $status);

    if ($stmt->execute()) {
        header("Location: ../user/user_dashboard.php?success=added");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

$query = "
    SELECT 
        c.id AS collection_id, 
        ci.title, 
        ci.category, 
        ci.release_year, 
        c.status
    FROM collections c
    JOIN catalog_items ci ON c.catalog_item = ci.id
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();


if (isset($_POST['update_collection'])) {
    $collection_id = $_POST['collection_id'];
    $status = $_POST['status'];

    $updateQuery = "UPDATE collections SET status = ? WHERE id = ? AND user_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sii", $status, $collection_id, $user_id);

    if ($updateStmt->execute()) {
        header("Location: ../user/user_dashboard.php?success=updated");
        exit();
    } else {
        echo "Error: " . $updateStmt->error;
    }
}

if (isset($_POST['delete_collection'])) {
    $collection_id = $_POST['collection_id'];

    $deleteQuery = "DELETE FROM collections WHERE id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $collection_id, $user_id);

    if ($deleteStmt->execute()) {
        header("Location: ../user/user_dashboard.php?success=added");
    } else {
        echo "Error: " . $deleteStmt->error;
    }
}
?>