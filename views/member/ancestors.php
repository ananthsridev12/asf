<?php
// views/member/ancestors.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ancestors</title>
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
    <h3 class="mb-3">Ancestors of <?php echo htmlspecialchars($person['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>

    <?php if (empty($ancestors)): ?>
      <div class="text-muted">No ancestors found.</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($ancestors as $a): ?>
          <li class="list-group-item">
            <?php echo htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8'); ?>
            <span class="text-muted">(ID: <?php echo (int)$a['person_id']; ?>)</span>
            <?php if (!empty($a['generation'])): ?>
              <span class="badge text-bg-secondary ms-2">Gen <?php echo (int)$a['generation']; ?></span>
            <?php endif; ?>
            <?php if (!empty($a['parent_type'])): ?>
              <span class="text-muted ms-2"><?php echo htmlspecialchars(ucfirst((string)$a['parent_type']), ENT_QUOTES, 'UTF-8'); ?> side</span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</body>
</html>
