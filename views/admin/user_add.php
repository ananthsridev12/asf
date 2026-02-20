<?php
// views/admin/user_add.php
require_role('admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Admin</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=admin-users">Users</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Add User</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=admin-user-add" class="card p-3 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Username *</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Role *</label>
          <select name="role_id" class="form-select" required>
            <option value="">Select role</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?php echo (int)$r['role_id']; ?>"><?php echo htmlspecialchars($r['role_name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Person ID (optional)</label>
          <input type="number" name="person_id" class="form-control" min="1">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
            <label class="form-check-label" for="is_active">Active</label>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Create User</button>
      </div>
    </form>
  </div>
</body>
</html>
