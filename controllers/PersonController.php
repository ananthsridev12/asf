<?php
declare(strict_types=1);

// controllers/PersonController.php

final class PersonController
{
    private PDO $db;
    private PersonModel $persons;
    private BranchModel $branches;
    private ActivityLogModel $logs;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->persons = new PersonModel($db);
        $this->branches = new BranchModel($db);
        $this->logs = new ActivityLogModel($db);
    }

    public function list(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $total = $this->persons->countAll();
        $items = $this->persons->listPaginated($page, $perPage);
        $pages = (int)ceil($total / $perPage);

        include __DIR__ . '/../views/admin/person_list.php';
    }

    public function showCreate(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $branches = $this->branches->all();

        include __DIR__ . '/../views/admin/person_add.php';
    }

    public function create(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin-person-add');
            exit;
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? 'unknown';
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $birthYear = trim($_POST['birth_year'] ?? '');
        $dateOfDeath = trim($_POST['date_of_death'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $currentLocation = trim($_POST['current_location'] ?? '');
        $nativeLocation = trim($_POST['native_location'] ?? '');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $isAlive = isset($_POST['is_alive']) ? 1 : 0;

        $parentId = (int)($_POST['parent_id'] ?? 0);
        $parentType = $_POST['parent_type'] ?? '';
        $birthOrder = trim($_POST['birth_order'] ?? '');

        if ($fullName === '' || $branchId <= 0) {
            $_SESSION['flash_error'] = 'Full name and branch are required.';
            header('Location: /index.php?route=admin-person-add');
            exit;
        }

        $allowedGenders = ['male','female','other','unknown'];
        if (!in_array($gender, $allowedGenders, true)) {
            $gender = 'unknown';
        }

        $allowedParentTypes = ['father','mother','adoptive','step'];
        if ($parentId > 0 && !in_array($parentType, $allowedParentTypes, true)) {
            $_SESSION['flash_error'] = 'Invalid parent type.';
            header('Location: /index.php?route=admin-person-add');
            exit;
        }

        $dob = $dateOfBirth !== '' ? $dateOfBirth : null;
        $by = $birthYear !== '' ? (int)$birthYear : null;
        $dod = $dateOfDeath !== '' ? $dateOfDeath : null;
        $bo = $birthOrder !== '' ? (int)$birthOrder : null;

        $createdByPersonId = $_SESSION['user']['person_id'] ?? null;

        try {
            $this->db->beginTransaction();

            $lineagePath = '/';
            $depthLevel = 0;
            $rootId = null;

            if ($parentId > 0) {
                $pStmt = $this->db->prepare('SELECT person_id, lineage_path, depth_level, root_id FROM persons WHERE person_id = :id');
                $pStmt->execute([':id' => $parentId]);
                $parent = $pStmt->fetch();
                if (!$parent) {
                    throw new RuntimeException('Parent not found.');
                }
                $depthLevel = (int)$parent['depth_level'] + 1;
                $rootId = (int)$parent['root_id'];
                $lineagePath = $parent['lineage_path'];
            }

            $insert = $this->db->prepare(
                'INSERT INTO persons (
                    full_name, gender, date_of_birth, birth_year, date_of_death,
                    blood_group, occupation, mobile, email, address, current_location, native_location,
                    lineage_path, depth_level, root_id, branch_id, is_alive, created_by
                 ) VALUES (
                    :full_name, :gender, :date_of_birth, :birth_year, :date_of_death,
                    :blood_group, :occupation, :mobile, :email, :address, :current_location, :native_location,
                    :lineage_path, :depth_level, :root_id, :branch_id, :is_alive, :created_by
                 )'
            );

            $insert->execute([
                ':full_name' => $fullName,
                ':gender' => $gender,
                ':date_of_birth' => $dob,
                ':birth_year' => $by,
                ':date_of_death' => $dod,
                ':blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
                ':occupation' => $occupation !== '' ? $occupation : null,
                ':mobile' => $mobile !== '' ? $mobile : null,
                ':email' => $email !== '' ? $email : null,
                ':address' => $address !== '' ? $address : null,
                ':current_location' => $currentLocation !== '' ? $currentLocation : null,
                ':native_location' => $nativeLocation !== '' ? $nativeLocation : null,
                ':lineage_path' => $lineagePath,
                ':depth_level' => $depthLevel,
                ':root_id' => $rootId,
                ':branch_id' => $branchId,
                ':is_alive' => $isAlive,
                ':created_by' => $createdByPersonId,
            ]);

            $newId = (int)$this->db->lastInsertId();

            if ($parentId > 0) {
                $newPath = $lineagePath . $newId . '/';
            } else {
                $newPath = '/' . $newId . '/';
                $rootId = $newId;
            }

            $upd = $this->db->prepare('UPDATE persons SET lineage_path = :p, root_id = :r WHERE person_id = :id');
            $upd->execute([':p' => $newPath, ':r' => $rootId, ':id' => $newId]);

            if ($parentId > 0) {
                $pc = $this->db->prepare(
                    'INSERT INTO parent_child (parent_id, child_id, parent_type, birth_order)
                     VALUES (:parent_id, :child_id, :parent_type, :birth_order)'
                );
                $pc->execute([
                    ':parent_id' => $parentId,
                    ':child_id' => $newId,
                    ':parent_type' => $parentType,
                    ':birth_order' => $bo,
                ]);
            }

            if (!empty($_SESSION['user']['user_id'])) {
                $this->logs->log((int)$_SESSION['user']['user_id'], 'person_created', $newId);
            }

            $this->db->commit();
            $_SESSION['flash_success'] = 'Person added successfully.';
            header('Location: /index.php?route=admin-person-add');
            exit;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /index.php?route=admin-person-add');
            exit;
        }
    }

    public function showEdit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $person = $this->persons->getById($id);
        if (!$person) {
            http_response_code(404);
            echo 'Person not found';
            exit;
        }

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $branches = $this->branches->all();

        $parents = $this->db->prepare(
            'SELECT pc.parent_id, pc.parent_type, pc.birth_order, p.full_name
             FROM parent_child pc
             INNER JOIN persons p ON p.person_id = pc.parent_id
             WHERE pc.child_id = :id
             ORDER BY pc.parent_type'
        );
        $parents->execute([':id' => $id]);
        $parentRows = $parents->fetchAll();

        $marriages = $this->db->prepare(
            'SELECT m.marriage_id, m.person1_id, m.person2_id, m.marriage_date, m.divorce_date, m.status,
                    p1.full_name AS person1_name, p2.full_name AS person2_name
             FROM marriages m
             INNER JOIN persons p1 ON p1.person_id = m.person1_id
             INNER JOIN persons p2 ON p2.person_id = m.person2_id
             WHERE m.person1_id = :id1 OR m.person2_id = :id2
             ORDER BY m.marriage_date DESC'
        );
        $marriages->execute([':id1' => $id, ':id2' => $id]);
        $marriageRows = $marriages->fetchAll();

        $media = $this->db->prepare('SELECT * FROM media WHERE person_id = :id ORDER BY uploaded_at DESC');
        $media->execute([':id' => $id]);
        $mediaRows = $media->fetchAll();

        include __DIR__ . '/../views/admin/person_edit.php';
    }

    public function update(): void
    {
        $id = (int)($_POST['person_id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $person = $this->persons->getById($id);
        if (!$person) {
            http_response_code(404);
            echo 'Person not found';
            exit;
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? 'unknown';
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $birthYear = trim($_POST['birth_year'] ?? '');
        $dateOfDeath = trim($_POST['date_of_death'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $currentLocation = trim($_POST['current_location'] ?? '');
        $nativeLocation = trim($_POST['native_location'] ?? '');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $isAlive = isset($_POST['is_alive']) ? 1 : 0;

        if ($fullName === '' || $branchId <= 0) {
            $_SESSION['flash_error'] = 'Full name and branch are required.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $allowedGenders = ['male','female','other','unknown'];
        if (!in_array($gender, $allowedGenders, true)) {
            $gender = 'unknown';
        }

        $dob = $dateOfBirth !== '' ? $dateOfBirth : null;
        $by = $birthYear !== '' ? (int)$birthYear : null;
        $dod = $dateOfDeath !== '' ? $dateOfDeath : null;

        $stmt = $this->db->prepare(
            'UPDATE persons SET
                full_name = :full_name,
                gender = :gender,
                date_of_birth = :date_of_birth,
                birth_year = :birth_year,
                date_of_death = :date_of_death,
                blood_group = :blood_group,
                occupation = :occupation,
                mobile = :mobile,
                email = :email,
                address = :address,
                current_location = :current_location,
                native_location = :native_location,
                branch_id = :branch_id,
                is_alive = :is_alive
             WHERE person_id = :id'
        );

        $stmt->execute([
            ':full_name' => $fullName,
            ':gender' => $gender,
            ':date_of_birth' => $dob,
            ':birth_year' => $by,
            ':date_of_death' => $dod,
            ':blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
            ':occupation' => $occupation !== '' ? $occupation : null,
            ':mobile' => $mobile !== '' ? $mobile : null,
            ':email' => $email !== '' ? $email : null,
            ':address' => $address !== '' ? $address : null,
            ':current_location' => $currentLocation !== '' ? $currentLocation : null,
            ':native_location' => $nativeLocation !== '' ? $nativeLocation : null,
            ':branch_id' => $branchId,
            ':is_alive' => $isAlive,
            ':id' => $id,
        ]);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'person_updated', $id);
        }

        $_SESSION['flash_success'] = 'Person updated.';
        header('Location: /index.php?route=admin-person-edit&id=' . $id);
        exit;
    }

    public function addParent(): void
    {
        $id = (int)($_POST['child_id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $parentId = (int)($_POST['parent_id'] ?? 0);
        $parentType = $_POST['parent_type'] ?? '';
        $birthOrder = trim($_POST['birth_order'] ?? '');
        $bo = $birthOrder !== '' ? (int)$birthOrder : null;

        $allowedParentTypes = ['father','mother','adoptive','step'];
        if ($id <= 0 || $parentId <= 0 || !in_array($parentType, $allowedParentTypes, true)) {
            $_SESSION['flash_error'] = 'Invalid parent data.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO parent_child (parent_id, child_id, parent_type, birth_order)
             VALUES (:parent_id, :child_id, :parent_type, :birth_order)'
        );
        $stmt->execute([
            ':parent_id' => $parentId,
            ':child_id' => $id,
            ':parent_type' => $parentType,
            ':birth_order' => $bo,
        ]);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'parent_assigned', $id);
        }

        $_SESSION['flash_success'] = 'Parent assigned.';
        header('Location: /index.php?route=admin-person-edit&id=' . $id);
        exit;
    }

    public function addMarriage(): void
    {
        $id = (int)($_POST['person_id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $spouseId = (int)($_POST['spouse_id'] ?? 0);
        $marriageDate = trim($_POST['marriage_date'] ?? '');
        $divorceDate = trim($_POST['divorce_date'] ?? '');
        $status = $_POST['status'] ?? 'married';
        $allowedStatus = ['married','divorced','widowed'];

        if ($id <= 0 || $spouseId <= 0 || !in_array($status, $allowedStatus, true)) {
            $_SESSION['flash_error'] = 'Invalid marriage data.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO marriages (person1_id, person2_id, marriage_date, divorce_date, status)
             VALUES (:p1, :p2, :md, :dd, :status)'
        );
        $stmt->execute([
            ':p1' => $id,
            ':p2' => $spouseId,
            ':md' => $marriageDate !== '' ? $marriageDate : null,
            ':dd' => $divorceDate !== '' ? $divorceDate : null,
            ':status' => $status,
        ]);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'marriage_created', $id);
        }

        $_SESSION['flash_success'] = 'Marriage added.';
        header('Location: /index.php?route=admin-person-edit&id=' . $id);
        exit;
    }

    public function uploadMedia(): void
    {
        $id = (int)($_POST['person_id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        if ($id <= 0 || empty($_FILES['media_file']['name'])) {
            $_SESSION['flash_error'] = 'No file selected.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $file = $_FILES['media_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Upload failed.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            $_SESSION['flash_error'] = 'Invalid file type.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $ext = $allowed[$mime];
        $mediaType = ($mime === 'application/pdf') ? 'document' : 'photo';

        $baseDir = __DIR__ . '/../uploads/people/' . $id;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $name = uniqid('media_', true) . '.' . $ext;
        $target = $baseDir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $_SESSION['flash_error'] = 'Failed to save file.';
            header('Location: /index.php?route=admin-person-edit&id=' . $id);
            exit;
        }

        $relativePath = '/uploads/people/' . $id . '/' . $name;
        $stmt = $this->db->prepare(
            'INSERT INTO media (person_id, file_path, media_type, uploaded_by)
             VALUES (:person_id, :file_path, :media_type, :uploaded_by)'
        );
        $stmt->execute([
            ':person_id' => $id,
            ':file_path' => $relativePath,
            ':media_type' => $mediaType,
            ':uploaded_by' => (int)($_SESSION['user']['user_id'] ?? 0),
        ]);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'media_uploaded', $id);
        }

        $_SESSION['flash_success'] = 'Media uploaded.';
        header('Location: /index.php?route=admin-person-edit&id=' . $id);
        exit;
    }

    public function search(): void
    {
        require_role('admin');
        $q = trim($_GET['q'] ?? '');
        if ($q === '' || strlen($q) < 2) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            exit;
        }

        $stmt = $this->db->prepare(
            'SELECT person_id, full_name
             FROM persons
             WHERE full_name LIKE :q1 OR CAST(person_id AS CHAR) LIKE :q2
             ORDER BY full_name ASC
             LIMIT 20'
        );
        $stmt->execute([':q1' => '%' . $q . '%', ':q2' => '%' . $q . '%']);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['display_name'] = $this->formatPersonDisplay($row);
        }
        unset($row);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows);
        exit;
    }

    private function formatPersonDisplay(array $person): string
    {
        $personId = (int)$person['person_id'];
        $name = trim((string)($person['full_name'] ?? ''));
        if ($name === '') {
            return 'Unknown';
        }

        $fatherName = null;
        $fatherStmt = $this->db->prepare(
            'SELECT p.full_name
             FROM parent_child pc
             INNER JOIN persons p ON p.person_id = pc.parent_id
             WHERE pc.child_id = :id AND pc.parent_type = :ptype
             LIMIT 1'
        );
        $fatherStmt->execute([':id' => $personId, ':ptype' => 'father']);
        $frow = $fatherStmt->fetch();
        if ($frow && !empty($frow['full_name'])) {
            $fatherName = (string)$frow['full_name'];
        }

        $husbandName = null;
        if ((string)($person['gender'] ?? '') === 'female') {
            $husbandStmt = $this->db->prepare(
                'SELECT p.full_name
                 FROM marriages m
                 INNER JOIN persons p
                   ON p.person_id = CASE WHEN m.person1_id = :id1 THEN m.person2_id ELSE m.person1_id END
                 WHERE (m.person1_id = :id2 OR m.person2_id = :id3) AND p.gender = :g
                 ORDER BY m.marriage_id DESC
                 LIMIT 1'
            );
            $husbandStmt->execute([
                ':id1' => $personId,
                ':id2' => $personId,
                ':id3' => $personId,
                ':g' => 'male',
            ]);
            $hrow = $husbandStmt->fetch();
            if ($hrow && !empty($hrow['full_name'])) {
                $husbandName = (string)$hrow['full_name'];
            }
        }

        $parts = [$name];
        if ($fatherName !== null) {
            $parts[] = 'F: ' . $fatherName;
        }
        if ($husbandName !== null) {
            $parts[] = 'H: ' . $husbandName;
        }
        return implode(' | ', $parts);
    }
}
