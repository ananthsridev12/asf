<?php
// views/member/person_edit.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Family Person</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Member</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=member-family">Family List</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Edit Family Person</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=member-person-edit" class="card p-3 shadow-sm mb-4">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="person_id" value="<?php echo (int)$person['person_id']; ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Gender</label>
          <?php $g = (string)($person['gender'] ?? 'unknown'); ?>
          <select name="gender" class="form-select">
            <option value="unknown" <?php echo $g === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
            <option value="male" <?php echo $g === 'male' ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?php echo $g === 'female' ? 'selected' : ''; ?>>Female</option>
            <option value="other" <?php echo $g === 'other' ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Family Line</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($person['branch_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
        </div>

        <div class="col-md-3">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars((string)($person['date_of_birth'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Birth Year</label>
          <input type="number" name="birth_year" class="form-control" value="<?php echo htmlspecialchars((string)($person['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Date of Death</label>
          <input type="date" name="date_of_death" class="form-control" value="<?php echo htmlspecialchars((string)($person['date_of_death'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" <?php echo (int)($person['is_alive'] ?? 0) === 1 ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_alive">Is Alive</label>
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Blood Group</label>
          <input type="text" name="blood_group" class="form-control" value="<?php echo htmlspecialchars((string)($person['blood_group'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Occupation</label>
          <input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars((string)($person['occupation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Mobile</label>
          <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars((string)($person['mobile'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars((string)($person['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Location</label>
          <input type="text" name="current_location" class="form-control" value="<?php echo htmlspecialchars((string)($person['current_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Native Location</label>
          <input type="text" name="native_location" class="form-control" value="<?php echo htmlspecialchars((string)($person['native_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars((string)($person['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/index.php?route=member-marriage-add" class="btn btn-outline-secondary ms-2">Add Marriage</a>
      </div>
    </form>

    <div class="card p-3 mb-4">
      <h5>Add Parent</h5>
      <form method="post" action="/index.php?route=member-parent-add">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="child_id" value="<?php echo (int)$person['person_id']; ?>">

        <div class="mb-2 position-relative">
          <label class="form-label">Parent Search</label>
          <input type="text" id="parent_search" class="form-control" placeholder="Type name or ID">
          <input type="hidden" name="parent_id" id="parent_id" required>
          <div id="parent_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="mb-2">
          <label class="form-label">Parent Type</label>
          <select name="parent_type" class="form-select" required>
            <option value="father">Father</option>
            <option value="mother">Mother</option>
            <option value="adoptive">Adoptive</option>
            <option value="step">Step</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Birth Order</label>
          <input type="number" name="birth_order" class="form-control" min="1">
        </div>
        <button class="btn btn-secondary" type="submit">Add Parent</button>
      </form>
    </div>

    <div class="card p-3">
      <h5>Parents</h5>
      <?php if (empty($parentRows)): ?>
        <div class="text-muted">No parents assigned.</div>
      <?php else: ?>
        <ul class="list-group">
          <?php foreach ($parentRows as $pr): ?>
            <li class="list-group-item">
              <?php echo htmlspecialchars((string)$pr['full_name'], ENT_QUOTES, 'UTF-8'); ?>
              (<?php echo htmlspecialchars((string)$pr['parent_type'], ENT_QUOTES, 'UTF-8'); ?>)
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <script>
    (function () {
      const input = document.getElementById('parent_search');
      const hidden = document.getElementById('parent_id');
      const list = document.getElementById('parent_results');
      let timer = null;

      function clearList() { list.innerHTML = ''; }
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
                const a = document.createElement('button');
                a.type = 'button';
                a.className = 'list-group-item list-group-item-action';
                a.textContent = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
                a.addEventListener('click', function () { selectItem(item); });
                list.appendChild(a);
              });
            });
        }, 250);
      });

      document.addEventListener('click', function (e) {
        if (!list.contains(e.target) && e.target !== input) {
          clearList();
        }
      });
    })();
  </script>
</body>
</html>
