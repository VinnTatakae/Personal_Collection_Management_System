<?php
// user/user_dashboard.php
session_start();
include_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// ambil daftar kategori untuk dropdown
$catStmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$catStmt->execute();
$catResult = $catStmt->get_result();
$categories = $catResult->fetch_all(MYSQLI_ASSOC);

// include entities processing to get $result (collections)
include_once __DIR__ . '/../entities/collection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once __DIR__ . '/../includes/header.php'; ?>

  <div class="card shadow-sm p-4">
    <div class="d-flex justify-content-between mb-3">
        <h5>Your Collections</h5>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Collection</button>
    </div>

    <form method="GET" class="d-flex mb-3">
        <input type="text" name="search" class="form-control me-2" placeholder="Search title or year..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <select name="category" class="form-select me-2" style="max-width: 200px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['name']) ?>" <?= (($_GET['category'] ?? '') === $c['name']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
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
        <?php if (isset($result) && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td><?= htmlspecialchars($row['release_year']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>
                      <form method="POST" action="../entities/collection.php" class="d-inline">
                          <input type="hidden" name="collection_id" value="<?= $row['collection_id'] ?>">
                          <select name="status" class="form-select form-select-sm d-inline w-auto">
                              <option value="todo" <?= $row['status'] === 'todo' ? 'selected' : '' ?>>To Play/Read/Watch</option>
                              <option value="in_progress" <?= $row['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                              <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
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
        <?php else: ?>
            <tr><td colspan="5" class="text-center text-muted">No items found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Collection Modal -->
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
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Creator / Author</label>
            <input type="text" name="creator" class="form-control">
          </div>
          <div class="mb-3">
            <label>Category</label>
            <select name="category_id" class="form-select" required>
              <option value="">-- Select Category --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Release Year</label>
            <input type="number" name="release_year" class="form-control">
          </div>
          <div class="mb-3">
            <label>Description (optional)</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
              <option value="todo">To Play/Read/Watch</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
