# 01 - Project Overview

## Tech Stack

- PHP (no framework)
- MySQL (InnoDB)
- Bootstrap 5 (view layer)
- Simple front controller routing via `index.php`

## High-Level Structure

- `index.php`: Route dispatch and bootstrap.
- `config/`: DB config.
- `controllers/`: Request handling and business orchestration.
- `models/`: Data access helpers.
- `services/`: Reusable domain logic (`RelationshipEngine`).
- `views/`: Admin/member/auth UI templates.
- `sql/`: Migration/seed scripts.

## Main Request Flow

1. Browser hits `index.php?route=...`
2. `index.php` checks auth/role (`admin` or `member`)
3. Controller action executes
4. Controller loads model/service data
5. Controller includes a view from `views/...`

## Important Modules

- `controllers/MemberController.php`
  - Profile
  - Add family member
  - Add marriage
  - Family list
  - Ancestors/descendants/tree
  - Relationship finder

- `controllers/PersonController.php`
  - Admin CRUD on persons
  - Assign parent and marriage

- `services/RelationshipEngine.php`
  - Graph traversal using `father_id`, `mother_id`, `spouse_id`
  - LCA-based relationship logic
  - Cousin level, removed count, side detection

## Role Model

- Admin: full management
- Member: own family actions + relationship views

## Notes

- Legacy tables (`parent_child`, `marriages`) are still used for write paths and compatibility.
- New relationship computation uses graph columns in `persons`.
