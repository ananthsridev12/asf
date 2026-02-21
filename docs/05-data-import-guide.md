# 05 - Excel to Database Import Guide

## Goal

Prepare family data in Excel and load into DB reliably.

## Recommended Excel Columns

- `external_id` (e.g., `P0000001`)
- `full_name`
- `gender`
- `date_of_birth`
- `birth_year`
- `father_external_id`
- `mother_external_id`
- `spouse_external_id`
- optional contact/profile columns

## Import Strategy

1. Import people first (without parent/spouse links).
2. Build map: `external_id -> person_id`.
3. Update `father_id`, `mother_id`, `spouse_id` by joining map.
4. Validate invalid self-links and missing references.
5. Optionally sync legacy tables `parent_child` and `marriages`.

## Validation Checklist

- No duplicate external IDs
- DOB format is valid (`YYYY-MM-DD` preferred)
- No row where person links to self
- Father/mother IDs exist in map
- Spouse links are mutual where applicable

## Post-Import SQL

Run:

- `sql/002_person_parent_spouse_columns.sql`

This backfills/normalizes graph columns and fixes common invalid links.

## Why This Matters

Relationship engine quality depends on correct `father_id`, `mother_id`, `spouse_id` values.
Names are never used for relationship inference.
