-- Add graph columns used by RelationshipEngine.
ALTER TABLE persons
  ADD COLUMN IF NOT EXISTS father_id BIGINT(20) UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS mother_id BIGINT(20) UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS spouse_id BIGINT(20) UNSIGNED NULL;

-- Optional indexes for faster graph traversal.
ALTER TABLE persons
  ADD INDEX IF NOT EXISTS idx_persons_father_id (father_id),
  ADD INDEX IF NOT EXISTS idx_persons_mother_id (mother_id),
  ADD INDEX IF NOT EXISTS idx_persons_spouse_id (spouse_id);

-- Backfill from parent_child for biological parents when available.
UPDATE persons c
INNER JOIN (
  SELECT child_id, MAX(parent_id) AS parent_id
  FROM parent_child
  WHERE parent_type = 'father'
  GROUP BY child_id
) p ON p.child_id = c.person_id
SET c.father_id = p.parent_id
WHERE c.father_id IS NULL OR c.father_id = 0;

UPDATE persons c
INNER JOIN (
  SELECT child_id, MAX(parent_id) AS parent_id
  FROM parent_child
  WHERE parent_type = 'mother'
  GROUP BY child_id
) p ON p.child_id = c.person_id
SET c.mother_id = p.parent_id
WHERE c.mother_id IS NULL OR c.mother_id = 0;

-- Backfill spouse links from latest marriage rows where missing.
UPDATE persons a
INNER JOIN marriages m ON m.person1_id = a.person_id
SET a.spouse_id = m.person2_id
WHERE (a.spouse_id IS NULL OR a.spouse_id = 0);

UPDATE persons b
INNER JOIN marriages m ON m.person2_id = b.person_id
SET b.spouse_id = m.person1_id
WHERE (b.spouse_id IS NULL OR b.spouse_id = 0);

-- Safety cleanup for invalid self-links.
UPDATE persons SET father_id = NULL WHERE father_id = person_id;
UPDATE persons SET mother_id = NULL WHERE mother_id = person_id;
UPDATE persons SET spouse_id = NULL WHERE spouse_id = person_id;

