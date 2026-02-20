<?php
// views/member/person_add.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Family Member</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <h3 class="mb-3">Add Family Member</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      Tip: keep <strong>Relation to</strong> as relation to selected <strong>Reference Person</strong> (default is you).
      Example: select your mother as reference + choose <strong>Brother</strong> to add your maternal uncle.
    </div>

    <form method="post" action="/index.php?route=member-person-add" class="card p-3 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="row g-3">
        <div class="col-md-6 position-relative">
          <label class="form-label">Reference Person (optional)</label>
          <input type="text" id="reference_search" class="form-control" placeholder="Default is you. Search to set another person">
          <input type="hidden" name="reference_person_id" id="reference_person_id">
          <div class="form-text">Relation selected below will be applied relative to this person.</div>
          <div id="reference_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="col-md-6 position-relative">
          <label class="form-label">Search Existing Person</label>
          <input type="text" id="existing_search" class="form-control" placeholder="Type name or ID">
          <input type="hidden" name="existing_person_id" id="existing_person_id">
          <div class="form-text">Select existing first to avoid duplicates.</div>
          <div id="existing_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Or New Full Name</label>
          <input type="text" name="full_name" id="full_name" class="form-control">
        </div>
        <div class="col-md-6 position-relative">
          <label class="form-label">Parent (optional)</label>
          <input type="text" id="parent_search" class="form-control" placeholder="Search parent name or ID">
          <input type="hidden" name="parent_person_id" id="parent_person_id">
          <div id="parent_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Parent Type (if selected)</label>
          <select name="parent_link_type" class="form-select">
            <option value="father">Father</option>
            <option value="mother">Mother</option>
            <option value="adoptive">Adoptive</option>
            <option value="step">Step</option>
          </select>
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
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" checked>
            <label class="form-check-label" for="is_alive">Is Alive</label>
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="date_of_birth" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Birth Year</label>
          <input type="number" name="birth_year" class="form-control" min="1800" max="2100">
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Location</label>
          <input type="text" name="current_location" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Native Location</label>
          <input type="text" name="native_location" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label">Relation to You</label>
          <select name="relation_type" id="relation_type" class="form-select">
            <option value="none" selected>No direct link</option>
            <option value="child">Child</option>
            <option value="father">Father</option>
            <option value="mother">Mother</option>
            <option value="brother">Brother</option>
            <option value="sister">Sister</option>
            <option value="grandfather">Grandfather</option>
            <option value="grandmother">Grandmother</option>
            <option value="spouse">Spouse</option>
          </select>
        </div>

        <div class="col-md-3" id="parent_type_wrap" style="display:none;">
          <label class="form-label">Your Parent Type</label>
          <select name="parent_type" class="form-select">
            <option value="father">Father</option>
            <option value="mother">Mother</option>
            <option value="adoptive">Adoptive</option>
            <option value="step">Step</option>
          </select>
        </div>

        <div class="col-md-3" id="birth_order_wrap" style="display:none;">
          <label class="form-label">Child Birth Order</label>
          <input type="number" name="birth_order" class="form-control" min="1">
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save Family Member</button>
      </div>
    </form>
  </div>

  <script>
    (function () {
      const relation = document.getElementById('relation_type');
      const parentWrap = document.getElementById('parent_type_wrap');
      const orderWrap = document.getElementById('birth_order_wrap');
      const fullName = document.getElementById('full_name');

      function toggleFields() {
        const isChild = relation.value === 'child';
        parentWrap.style.display = isChild ? '' : 'none';
        orderWrap.style.display = isChild ? '' : 'none';
      }

      function attachSearch(inputId, hiddenId, resultsId, onSelect) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const results = document.getElementById(resultsId);
        let timer = null;

        function clearResults() {
          results.innerHTML = '';
        }

        function selectItem(item) {
          input.value = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
          hidden.value = item.person_id;
          clearResults();
          if (typeof onSelect === 'function') {
            onSelect(item);
          }
        }

        input.addEventListener('input', function () {
          const q = input.value.trim();
          hidden.value = '';
          if (q.length < 2) {
            clearResults();
            return;
          }
          clearTimeout(timer);
          timer = setTimeout(function () {
            fetch('/index.php?route=member-person-search&q=' + encodeURIComponent(q))
              .then(function (res) { return res.json(); })
              .then(function (data) {
                clearResults();
                data.forEach(function (item) {
                  const btn = document.createElement('button');
                  btn.type = 'button';
                  btn.className = 'list-group-item list-group-item-action';
                  btn.textContent = (item.display_name || item.full_name) + ' (ID: ' + item.person_id + ')';
                  btn.addEventListener('click', function () { selectItem(item); });
                  results.appendChild(btn);
                });
              });
          }, 250);
        });

        document.addEventListener('click', function (e) {
          if (!results.contains(e.target) && e.target !== input) {
            clearResults();
          }
        });
      }

      relation.addEventListener('change', toggleFields);
      toggleFields();

      attachSearch('reference_search', 'reference_person_id', 'reference_results');
      attachSearch('existing_search', 'existing_person_id', 'existing_results', function () {
        fullName.value = '';
      });
      attachSearch('parent_search', 'parent_person_id', 'parent_results');

      fullName.addEventListener('input', function () {
        if (fullName.value.trim() !== '') {
          document.getElementById('existing_person_id').value = '';
          document.getElementById('existing_search').value = '';
          document.getElementById('existing_results').innerHTML = '';
        }
      });
    })();
  </script>
</body>
</html>
