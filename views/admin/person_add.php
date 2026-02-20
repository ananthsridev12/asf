<?php
// views/admin/person_add.php
require_role('admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Person</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Admin</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=admin-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-persons">Persons</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Add Person</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=admin-person-add" class="card p-3 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-select">
            <option value="unknown" selected>Unknown</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Family Line (Branch) *</label>
          <select name="branch_id" class="form-select" required>
            <option value="">Select Family Line</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?php echo (int)$b['branch_id']; ?>">
                <?php echo htmlspecialchars($b['branch_name'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Family Line = ancestral branch/group used to organize connected records.</div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="date_of_birth" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Birth Year</label>
          <input type="number" name="birth_year" class="form-control" min="1800" max="2100">
        </div>
        <div class="col-md-3">
          <label class="form-label">Date of Death</label>
          <input type="date" name="date_of_death" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" checked>
            <label class="form-check-label" for="is_alive">Is Alive</label>
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Blood Group</label>
          <input type="text" name="blood_group" class="form-control" placeholder="A+">
        </div>
        <div class="col-md-3">
          <label class="form-label">Occupation</label>
          <input type="text" name="occupation" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Mobile</label>
          <input type="text" name="mobile" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control">
        </div>

        <div class="col-12">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Location</label>
          <input type="text" name="current_location" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Native Location</label>
          <input type="text" name="native_location" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Parent Search (optional)</label>
          <input type="text" id="parent_search" class="form-control" placeholder="Type name or ID">
          <input type="hidden" name="parent_id" id="parent_id">
          <div class="form-text">Type at least 2 letters, then select.</div>
          <div id="parent_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Parent Type</label>
          <select name="parent_type" class="form-select">
            <option value="">Select</option>
            <option value="father">Father</option>
            <option value="mother">Mother</option>
            <option value="adoptive">Adoptive</option>
            <option value="step">Step</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Birth Order</label>
          <input type="number" name="birth_order" class="form-control" min="1">
          <div class="form-text">Used for sibling ordering when DOB is unknown.</div>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save Person</button>
      </div>
    </form>
  </div>

  <script>
    (function () {
      const input = document.getElementById('parent_search');
      const hidden = document.getElementById('parent_id');
      const list = document.getElementById('parent_results');
      let timer = null;

      function clearList() {
        list.innerHTML = '';
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
          fetch('/index.php?route=admin-person-search&q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
              clearList();
              data.forEach(item => {
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
