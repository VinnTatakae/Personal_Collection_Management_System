<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if (isset($_POST['add_catalog'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $release_year = $_POST['release_year'];

    $query = "INSERT INTO catalog_items (title, category, release_year, created_by) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $title, $category, $release_year, $user_id);
    $stmt->execute();
}

if (isset($_POST['update_catalog'])) {
    $id = $_POST['catalog_id'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $release_year = $_POST['release_year'];

    $query = "UPDATE catalog_items SET title=?, category=?, release_year=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $title, $category, $release_year, $id);
    $stmt->execute();
}

if (isset($_POST['delete_catalog'])) {
    $id = $_POST['catalog_id'];
    $stmt = $conn->prepare("DELETE FROM catalog_items WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM catalog_items WHERE 1";

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR release_year LIKE ?)";
}
if (!empty($category)) {
    $sql .= " AND category = ?";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($search) && !empty($category)) {
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $category);
} elseif (!empty($search)) {
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
} elseif (!empty($category)) {
    $stmt->bind_param("s", $category);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary">Welcome, Admin <?= htmlspecialchars($username); ?></h3>
        <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="card shadow-sm p-4">
        <h4 class="text-primary mb-3">Manage Catalog Items</h4>

        <form method="GET" class="d-flex mb-3">
            <input type="text" name="search" class="form-control me-2" placeholder="Search title or year..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <select name="category" class="form-select me-2" style="max-width: 200px;">
                <option value="">All Categories</option>
                <option value="Game" <?= (($_GET['category'] ?? '') === 'Game') ? 'selected' : '' ?>>Game</option>
                <option value="Film" <?= (($_GET['category'] ?? '') === 'Film') ? 'selected' : '' ?>>Film</option>
                <option value="Novel" <?= (($_GET['category'] ?? '') === 'Novel') ? 'selected' : '' ?>>Novel</option>
            </select>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>

        
        <table class="table table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Release Year</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['release_year']) ?></td>
                            <td>


                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="catalog_id" value="<?= $row['id'] ?>">
                                    <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" class="form-control d-inline w-auto" required>
                                    <select name="category" class="form-select d-inline w-auto" required>
                                        <option <?= $row['category'] == 'Game' ? 'selected' : '' ?>>Game</option>
                                        <option <?= $row['category'] == 'Film' ? 'selected' : '' ?>>Film</option>
                                        <option <?= $row['category'] == 'Novel' ? 'selected' : '' ?>>Novel</option>
                                    </select>
                                    <input type="number" name="release_year" value="<?= $row['release_year'] ?>" class="form-control d-inline w-auto" required>
                                    <button type="submit" name="update_catalog" class="btn btn-sm btn-outline-success">Save</button>
                                </form>

                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="catalog_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete_catalog" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <hr>

        
        <h5>Add New Catalog</h5>
        <form method="POST" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="title" class="form-control" placeholder="Title" required>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select" required>
                    <option value="Game">Game</option>
                    <option value="Film">Film</option>
                    <option value="Novel">Novel</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="release_year" class="form-control" placeholder="Year" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_catalog" class="btn btn-primary w-100">Add</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
