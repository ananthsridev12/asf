<?php
// views/admin/person_edit.php
require_role('admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Person</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Admin</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=admin-persons">Persons</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=admin-dashboard">Dashboard</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3 class="mb-3">Edit Person</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=admin-person-update" class="card p-3 shadow-sm mb-4">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="person_id" value="<?php echo (int)$person['person_id']; ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($person['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-select">
            <?php $g = $person['gender']; ?>
            <option value="unknown" <?php echo $g === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
            <option value="male" <?php echo $g === 'male' ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?php echo $g === 'female' ? 'selected' : ''; ?>>Female</option>
            <option value="other" <?php echo $g === 'other' ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Family Line (Branch) *</label>
          <select name="branch_id" class="form-select" required>
            <?php foreach ($branches as $b): ?>
              <option value="<?php echo (int)$b['branch_id']; ?>" <?php echo (int)$person['branch_id'] === (int)$b['branch_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b['branch_name'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Family Line = ancestral branch/group used to organize connected records.</div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($person['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Birth Year</label>
          <input type="number" name="birth_year" class="form-control" value="<?php echo htmlspecialchars((string)($person['birth_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Date of Death</label>
          <input type="date" name="date_of_death" class="form-control" value="<?php echo htmlspecialchars($person['date_of_death'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" <?php echo (int)$person['is_alive'] === 1 ? 'checked' : ''; ?>>
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
        <div class="col-12">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars((string)($person['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Location</label>
          <input type="text" name="current_location" class="form-control" value="<?php echo htmlspecialchars((string)($person['current_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Native Location</label>
          <input type="text" name="native_location" class="form-control" value="<?php echo htmlspecialchars((string)($person['native_location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card p-3 mb-4">
          <h5>Add Parent</h5>
          <form method="post" action="/index.php?route=admin-parent-add">
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

        <div class="card p-3 mb-4">
          <h5>Parents</h5>
          <?php if (empty($parentRows)): ?>
            <div class="text-muted">No parents assigned.</div>
          <?php else: ?>
            <ul class="list-group">
              <?php foreach ($parentRows as $pr): ?>
                <li class="list-group-item">
                  <?php echo htmlspecialchars($pr['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                  (<?php echo htmlspecialchars($pr['parent_type'], ENT_QUOTES, 'UTF-8'); ?>)
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card p-3 mb-4">
          <h5>Add Marriage</h5>
          <form method="post" action="/index.php?route=admin-marriage-add">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="person_id" value="<?php echo (int)$person['person_id']; ?>">

            <div class="mb-2 position-relative">
              <label class="form-label">Spouse Search</label>
              <input type="text" id="spouse_search" class="form-control" placeholder="Type name or ID">
              <input type="hidden" name="spouse_id" id="spouse_id" required>
              <div id="spouse_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
            </div>
            <div class="mb-2">
              <label class="form-label">Marriage Date</label>
              <input type="date" name="marriage_date" class="form-control">
            </div>
            <div class="mb-2">
              <label class="form-label">Divorce Date</label>
              <input type="date" name="divorce_date" class="form-control">
            </div>
            <div class="mb-2">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="married">Married</option>
                <option value="divorced">Divorced</option>
                <option value="widowed">Widowed</option>
              </select>
            </div>
            <button class="btn btn-secondary" type="submit">Add Marriage</button>
          </form>
        </div>

        <div class="card p-3 mb-4">
          <h5>Marriages</h5>
          <?php if (empty($marriageRows)): ?>
            <div class="text-muted">No marriages recorded.</div>
          <?php else: ?>
            <ul class="list-group">
              <?php foreach ($marriageRows as $m): ?>
                <li class="list-group-item">
                  <?php echo htmlspecialchars($m['person1_name'], ENT_QUOTES, 'UTF-8'); ?>
                  &amp;
                  <?php echo htmlspecialchars($m['person2_name'], ENT_QUOTES, 'UTF-8'); ?>
                  (<?php echo htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8'); ?>)
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card p-3">
      <h5>Media Upload</h5>
      <form method="post" action="/index.php?route=admin-media-upload" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="person_id" value="<?php echo (int)$person['person_id']; ?>">
        <div class="mb-2">
          <input type="file" name="media_file" class="form-control" required>
        </div>
        <button class="btn btn-secondary" type="submit">Upload</button>
      </form>

      <div class="mt-3">
        <h6>Uploaded Media</h6>
        <?php if (empty($mediaRows)): ?>
          <div class="text-muted">No media uploaded.</div>
        <?php else: ?>
          <ul class="list-group">
            <?php foreach ($mediaRows as $m): ?>
              <li class="list-group-item">
                <a href="<?php echo htmlspecialchars($m['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                  <?php echo htmlspecialchars(basename($m['file_path']), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <span class="text-muted">(<?php echo htmlspecialchars($m['media_type'], ENT_QUOTES, 'UTF-8'); ?>)</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    (function () {
      function attachSearch(inputId, hiddenId, resultsId) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const list = document.getElementById(resultsId);
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
      }

      attachSearch('parent_search', 'parent_id', 'parent_results');
      attachSearch('spouse_search', 'spouse_id', 'spouse_results');
    })();
  </script>
</body>
</html>
