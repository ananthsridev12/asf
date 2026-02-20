-- MySQL-compatible migration (works even if IF NOT EXISTS on ALTER is unsupported).
-- 1) Add columns only when missing.
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons' AND COLUMN_NAME = 'father_id') = 0,
  'ALTER TABLE persons ADD COLUMN father_id BIGINT(20) UNSIGNED NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons' AND COLUMN_NAME = 'mother_id') = 0,
  'ALTER TABLE persons ADD COLUMN mother_id BIGINT(20) UNSIGNED NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons' AND COLUMN_NAME = 'spouse_id') = 0,
  'ALTER TABLE persons ADD COLUMN spouse_id BIGINT(20) UNSIGNED NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Add indexes only when missing.
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons' AND INDEX_NAME = 'idx_persons_father_id') = 0,
  'ALTER TABLE persons ADD INDEX idx_persons_father_id (father_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons' AND INDEX_NAME = 'idx_persons_mother_id') = 0,
  'ALTER TABLE persons ADD INDEX idx_persons_mother_id (mother_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons' AND INDEX_NAME = 'idx_persons_spouse_id') = 0,
  'ALTER TABLE persons ADD INDEX idx_persons_spouse_id (spouse_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Backfill father/mother from parent_child for biological parents when available.
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

-- 4) Backfill spouse links where missing.
UPDATE persons a
INNER JOIN marriages m ON m.person1_id = a.person_id
SET a.spouse_id = m.person2_id
WHERE a.spouse_id IS NULL OR a.spouse_id = 0;

UPDATE persons b
INNER JOIN marriages m ON m.person2_id = b.person_id
SET b.spouse_id = m.person1_id
WHERE b.spouse_id IS NULL OR b.spouse_id = 0;

-- 5) Safety cleanup for invalid self-links.
UPDATE persons SET father_id = NULL WHERE father_id = person_id;
UPDATE persons SET mother_id = NULL WHERE mother_id = person_id;
UPDATE persons SET spouse_id = NULL WHERE spouse_id = person_id;
