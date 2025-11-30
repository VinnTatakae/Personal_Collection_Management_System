<?php
// admin/admin_dashboard.php
session_start();
include_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? null);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
        $stmt->execute();
        header("Location: ../admin/admin_dashboard.php?success=cat_added");
        exit();
    }
}

// Handle update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? null);
    if ($id > 0 && $name !== '') {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $desc, $id);
        $stmt->execute();
        header("Location: ../admin/admin_dashboard.php?success=cat_updated");
        exit();
    }
}

// Handle delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = (int) ($_POST['category_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: ../admin/admin_dashboard.php?success=cat_deleted");
        exit();
    }
}

// Fetch categories
$catRes = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// (Optional) Fetch all collections to view
$collRes = $conn->query("
    SELECT c.id, c.title, c.creator, c.release_year, c.status, c.added_at, cat.name AS category_name, u.username
    FROM collections c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.added_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once __DIR__ . '/../includes/header.php'; ?>

  <div class="card p-4 mb-4">
    <h5>Manage Categories</h5>
    <form method="POST" class="row g-2 align-items-center mb-3">
      <div class="col-md-4">
        <input type="text" name="name" class="form-control" placeholder="Category name" required>
      </div>
      <div class="col-md-5">
        <input type="text" name="description" class="form-control" placeholder="Description (optional)">
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary" name="add_category" type="submit">Add Category</button>
      </div>
    </form>

    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Action</th></tr></thead>
      <tbody>
        <?php while ($cat = $catRes->fetch_assoc()): ?>
          <tr>
            <td><?= $cat['id'] ?></td>
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td><?= htmlspecialchars($cat['description']) ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                <input type="text" name="description" value="<?= htmlspecialchars($cat['description']) ?>">
                <button type="submit" name="update_category" class="btn btn-sm btn-outline-success">Save</button>
              </form>
              <form method="POST" class="d-inline">
                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete category?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="card p-4">
    <h5>All Collections (users)</h5>
    <table class="table table-striped">
      <thead><tr><th>Title</th><th>Category</th><th>Year</th><th>User</th><th>Status</th><th>Added</th></tr></thead>
      <tbody>
        <?php while ($r = $collRes->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['category_name']) ?></td>
            <td><?= htmlspecialchars($r['release_year']) ?></td>
            <td><?= htmlspecialchars($r['username']) ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['added_at']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
