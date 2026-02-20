<?php
// views/member/descendants.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Descendants</title>
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
    <h3 class="mb-3">Descendants of <?php echo htmlspecialchars($person['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>

    <?php if (empty($descendants)): ?>
      <div class="text-muted">No descendants found.</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($descendants as $d): ?>
          <li class="list-group-item">
            <?php echo htmlspecialchars($d['full_name'], ENT_QUOTES, 'UTF-8'); ?>
            <span class="text-muted">(ID: <?php echo (int)$d['person_id']; ?>, Depth: <?php echo (int)$d['depth_level']; ?>)</span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</body>
</html>
