<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}


$user_id = (int) $_SESSION['user_id'];

// ADD COLLECTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_collection'])) {
    $title = trim($_POST['title'] ?? '');
    $creator = trim($_POST['creator'] ?? null);
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $release_year = !empty($_POST['release_year']) ? (int) $_POST['release_year'] : null;
    $description = trim($_POST['description'] ?? null);
    $status = $_POST['status'] ?? 'todo';

    // minimal validation
    if ($title === '' || $category_id <= 0) {
        header("Location: ../user/user_dashboard.php?error=missing");
        exit();
    }

    $query = "INSERT INTO collections (user_id, category_id, title, creator, release_year, description, status, added_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    // bind params - use s for strings, i for integers (release_year may be null)
    $stmt->bind_param(
        "iississ",
        $user_id,
        $category_id,
        $title,
        $creator,
        $release_year,
        $description,
        $status
    );

    if ($stmt->execute()) {
        header("Location: ../user/user_dashboard.php?success=added");
        exit();
    } else {
        echo "Error: " . $stmt->error;
        exit();
    }
}

// UPDATE COLLECTION (status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_collection'])) {
    $collection_id = (int) ($_POST['collection_id'] ?? 0);
    $status = $_POST['status'] ?? 'todo';

    $updateQuery = "UPDATE collections SET status = ? WHERE id = ? AND user_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sii", $status, $collection_id, $user_id);

    if ($updateStmt->execute()) {
        header("Location: ../user/user_dashboard.php?success=updated");
        exit();
    } else {
        echo "Error: " . $updateStmt->error;
        exit();
    }
}

// DELETE COLLECTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_collection'])) {
    $collection_id = (int) ($_POST['collection_id'] ?? 0);

    $deleteQuery = "DELETE FROM collections WHERE id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $collection_id, $user_id);

    if ($deleteStmt->execute()) {
        header("Location: ../user/user_dashboard.php?success=deleted");
        exit();
    } else {
        echo "Error: " . $deleteStmt->error;
        exit();
    }
}

// SELECT user's collections
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "
    SELECT 
        c.id AS collection_id,
        c.title,
        c.creator,
        c.release_year,
        c.description,
        c.status,
        c.added_at,
        cat.id AS category_id,
        cat.name AS category_name
    FROM collections c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.user_id = ?
";

$params = [];
$types = "i";
$params[] = $user_id;

if ($search !== '') {
    $sql .= " AND (c.title LIKE ? OR c.release_year LIKE ?)";
    $types .= "ss";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}
if ($category !== '') {
    $sql .= " AND cat.name = ?";
    $types .= "s";
    $params[] = $category;
}

$sql .= " ORDER BY c.added_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

// dynamic bind_param
$refArr = [];
$refArr[] = & $types;
for ($i = 0; $i < count($params); $i++) {
    $refArr[] = & $params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $refArr);

$stmt->execute();
$result = $stmt->get_result();

// Note: this file only processes actions and provides $result for user_dashboard to consume.
// If you want, you can redirect user_dashboard to include this file and use $result variable.
