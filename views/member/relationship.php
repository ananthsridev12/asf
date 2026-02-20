<?php
// views/member/relationship.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Relationship Finder</title>
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
    <h3 class="mb-3">Relationship Finder</h3>

    <form method="get" action="/index.php" class="card p-3 mb-3">
      <input type="hidden" name="route" value="member-relationship">
      <div class="row g-3">
        <div class="col-md-4 position-relative">
          <label class="form-label">Person 1 Search</label>
          <input type="text" id="person1_search" class="form-control" placeholder="Type name or ID" value="<?php echo $person1 ? htmlspecialchars(($person1['display_name'] ?? $person1['full_name']) . ' (ID: ' . $person1['person_id'] . ')', ENT_QUOTES, 'UTF-8') : ''; ?>">
          <input type="hidden" name="id1" id="id1" value="<?php echo htmlspecialchars((string)($_GET['id1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          <div id="person1_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="col-md-4 position-relative">
          <label class="form-label">Person 2 Search</label>
          <input type="text" id="person2_search" class="form-control" placeholder="Type name or ID" value="<?php echo $person2 ? htmlspecialchars(($person2['display_name'] ?? $person2['full_name']) . ' (ID: ' . $person2['person_id'] . ')', ENT_QUOTES, 'UTF-8') : ''; ?>">
          <input type="hidden" name="id2" id="id2" value="<?php echo htmlspecialchars((string)($_GET['id2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          <div id="person2_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary">Find Relationship</button>
        </div>
      </div>
    </form>

    <?php if ($person1 && $person2): ?>
      <?php if ($result && ($result['type'] ?? '') === 'spouse'): ?>
        <div class="alert alert-success">
          Relationship: <strong>Spouse</strong><br>
          Status: <?php echo htmlspecialchars((string)($result['marriage']['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><br>
          Marriage date: <?php echo htmlspecialchars((string)($result['marriage']['marriage_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php elseif ($result && ($result['type'] ?? '') === 'explicit'): ?>
        <div class="alert alert-success">
          Relationship: <strong><?php echo htmlspecialchars((string)($result['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
      <?php elseif ($result && ($result['type'] ?? '') === 'lineage' && !empty($result['lca'])): ?>
        <div class="alert alert-success">
          <?php if (!empty($result['label'])): ?>
            Relationship: <strong><?php echo htmlspecialchars((string)$result['label'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
          <?php endif; ?>
          Closest common ancestor: <strong><?php echo htmlspecialchars($result['lca']['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
          (ID: <?php echo (int)$result['lca']['person_id']; ?>)<br>
          Person 1 distance: <?php echo (int)$result['gen1']; ?> generations<br>
          Person 2 distance: <?php echo (int)$result['gen2']; ?> generations
        </div>
      <?php else: ?>
        <div class="alert alert-warning">No relationship found.</div>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-muted">Enter two person IDs to calculate relationship.</div>
    <?php endif; ?>
  </div>
  <script>
    (function () {
      function attachSearch(inputId, hiddenId, resultsId) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const results = document.getElementById(resultsId);
        let timer = null;

        function clearList() {
          results.innerHTML = '';
        }

        function selectItem(item) {
          input.value = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
          hidden.value = item.person_id;
          clearList();
        }

        input.addEventListener('input', function () {
          const q = input.value.trim();
          hidden.value = '';
          clearList();
          if (q.length < 2) return;

          clearTimeout(timer);
          timer = setTimeout(function () {
            fetch('/index.php?route=member-person-search&q=' + encodeURIComponent(q))
              .then(function (res) { return res.json(); })
              .then(function (data) {
                clearList();
                data.forEach(function (item) {
                  const btn = document.createElement('button');
                  btn.type = 'button';
                  btn.className = 'list-group-item list-group-item-action';
                  btn.textContent = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
                  btn.addEventListener('click', function () {
                    selectItem(item);
                  });
                  results.appendChild(btn);
                });
              });
          }, 250);
        });

        document.addEventListener('click', function (e) {
          if (!results.contains(e.target) && e.target !== input) {
            clearList();
          }
        });
      }

      attachSearch('person1_search', 'id1', 'person1_results');
      attachSearch('person2_search', 'id2', 'person2_results');
    })();
  </script>
</body>
</html>
