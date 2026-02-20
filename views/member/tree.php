<?php
// views/member/tree.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Family Tree</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .tree ul { list-style: none; padding-left: 1rem; margin: 0; }
    .tree li { margin: 0.35rem 0; }
    .tree .node { border-left: 2px solid #dee2e6; padding-left: 0.75rem; }
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Member</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=member-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-family">Family List</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Family Tree</h3>
    <p class="text-muted mb-3">This view shows the lineage tree for your connected root family.</p>

    <?php
      $renderTree = function (int $id) use (&$renderTree, $peopleById, $childrenByParent): void {
          if (!isset($peopleById[$id])) {
              return;
          }
          $p = $peopleById[$id];
          $meta = [];
          if (!empty($p['dob_display'])) {
              $meta[] = 'DOB: ' . $p['dob_display'];
          }
          if (!empty($p['age_display'])) {
              $meta[] = 'Age: ' . $p['age_display'];
          }
          echo '<li>';
          echo '<div class="node">';
          echo '<strong>' . htmlspecialchars((string)($p['display_name'] ?? $p['full_name']), ENT_QUOTES, 'UTF-8') . '</strong>';
          echo ' <span class="text-muted">(ID: ' . (int)$p['person_id'] . ')</span>';
          if (!empty($meta)) {
              echo '<div class="small text-muted">' . htmlspecialchars(implode(' | ', $meta), ENT_QUOTES, 'UTF-8') . '</div>';
          }
          echo '</div>';

          $children = $childrenByParent[$id] ?? [];
          if (!empty($children)) {
              echo '<ul>';
              foreach ($children as $cid) {
                  $renderTree((int)$cid);
              }
              echo '</ul>';
          }

          echo '</li>';
      };
    ?>

    <?php if (empty($peopleById)): ?>
      <div class="alert alert-warning">No tree data available.</div>
    <?php else: ?>
      <div class="card p-3 tree">
        <ul>
          <?php $renderTree($rootId); ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
