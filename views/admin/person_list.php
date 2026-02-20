<?php
// views/admin/person_list.php
require_role('admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Persons</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Admin</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=admin-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-person-add">Add Person</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Persons</h3>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Gender</th>
              <th>Branch</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $p): ?>
              <tr>
                <td><?php echo (int)$p['person_id']; ?></td>
                <td><?php echo htmlspecialchars($p['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($p['gender'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int)$p['branch_id']; ?></td>
                <td>
                  <a class="btn btn-sm btn-primary" href="/index.php?route=admin-person-edit&id=<?php echo (int)$p['person_id']; ?>">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link" href="/index.php?route=admin-persons&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</body>
</html>
