<?php
// views/member/family_list.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Family List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Member</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=member-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Family List</h3>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Gender</th>
              <th>DOB</th>
              <th>Age</th>
              <th>Family Line</th>
              <th>Relationship</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $p): ?>
              <tr>
                <td><?php echo (int)$p['person_id']; ?></td>
                <td><?php echo htmlspecialchars((string)($p['display_name'] ?? $p['full_name']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($p['gender'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($p['dob_display'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($p['age_display'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($p['branch_label'] ?? ('Line #' . (int)$p['branch_id'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($relations[(int)$p['person_id']] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="/index.php?route=member-person-edit&id=<?php echo (int)$p['person_id']; ?>">Edit</a>
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
              <a class="page-link" href="/index.php?route=member-family&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</body>
</html>
