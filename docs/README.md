# FamilyTree Project Docs

This folder contains project and database documentation.

## Files

- `docs/01-project-overview.md` - Architecture, folder layout, request flow.
- `docs/02-database-structure.md` - Core tables, columns, and relationships.
- `docs/03-relationship-engine.md` - How relationship calculation works.
- `docs/04-setup-git-deploy.md` - GitHub + HostGator/cPanel workflow.
- `docs/05-data-import-guide.md` - How to collect Excel data and map into DB.

## Current Repo

- Main entry point: `index.php`
- Member logic: `controllers/MemberController.php`
- Admin person logic: `controllers/PersonController.php`
- Relationship service: `services/RelationshipEngine.php`
- Migration for graph columns: `sql/002_person_parent_spouse_columns.sql`
