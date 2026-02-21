# 02 - Database Structure

This is the practical schema used by current code.

## Core Tables

## `persons`

Primary entity table.

Commonly used columns:

- `person_id` (PK)
- `full_name`
- `gender`
- `date_of_birth`
- `birth_year`
- `date_of_death`
- `blood_group`
- `occupation`
- `mobile`
- `email`
- `address`
- `current_location`
- `native_location`
- `lineage_path`
- `depth_level`
- `root_id`
- `branch_id`
- `is_alive`
- `created_by`
- `father_id` (new graph column)
- `mother_id` (new graph column)
- `spouse_id` (new graph column)

Graph columns were added by:

- `sql/002_person_parent_spouse_columns.sql`

## `users`

Authentication table.

Used columns:

- `user_id` (PK)
- `username`
- `password_hash`
- `role` (`admin`/`member`)
- `person_id` (links member to person)

## `branches`

Family line/branch metadata.

Used columns:

- `branch_id` (PK)
- `branch_name`

## `parent_child`

Legacy parent-child relation table, still used for writes and compatibility.

Used columns:

- `parent_id`
- `child_id`
- `parent_type` (`father`, `mother`, `adoptive`, `step`)
- `birth_order`

## `marriages`

Legacy spouse relation table, still used for writes/history.

Used columns:

- `marriage_id`
- `person1_id`
- `person2_id`
- `marriage_date`
- `divorce_date`
- `status`

## `activity_logs`

Audit trail.

Used columns:

- `user_id`
- `action`
- `target_id`
- timestamp columns

## Relationship Dictionary Support

From `sql/001_relationship_dictionary_and_roles.sql`:

- `relationship_dictionary`
  - master labels and metadata (side/category/degree/generation)
- `person_roles`
  - optional role tags per person

## Effective Data Model

Runtime relationship engine currently computes using:

- `persons.father_id`
- `persons.mother_id`
- `persons.spouse_id`

Compatibility backfill is handled from:

- `parent_child`
- `marriages`

## Integrity Rules to Maintain

- `father_id != person_id`
- `mother_id != person_id`
- `spouse_id != person_id`
- Prefer mutual spouse links (`A.spouse_id = B` and `B.spouse_id = A`)
