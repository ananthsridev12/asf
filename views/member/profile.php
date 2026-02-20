<?php
// views/member/profile.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile</title>
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
    <h3 class="mb-3">My Profile</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/index.php?route=member-profile" class="card p-3 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($person['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Gender</label>
          <?php $g = $person['gender'] ?? 'unknown'; ?>
          <select name="gender" class="form-select">
            <option value="unknown" <?php echo $g === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
            <option value="male" <?php echo $g === 'male' ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?php echo $g === 'female' ? 'selected' : ''; ?>>Female</option>
            <option value="other" <?php echo $g === 'other' ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Family Line (Branch) *</label>
          <select name="branch_id" class="form-select" required>
            <option value="">Select Family Line</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?php echo (int)$b['branch_id']; ?>" <?php echo (int)($person['branch_id'] ?? 0) === (int)$b['branch_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b['branch_name'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Family Line means your main ancestral line/group in this family tree.</div>
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
            <input class="form-check-input" type="checkbox" name="is_alive" id="is_alive" <?php echo (int)($person['is_alive'] ?? 1) === 1 ? 'checked' : ''; ?>>
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
        <button type="submit" class="btn btn-primary">Save Profile</button>
      </div>
    </form>
  </div>
</body>
</html>
