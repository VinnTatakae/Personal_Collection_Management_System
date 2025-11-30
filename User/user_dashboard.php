<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

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

if (!empty($search)) {
    $query .= " AND (ci.title LIKE ? OR ci.release_year LIKE ?)";
}
if (!empty($category)) {
    $query .= " AND ci.category = ?";
}

$stmt = $conn->prepare($query);

if (!empty($search) && !empty($category)) {
    $like = "%$search%";
    $stmt->bind_param("isss", $user_id, $like, $like, $category);
} elseif (!empty($search)) {
    $like = "%$search%";
    $stmt->bind_param("iss", $user_id, $like, $like);
} elseif (!empty($category)) {
    $stmt->bind_param("is", $user_id, $category);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary">Welcome, <?= htmlspecialchars($username) ?>!</h3>
        <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="card shadow-sm p-4">
        <div class="d-flex justify-content-between mb-3">
            <h5>Your Collections</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Collection</button>
        </div>

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
       
        <table class="table table-striped table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Release Year</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= htmlspecialchars($row['release_year']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td>
                          <form method="POST" action="../entities/collection.php" class="d-inline">
                              <input type="hidden" name="collection_id" value="<?= $row['collection_id'] ?>">
                              <select name="status" class="form-select form-select-sm d-inline w-auto">
                                  <option value="To Play/Read/Watch" <?= $row['status'] === 'To Play/Read/Watch' ? 'selected' : '' ?>>To Play/Read/Watch</option>
                                  <option value="In Progress" <?= $row['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                  <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                              </select>
                              <button type="submit" name="update_collection" class="btn btn-sm btn-outline-primary">Update</button>
                          </form>
                          <form method="POST" action="../entities/collection.php" class="d-inline">
                              <input type="hidden" name="collection_id" value="<?= $row['collection_id'] ?>">
                              <button type="submit" name="delete_collection" class="btn btn-sm btn-outline-danger">Delete</button>
                          </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="../entities/collection.php">
        <div class="modal-header">
          <h5 class="modal-title">Add New Collection</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Choose Item</label>
            <select name="catalog_item" class="form-select" required>
              <option value="">-- Select Item --</option>
              <?php
              $catalogQuery = "SELECT id, title FROM catalog_items ORDER BY title ASC";
              $catalogResult = $conn->query($catalogQuery);
              while ($item = $catalogResult->fetch_assoc()) {
                  echo "<option value='{$item['id']}'>{$item['title']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
              <option value="To Play/Read/Watch">To Play/Read/Watch</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_collection" class="btn btn-primary">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="../entities/collection.php">
        <div class="modal-header">
          <h5 class="modal-title">Add New Collection</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
              <option value="To Play/Read/Watch">To Play/Read/Watch</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_collection" class="btn btn-primary">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
