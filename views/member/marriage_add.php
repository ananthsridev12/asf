<?php
// views/member/marriage_add.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Marriage</title>
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
    <h3 class="mb-3">Add Marriage</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=member-marriage-add" class="card p-3 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="row g-3">
        <div class="col-md-6 position-relative">
          <label class="form-label">Person 1 Search</label>
          <input type="text" id="person1_search" class="form-control" placeholder="Type name or ID">
          <input type="hidden" name="person1_id" id="person1_id" required>
          <div id="person1_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>

        <div class="col-md-6 position-relative">
          <label class="form-label">Person 2 Search</label>
          <input type="text" id="person2_search" class="form-control" placeholder="Type name or ID">
          <input type="hidden" name="person2_id" id="person2_id" required>
          <div id="person2_results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Marriage Date</label>
          <input type="date" name="marriage_date" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Divorce Date</label>
          <input type="date" name="divorce_date" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="married" selected>Married</option>
            <option value="divorced">Divorced</option>
            <option value="widowed">Widowed</option>
          </select>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save Marriage</button>
      </div>
    </form>
  </div>

  <script>
    (function () {
      function attachSearch(inputId, hiddenId, resultsId) {
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
        }

        input.addEventListener('input', function () {
          const q = input.value.trim();
          hidden.value = '';
          clearResults();
          if (q.length < 2) {
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

      attachSearch('person1_search', 'person1_id', 'person1_results');
      attachSearch('person2_search', 'person2_id', 'person2_results');
    })();
  </script>
</body>
</html>
