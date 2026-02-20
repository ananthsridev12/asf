<?php
// views/member/dashboard.php
require_role('member');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Member Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <span class="navbar-brand">FamilyTree Member</span>
      <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm" href="/index.php?route=member-profile">My Profile</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-person-add">Add Family</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-marriage-add">Add Marriage</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-family">Family List</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-tree">Family Tree</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-ancestors">Ancestors</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-descendants">Descendants</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=member-relationship">Relationship</a>
        <a class="btn btn-outline-light btn-sm ms-2" href="/index.php?route=logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h3>Welcome</h3>
    <p class="text-muted">Use the links above to manage your profile and explore your family tree.</p>
  </div>
</body>
</html>
