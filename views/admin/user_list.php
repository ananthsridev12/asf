<?php
// views/admin/user_list.php
require_role('admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Admin</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=admin-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-user-add">Add User</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Users</h3>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Active</th>
              <th>Person ID</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $u): ?>
              <tr>
                <td><?php echo (int)$u['user_id']; ?></td>
                <td><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($u['role_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int)$u['is_active'] === 1 ? 'Yes' : 'No'; ?></td>
                <td><?php echo $u['person_id'] ? (int)$u['person_id'] : '-'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
