# 03 - Relationship Engine

File: `services/RelationshipEngine.php`

## Purpose

Compute relationship between two persons without name-based assumptions.

## Inputs Used

Only:

- `father_id`
- `mother_id`
- `spouse_id`

## Core Method

- `getRelationship(int $personAId, int $personBId): array`

Returns:

- `title`
- `cousin_level`
- `removed`
- `generation_difference`
- `side`
- optional `lca_id`, `lca_name`

## Logic Summary

1. Validate persons exist.
2. Check direct relationships first:
   - parent/child
   - spouse
   - sibling
3. Check in-law patterns.
4. Build ancestor maps for both persons (cached).
5. Find lowest common ancestor (LCA).
6. Compute distances `X` (A->LCA) and `Y` (B->LCA).
7. Derive title using rules:
   - parent/child/sibling/uncle/aunt/nephew/niece
   - cousin level = `min(X, Y) - 1`
   - removed = `abs(X - Y)`
8. Side detection from first upward edge:
   - father -> `Paternal`
   - mother -> `Maternal`
   - mixed -> `Both`

## Generation Difference

- `generation_difference = Y - X`
- Negative means B is older generation than A.
- Positive means B is younger generation than A.

## Scope

- Ancestor traversal limited to 6 levels for performance/control.
- Supports side branches through sibling/cousin logic.

## Where Used

- `controllers/MemberController.php`
  - `familyList()`
  - `relationship()`
