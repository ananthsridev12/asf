<?php
// views/admin/dashboard.php
require_role('admin');
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Admin</span>
      <div class="ms-auto">
        <span class="text-white me-3"><?php echo htmlspecialchars($user['username'] ?? ''); ?></span>
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=admin-person-add">Add Person</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-persons">Persons</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-users">Users</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Dashboard</h3>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="text-muted">Total Persons</div>
            <div class="display-6"><?php echo (int)($totalPersons ?? 0); ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="text-muted">Total Branches</div>
            <div class="display-6"><?php echo (int)($totalBranches ?? 0); ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="text-muted">Active Users</div>
            <div class="display-6"><?php echo (int)($totalUsers ?? 0); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
