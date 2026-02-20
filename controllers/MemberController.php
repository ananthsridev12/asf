<?php
declare(strict_types=1);

// controllers/MemberController.php

final class MemberController
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

    public function dashboard(): void
    {
        include __DIR__ . '/../views/member/dashboard.php';
    }

    public function profile(): void
    {
        $user = $_SESSION['user'] ?? [];
        $personId = (int)($user['person_id'] ?? 0);
        $person = $personId > 0 ? $this->persons->getById($personId) : null;

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $branches = $this->branches->all();

        include __DIR__ . '/../views/member/profile.php';
    }

    public function saveProfile(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member-profile');
            exit;
        }

        $user = $_SESSION['user'] ?? [];
        $userId = (int)($user['user_id'] ?? 0);
        $personId = (int)($user['person_id'] ?? 0);

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
            header('Location: /index.php?route=member-profile');
            exit;
        }

        $allowedGenders = ['male','female','other','unknown'];
        if (!in_array($gender, $allowedGenders, true)) {
            $gender = 'unknown';
        }

        $dob = $dateOfBirth !== '' ? $dateOfBirth : null;
        $by = $birthYear !== '' ? (int)$birthYear : null;
        $dod = $dateOfDeath !== '' ? $dateOfDeath : null;

        if ($personId > 0) {
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
                ':id' => $personId,
            ]);

            if ($userId > 0) {
                $this->logs->log($userId, 'member_profile_updated', $personId);
            }
        } else {
            $this->db->beginTransaction();
            try {
                $stmt = $this->db->prepare(
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
                    ':lineage_path' => '/',
                    ':depth_level' => 0,
                    ':root_id' => null,
                    ':branch_id' => $branchId,
                    ':is_alive' => $isAlive,
                    ':created_by' => null,
                ]);

                $newId = (int)$this->db->lastInsertId();
                $newPath = '/' . $newId . '/';

                $upd = $this->db->prepare('UPDATE persons SET lineage_path = :p, root_id = :r WHERE person_id = :id');
                $upd->execute([':p' => $newPath, ':r' => $newId, ':id' => $newId]);

                $link = $this->db->prepare('UPDATE users SET person_id = :pid WHERE user_id = :uid');
                $link->execute([':pid' => $newId, ':uid' => $userId]);

                $_SESSION['user']['person_id'] = $newId;

                if ($userId > 0) {
                    $this->logs->log($userId, 'member_profile_created', $newId);
                }

                $this->db->commit();
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: /index.php?route=member-profile');
                exit;
            }
        }

        $_SESSION['flash_success'] = 'Profile saved.';
        header('Location: /index.php?route=member-profile');
        exit;
    }

    public function familyList(): void
    {
        $basePersonId = (int)($_SESSION['user']['person_id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $total = $this->persons->countAll();
        $items = $this->persons->listPaginated($page, $perPage);
        $pages = (int)ceil($total / $perPage);
        $relations = [];
        $branchRows = $this->branches->all();
        $branchMap = [];
        foreach ($branchRows as $b) {
            $branchMap[(int)$b['branch_id']] = (string)$b['branch_name'];
        }

        foreach ($items as &$item) {
            $item['display_name'] = $this->formatPersonDisplay($item);
            $item['dob_display'] = $this->formatDateForView($item['date_of_birth'] ?? null);
            $age = $this->calculateAge($item);
            $item['age_display'] = $age !== null ? (string)$age : '';
            $item['branch_label'] = $branchMap[(int)$item['branch_id']] ?? ('Line #' . (int)$item['branch_id']);
        }
        unset($item);

        if ($basePersonId > 0) {
            $base = $this->persons->getById($basePersonId);
            if ($base) {
                foreach ($items as $item) {
                    $label = $this->getExplicitRelationship($base, $item);
                    if ($label === 'No direct relationship') {
                        $p1 = array_map('intval', array_filter(explode('/', trim((string)$base['lineage_path'], '/'))));
                        $p2 = array_map('intval', array_filter(explode('/', trim((string)$item['lineage_path'], '/'))));
                        $len = min(count($p1), count($p2));
                        $idx = -1;
                        for ($i = 0; $i < $len; $i++) {
                            if ($p1[$i] === $p2[$i]) {
                                $idx = $i;
                            } else {
                                break;
                            }
                        }
                        if ($idx >= 0) {
                            $gen1 = count($p1) - $idx - 1;
                            $gen2 = count($p2) - $idx - 1;
                            $lineage = $this->describeLineageRelationship($gen1, $gen2, (string)($item['gender'] ?? 'unknown'));
                            if ($lineage !== null) {
                                $label = ucfirst($lineage);
                            }
                        }
                    }
                    $relations[(int)$item['person_id']] = $label;
                }
            }
        }

        include __DIR__ . '/../views/member/family_list.php';
    }

    public function showAddFamilyMember(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        include __DIR__ . '/../views/member/person_add.php';
    }

    public function showEditFamilyMember(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        [$basePerson, $person] = $this->assertMemberCanManagePerson($id);

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $branches = $this->branches->all();

        $parentsStmt = $this->db->prepare(
            'SELECT pc.parent_id, pc.parent_type, pc.birth_order, p.full_name
             FROM parent_child pc
             INNER JOIN persons p ON p.person_id = pc.parent_id
             WHERE pc.child_id = :id
             ORDER BY pc.parent_type'
        );
        $parentsStmt->execute([':id' => $id]);
        $parentRows = $parentsStmt->fetchAll();

        include __DIR__ . '/../views/member/person_edit.php';
    }

    public function createFamilyMember(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member-person-add');
            exit;
        }

        $userId = (int)($_SESSION['user']['user_id'] ?? 0);
        $basePersonId = (int)($_SESSION['user']['person_id'] ?? 0);
        $basePerson = $basePersonId > 0 ? $this->persons->getById($basePersonId) : null;
        if (!$basePerson) {
            $_SESSION['flash_error'] = 'Complete your profile first before adding family members.';
            header('Location: /index.php?route=member-profile');
            exit;
        }

        $existingPersonId = (int)($_POST['existing_person_id'] ?? 0);
        $referencePersonId = (int)($_POST['reference_person_id'] ?? 0);
        $parentPersonId = (int)($_POST['parent_person_id'] ?? 0);
        $parentLinkType = (string)($_POST['parent_link_type'] ?? 'father');
        $fullName = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? 'unknown';
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $birthYear = trim($_POST['birth_year'] ?? '');
        $currentLocation = trim($_POST['current_location'] ?? '');
        $nativeLocation = trim($_POST['native_location'] ?? '');
        $isAlive = isset($_POST['is_alive']) ? 1 : 0;
        $relationType = $_POST['relation_type'] ?? 'none';
        $parentType = $_POST['parent_type'] ?? '';
        $birthOrder = trim($_POST['birth_order'] ?? '');

        $allowedGenders = ['male','female','other','unknown'];
        if (!in_array($gender, $allowedGenders, true)) {
            $gender = 'unknown';
        }

        $allowedRelationTypes = ['none', 'child', 'spouse', 'father', 'mother', 'brother', 'sister', 'grandfather', 'grandmother'];
        if (!in_array($relationType, $allowedRelationTypes, true)) {
            $relationType = 'none';
        }

        $allowedParentTypes = ['father','mother','adoptive','step'];
        if ($relationType === 'child' && !in_array($parentType, $allowedParentTypes, true)) {
            $_SESSION['flash_error'] = 'Select valid parent type for child relation.';
            header('Location: /index.php?route=member-person-add');
            exit;
        }
        if ($parentPersonId > 0 && !in_array($parentLinkType, $allowedParentTypes, true)) {
            $_SESSION['flash_error'] = 'Invalid parent link type.';
            header('Location: /index.php?route=member-person-add');
            exit;
        }

        if ($existingPersonId <= 0 && $fullName === '') {
            $_SESSION['flash_error'] = 'Full name is required when not selecting an existing person.';
            header('Location: /index.php?route=member-person-add');
            exit;
        }

        $dob = $dateOfBirth !== '' ? $dateOfBirth : null;
        $by = $birthYear !== '' ? (int)$birthYear : null;
        $bo = $birthOrder !== '' ? (int)$birthOrder : null;

        try {
            $this->db->beginTransaction();

            $anchorPerson = $basePerson;
            if ($referencePersonId > 0) {
                $ref = $this->persons->getById($referencePersonId);
                if (!$ref) {
                    throw new RuntimeException('Selected reference person not found.');
                }
                if ((int)$ref['branch_id'] !== (int)$basePerson['branch_id']) {
                    throw new RuntimeException('Reference person must be in your family line.');
                }
                $anchorPerson = $ref;
            }

            if ($existingPersonId > 0) {
                $target = $this->persons->getById($existingPersonId);
                if (!$target) {
                    throw new RuntimeException('Selected existing person not found.');
                }
                if ((int)$target['branch_id'] !== (int)$basePerson['branch_id']) {
                    throw new RuntimeException('Selected person must be in your family line.');
                }
                $targetPersonId = $existingPersonId;
            } else {
                $dup = $this->db->prepare(
                    'SELECT person_id, full_name
                     FROM persons
                     WHERE full_name = :name
                       AND (
                            (:dob IS NOT NULL AND date_of_birth = :dob2)
                            OR (:by IS NOT NULL AND birth_year = :by2)
                           )
                     LIMIT 5'
                );
                $dup->execute([
                    ':name' => $fullName,
                    ':dob' => $dob,
                    ':dob2' => $dob,
                    ':by' => $by,
                    ':by2' => $by,
                ]);
                $dups = $dup->fetchAll();
                if (!empty($dups)) {
                    $ids = array_map(static fn($r) => (string)$r['person_id'], $dups);
                    throw new RuntimeException('Possible existing person found. Use search and select existing ID(s): ' . implode(', ', $ids));
                }

                $targetPersonId = $this->createMemberOwnedPerson(
                    $fullName,
                    $gender,
                    $dob,
                    $by,
                    $isAlive,
                    $currentLocation !== '' ? $currentLocation : null,
                    $nativeLocation !== '' ? $nativeLocation : null,
                    (int)$basePerson['branch_id'],
                    $basePersonId
                );
            }

            $this->applyMemberRelationship(
                $relationType,
                $anchorPerson,
                $targetPersonId,
                $parentType,
                $bo
            );

            if ($parentPersonId > 0) {
                if ($parentPersonId === $targetPersonId) {
                    throw new RuntimeException('Parent and child cannot be the same person.');
                }
                $parentPerson = $this->persons->getById($parentPersonId);
                if (!$parentPerson) {
                    throw new RuntimeException('Selected parent not found.');
                }
                if ((int)$parentPerson['branch_id'] !== (int)$basePerson['branch_id']) {
                    throw new RuntimeException('Selected parent must be in your family line.');
                }
                $this->upsertParentChild($parentPersonId, $targetPersonId, $parentLinkType, null);
                $this->rebuildSubtreeForChild($targetPersonId, $parentPersonId);
            }

            if ($userId > 0) {
                $this->logs->log($userId, 'member_added_family_person', $targetPersonId);
            }

            $this->db->commit();
            $_SESSION['flash_success'] = 'Family member added successfully.';
            header('Location: /index.php?route=member-person-add');
            exit;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /index.php?route=member-person-add');
            exit;
        }
    }

    public function updateFamilyMember(): void
    {
        $id = (int)($_POST['person_id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member-person-edit&id=' . $id);
            exit;
        }

        [, $person] = $this->assertMemberCanManagePerson($id);

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
        $isAlive = isset($_POST['is_alive']) ? 1 : 0;

        if ($fullName === '') {
            $_SESSION['flash_error'] = 'Full name is required.';
            header('Location: /index.php?route=member-person-edit&id=' . $id);
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
            ':is_alive' => $isAlive,
            ':id' => $id,
        ]);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'member_family_person_updated', $id);
        }

        $_SESSION['flash_success'] = 'Person updated.';
        header('Location: /index.php?route=member-person-edit&id=' . $id);
        exit;
    }

    public function addParentFromEdit(): void
    {
        $childId = (int)($_POST['child_id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member-person-edit&id=' . $childId);
            exit;
        }

        [$basePerson, $person] = $this->assertMemberCanManagePerson($childId);

        $parentId = (int)($_POST['parent_id'] ?? 0);
        $parentType = (string)($_POST['parent_type'] ?? '');
        $birthOrder = trim($_POST['birth_order'] ?? '');
        $bo = $birthOrder !== '' ? (int)$birthOrder : null;

        $allowedParentTypes = ['father','mother','adoptive','step'];
        if ($parentId <= 0 || !in_array($parentType, $allowedParentTypes, true)) {
            $_SESSION['flash_error'] = 'Invalid parent data.';
            header('Location: /index.php?route=member-person-edit&id=' . $childId);
            exit;
        }
        if ($parentId === $childId) {
            $_SESSION['flash_error'] = 'Parent and child cannot be same person.';
            header('Location: /index.php?route=member-person-edit&id=' . $childId);
            exit;
        }

        $parent = $this->persons->getById($parentId);
        if (!$parent || (int)$parent['branch_id'] !== (int)$basePerson['branch_id']) {
            $_SESSION['flash_error'] = 'Selected parent must be in your family line.';
            header('Location: /index.php?route=member-person-edit&id=' . $childId);
            exit;
        }

        $this->upsertParentChild($parentId, $childId, $parentType, $bo);
        $this->rebuildSubtreeForChild($childId, $parentId);

        if (!empty($_SESSION['user']['user_id'])) {
            $this->logs->log((int)$_SESSION['user']['user_id'], 'member_parent_assigned', $childId);
        }

        $_SESSION['flash_success'] = 'Parent assigned.';
        header('Location: /index.php?route=member-person-edit&id=' . $childId);
        exit;
    }

    public function showAddMarriage(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        include __DIR__ . '/../views/member/marriage_add.php';
    }

    public function createMarriage(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member-marriage-add');
            exit;
        }

        $userId = (int)($_SESSION['user']['user_id'] ?? 0);
        $basePersonId = (int)($_SESSION['user']['person_id'] ?? 0);
        $basePerson = $basePersonId > 0 ? $this->persons->getById($basePersonId) : null;
        if (!$basePerson) {
            $_SESSION['flash_error'] = 'Complete your profile first before adding marriages.';
            header('Location: /index.php?route=member-profile');
            exit;
        }

        $person1Id = (int)($_POST['person1_id'] ?? 0);
        $person2Id = (int)($_POST['person2_id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'married');
        $marriageDate = trim($_POST['marriage_date'] ?? '');
        $divorceDate = trim($_POST['divorce_date'] ?? '');

        if ($person1Id <= 0 || $person2Id <= 0 || $person1Id === $person2Id) {
            $_SESSION['flash_error'] = 'Select two different persons.';
            header('Location: /index.php?route=member-marriage-add');
            exit;
        }

        $allowedStatus = ['married', 'divorced', 'widowed'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'married';
        }

        $person1 = $this->persons->getById($person1Id);
        $person2 = $this->persons->getById($person2Id);
        if (!$person1 || !$person2) {
            $_SESSION['flash_error'] = 'Selected person not found.';
            header('Location: /index.php?route=member-marriage-add');
            exit;
        }

        $baseBranch = (int)$basePerson['branch_id'];
        if ((int)$person1['branch_id'] !== $baseBranch || (int)$person2['branch_id'] !== $baseBranch) {
            $_SESSION['flash_error'] = 'You can add marriages only within your branch.';
            header('Location: /index.php?route=member-marriage-add');
            exit;
        }

        try {
            $this->db->beginTransaction();

            $check = $this->db->prepare(
                'SELECT 1
                 FROM marriages
                 WHERE (person1_id = :a1 AND person2_id = :b1) OR (person1_id = :a2 AND person2_id = :b2)
                 LIMIT 1'
            );
            $check->execute([
                ':a1' => $person1Id,
                ':b1' => $person2Id,
                ':a2' => $person2Id,
                ':b2' => $person1Id,
            ]);
            if ($check->fetch()) {
                throw new RuntimeException('Marriage already exists between selected persons.');
            }

            $ins = $this->db->prepare(
                'INSERT INTO marriages (person1_id, person2_id, marriage_date, divorce_date, status)
                 VALUES (:p1, :p2, :md, :dd, :status)'
            );
            $ins->execute([
                ':p1' => $person1Id,
                ':p2' => $person2Id,
                ':md' => $marriageDate !== '' ? $marriageDate : null,
                ':dd' => $divorceDate !== '' ? $divorceDate : null,
                ':status' => $status,
            ]);

            if ($userId > 0) {
                $this->logs->log($userId, 'member_added_marriage', $person1Id);
            }

            $this->db->commit();
            $_SESSION['flash_success'] = 'Marriage added successfully.';
            header('Location: /index.php?route=member-marriage-add');
            exit;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /index.php?route=member-marriage-add');
            exit;
        }
    }

    public function ancestors(): void
    {
        $personId = (int)($_GET['id'] ?? ($_SESSION['user']['person_id'] ?? 0));
        $person = $this->persons->getById($personId);
        if (!$person) {
            http_response_code(404);
            echo 'Person not found';
            exit;
        }

        $ancestors = [];
        $visited = [(int)$person['person_id'] => true];
        $queue = [
            [
                'person_id' => (int)$person['person_id'],
                'generation' => 0,
            ],
        ];
        $ancestorMap = [];

        $parentsStmt = $this->db->prepare(
            'SELECT pc.parent_id, pc.parent_type, p.full_name
             FROM parent_child pc
             INNER JOIN persons p ON p.person_id = pc.parent_id
             WHERE pc.child_id = :id
             ORDER BY CASE pc.parent_type
                        WHEN "father" THEN 1
                        WHEN "mother" THEN 2
                        WHEN "adoptive" THEN 3
                        WHEN "step" THEN 4
                        ELSE 9
                      END, p.full_name ASC'
        );

        while (!empty($queue)) {
            $node = array_shift($queue);
            $currId = (int)$node['person_id'];
            $currGen = (int)$node['generation'];

            $parentsStmt->execute([':id' => $currId]);
            $parents = $parentsStmt->fetchAll();

            foreach ($parents as $pr) {
                $pid = (int)$pr['parent_id'];
                $gen = $currGen + 1;
                if (!isset($ancestorMap[$pid]) || $gen < (int)$ancestorMap[$pid]['generation']) {
                    $ancestorMap[$pid] = [
                        'person_id' => $pid,
                        'full_name' => (string)$pr['full_name'],
                        'parent_type' => (string)$pr['parent_type'],
                        'generation' => $gen,
                    ];
                }
                if (!isset($visited[$pid])) {
                    $visited[$pid] = true;
                    $queue[] = [
                        'person_id' => $pid,
                        'generation' => $gen,
                    ];
                }
            }
        }

        $ancestors = array_values($ancestorMap);
        usort($ancestors, static function (array $a, array $b): int {
            $genCmp = ((int)$a['generation']) <=> ((int)$b['generation']);
            if ($genCmp !== 0) {
                return $genCmp;
            }
            $typeOrder = ['father' => 1, 'mother' => 2, 'adoptive' => 3, 'step' => 4];
            $ta = $typeOrder[(string)$a['parent_type']] ?? 9;
            $tb = $typeOrder[(string)$b['parent_type']] ?? 9;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return strcmp((string)$a['full_name'], (string)$b['full_name']);
        });

        include __DIR__ . '/../views/member/ancestors.php';
    }

    public function descendants(): void
    {
        $personId = (int)($_GET['id'] ?? ($_SESSION['user']['person_id'] ?? 0));
        $person = $this->persons->getById($personId);
        if (!$person) {
            http_response_code(404);
            echo 'Person not found';
            exit;
        }

        $stmt = $this->db->prepare(
            'SELECT person_id, full_name, depth_level, lineage_path, date_of_birth, birth_year
             FROM persons
             WHERE lineage_path LIKE :path AND person_id != :id
             ORDER BY depth_level ASC, lineage_path ASC'
        );
        $stmt->execute([
            ':path' => $person['lineage_path'] . '%',
            ':id' => $personId,
        ]);
        $descendants = $stmt->fetchAll();

        include __DIR__ . '/../views/member/descendants.php';
    }

    public function tree(): void
    {
        $basePersonId = (int)($_SESSION['user']['person_id'] ?? 0);
        $basePerson = $basePersonId > 0 ? $this->persons->getById($basePersonId) : null;
        if (!$basePerson) {
            http_response_code(404);
            echo 'Person not found';
            exit;
        }

        $rootId = (int)($basePerson['root_id'] ?? 0);
        if ($rootId <= 0) {
            $rootId = $basePersonId;
        }

        $peopleStmt = $this->db->prepare(
            'SELECT person_id, full_name, gender, depth_level, lineage_path, date_of_birth, birth_year
             FROM persons
             WHERE root_id = :root
             ORDER BY depth_level ASC, lineage_path ASC'
        );
        $peopleStmt->execute([':root' => $rootId]);
        $peopleRows = $peopleStmt->fetchAll();

        $peopleById = [];
        foreach ($peopleRows as $row) {
            $id = (int)$row['person_id'];
            $row['display_name'] = $this->formatPersonDisplay($row);
            $row['dob_display'] = $this->formatDateForView($row['date_of_birth'] ?? null);
            $age = $this->calculateAge($row);
            $row['age_display'] = $age !== null ? (string)$age : '';
            $peopleById[$id] = $row;
        }

        $childrenByParent = [];
        $personIds = array_keys($peopleById);
        if (!empty($personIds)) {
            $placeholders = implode(',', array_fill(0, count($personIds), '?'));
            $edgeStmt = $this->db->prepare(
                "SELECT parent_id, child_id
                 FROM parent_child
                 WHERE parent_id IN ($placeholders) AND child_id IN ($placeholders)"
            );
            $edgeStmt->execute(array_merge($personIds, $personIds));
            $edgeRows = $edgeStmt->fetchAll();

            foreach ($edgeRows as $edge) {
                $pid = (int)$edge['parent_id'];
                $cid = (int)$edge['child_id'];
                if (!isset($childrenByParent[$pid])) {
                    $childrenByParent[$pid] = [];
                }
                if (!in_array($cid, $childrenByParent[$pid], true)) {
                    $childrenByParent[$pid][] = $cid;
                }
            }
        }

        include __DIR__ . '/../views/member/tree.php';
    }

    public function relationship(): void
    {
        $personId = (int)($_GET['id1'] ?? ($_SESSION['user']['person_id'] ?? 0));
        $otherId = (int)($_GET['id2'] ?? 0);

        $person1 = $this->persons->getById($personId);
        $person2 = $otherId > 0 ? $this->persons->getById($otherId) : null;
        if ($person1) {
            $person1['display_name'] = $this->formatPersonDisplay($person1);
        }
        if ($person2) {
            $person2['display_name'] = $this->formatPersonDisplay($person2);
        }

        $result = null;
        if ($person1 && $person2) {
            // Direct spouse check from marriages table (both directions).
            $marriageStmt = $this->db->prepare(
                'SELECT marriage_id, status, marriage_date, divorce_date
                 FROM marriages
                 WHERE (person1_id = :id1 AND person2_id = :id2)
                    OR (person1_id = :id3 AND person2_id = :id4)
                 ORDER BY marriage_id DESC
                 LIMIT 1'
            );
            $marriageStmt->execute([
                ':id1' => $person1['person_id'],
                ':id2' => $person2['person_id'],
                ':id3' => $person2['person_id'],
                ':id4' => $person1['person_id'],
            ]);
            $marriage = $marriageStmt->fetch();

            if ($marriage) {
                $result = [
                    'type' => 'spouse',
                    'marriage' => $marriage,
                ];
            }

            if ($result !== null) {
                include __DIR__ . '/../views/member/relationship.php';
                return;
            }

            $explicit = $this->getExplicitRelationship($person1, $person2);
            if ($explicit !== 'No direct relationship') {
                $result = [
                    'type' => 'explicit',
                    'label' => $explicit,
                ];
                include __DIR__ . '/../views/member/relationship.php';
                return;
            }

            $p1 = array_map('intval', array_filter(explode('/', trim($person1['lineage_path'], '/'))));
            $p2 = array_map('intval', array_filter(explode('/', trim($person2['lineage_path'], '/'))));

            $len = min(count($p1), count($p2));
            $lca = null;
            $idx = 0;
            for ($i = 0; $i < $len; $i++) {
                if ($p1[$i] === $p2[$i]) {
                    $lca = $p1[$i];
                    $idx = $i;
                } else {
                    break;
                }
            }

            if ($lca) {
                $gen1 = count($p1) - $idx - 1;
                $gen2 = count($p2) - $idx - 1;
                $lcaPerson = $this->persons->getById($lca);
                $lineageLabel = $this->describeLineageRelationship($gen1, $gen2, (string)($person2['gender'] ?? 'unknown'));

                $result = [
                    'type' => 'lineage',
                    'lca' => $lcaPerson,
                    'gen1' => $gen1,
                    'gen2' => $gen2,
                    'label' => $lineageLabel,
                ];
            }
        }

        include __DIR__ . '/../views/member/relationship.php';
    }

    public function searchPeople(): void
    {
        require_role('member');
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

    private function assertMemberCanManagePerson(int $personId): array
    {
        $basePersonId = (int)($_SESSION['user']['person_id'] ?? 0);
        $basePerson = $basePersonId > 0 ? $this->persons->getById($basePersonId) : null;
        if (!$basePerson) {
            http_response_code(403);
            echo 'Complete your profile first.';
            exit;
        }

        $person = $this->persons->getById($personId);
        if (!$person) {
            http_response_code(404);
            echo 'Person not found';
            exit;
        }

        if ((int)$person['branch_id'] !== (int)$basePerson['branch_id']) {
            http_response_code(403);
            echo '403 - Forbidden';
            exit;
        }

        return [$basePerson, $person];
    }

    private function createMemberOwnedPerson(
        string $fullName,
        string $gender,
        ?string $dob,
        ?int $birthYear,
        int $isAlive,
        ?string $currentLocation,
        ?string $nativeLocation,
        int $branchId,
        int $createdByPersonId
    ): int {
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
            ':birth_year' => $birthYear,
            ':date_of_death' => null,
            ':blood_group' => null,
            ':occupation' => null,
            ':mobile' => null,
            ':email' => null,
            ':address' => null,
            ':current_location' => $currentLocation,
            ':native_location' => $nativeLocation,
            ':lineage_path' => '/',
            ':depth_level' => 0,
            ':root_id' => null,
            ':branch_id' => $branchId,
            ':is_alive' => $isAlive,
            ':created_by' => $createdByPersonId,
        ]);

        $newPersonId = (int)$this->db->lastInsertId();
        $upd = $this->db->prepare('UPDATE persons SET lineage_path = :p, root_id = :r WHERE person_id = :id');
        $upd->execute([':p' => '/' . $newPersonId . '/', ':r' => $newPersonId, ':id' => $newPersonId]);

        return $newPersonId;
    }

    private function applyMemberRelationship(
        string $relationType,
        array $basePerson,
        int $targetPersonId,
        string $parentType,
        ?int $birthOrder
    ): void {
        $basePersonId = (int)$basePerson['person_id'];
        if ($targetPersonId === $basePersonId) {
            return;
        }

        if ($relationType === 'child') {
            $ptype = $parentType;
            if ($ptype === '') {
                $ptype = ((string)$basePerson['gender'] === 'female') ? 'mother' : 'father';
            }
            $this->upsertParentChild($basePersonId, $targetPersonId, $ptype, $birthOrder);
            $this->rebuildSubtreeForChild($targetPersonId, $basePersonId);
            return;
        }

        if ($relationType === 'father' || $relationType === 'mother') {
            $this->upsertParentChild($targetPersonId, $basePersonId, $relationType, null);
            $this->rebuildSubtreeForChild($basePersonId, $targetPersonId);
            return;
        }

        if ($relationType === 'grandfather' || $relationType === 'grandmother') {
            $parent = $this->fetchOneParentOf($basePersonId);
            if (!$parent) {
                throw new RuntimeException('Add father/mother first before adding grandparent.');
            }
            $ptype = $relationType === 'grandfather' ? 'father' : 'mother';
            $this->upsertParentChild($targetPersonId, (int)$parent['parent_id'], $ptype, null);
            $this->rebuildSubtreeForChild((int)$parent['parent_id'], $targetPersonId);
            return;
        }

        if ($relationType === 'brother' || $relationType === 'sister') {
            $parents = $this->fetchAllParentsOf($basePersonId);
            if (empty($parents)) {
                throw new RuntimeException('Add father/mother first before adding siblings.');
            }
            foreach ($parents as $p) {
                $this->upsertParentChild((int)$p['parent_id'], $targetPersonId, (string)$p['parent_type'], null);
            }
            $this->syncPathFromSibling($basePersonId, $targetPersonId);
            return;
        }

        if ($relationType === 'spouse') {
            $this->upsertMarriage($basePersonId, $targetPersonId);
            return;
        }
    }

    private function upsertParentChild(int $parentId, int $childId, string $parentType, ?int $birthOrder): void
    {
        $check = $this->db->prepare(
            'SELECT 1 FROM parent_child WHERE parent_id = :p AND child_id = :c AND parent_type = :t LIMIT 1'
        );
        $check->execute([':p' => $parentId, ':c' => $childId, ':t' => $parentType]);
        if ($check->fetch()) {
            return;
        }
        $ins = $this->db->prepare(
            'INSERT INTO parent_child (parent_id, child_id, parent_type, birth_order)
             VALUES (:parent_id, :child_id, :parent_type, :birth_order)'
        );
        $ins->execute([
            ':parent_id' => $parentId,
            ':child_id' => $childId,
            ':parent_type' => $parentType,
            ':birth_order' => $birthOrder,
        ]);
    }

    private function upsertMarriage(int $p1, int $p2): void
    {
        $check = $this->db->prepare(
            'SELECT 1
             FROM marriages
             WHERE (person1_id = :a1 AND person2_id = :b1) OR (person1_id = :a2 AND person2_id = :b2)
             LIMIT 1'
        );
        $check->execute([
            ':a1' => $p1,
            ':b1' => $p2,
            ':a2' => $p2,
            ':b2' => $p1,
        ]);
        if ($check->fetch()) {
            return;
        }
        $ins = $this->db->prepare(
            'INSERT INTO marriages (person1_id, person2_id, marriage_date, divorce_date, status)
             VALUES (:p1, :p2, :md, :dd, :status)'
        );
        $ins->execute([
            ':p1' => $p1,
            ':p2' => $p2,
            ':md' => null,
            ':dd' => null,
            ':status' => 'married',
        ]);
    }

    private function fetchOneParentOf(int $childId): ?array
    {
        $stmt = $this->db->prepare('SELECT parent_id, parent_type FROM parent_child WHERE child_id = :id LIMIT 1');
        $stmt->execute([':id' => $childId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function fetchAllParentsOf(int $childId): array
    {
        $stmt = $this->db->prepare('SELECT parent_id, parent_type FROM parent_child WHERE child_id = :id');
        $stmt->execute([':id' => $childId]);
        return $stmt->fetchAll();
    }

    private function rebuildSubtreeForChild(int $childId, int $parentId): void
    {
        $parent = $this->persons->getById($parentId);
        $child = $this->persons->getById($childId);
        if (!$parent || !$child) {
            return;
        }

        $newPath = rtrim((string)$parent['lineage_path'], '/') . '/' . $childId . '/';
        $newRoot = (int)$parent['root_id'];
        $newDepth = (int)$parent['depth_level'] + 1;
        $this->updatePersonPathRecursive($childId, $newPath, $newRoot, $newDepth);
    }

    private function syncPathFromSibling(int $siblingId, int $targetId): void
    {
        $sibling = $this->persons->getById($siblingId);
        if (!$sibling) {
            return;
        }
        $basePath = trim((string)$sibling['lineage_path'], '/');
        $parts = $basePath === '' ? [] : explode('/', $basePath);
        array_pop($parts);
        $prefix = '/' . implode('/', $parts);
        if ($prefix === '/') {
            $newPath = '/' . $targetId . '/';
            $newDepth = 0;
            $newRoot = $targetId;
        } else {
            $newPath = $prefix . '/' . $targetId . '/';
            $newDepth = max(0, (int)$sibling['depth_level']);
            $newRoot = (int)$sibling['root_id'];
        }
        $this->updatePersonPathRecursive($targetId, $newPath, $newRoot, $newDepth);
    }

    private function updatePersonPathRecursive(int $personId, string $path, int $rootId, int $depthLevel): void
    {
        $upd = $this->db->prepare(
            'UPDATE persons SET lineage_path = :p, root_id = :r, depth_level = :d WHERE person_id = :id'
        );
        $upd->execute([
            ':p' => $path,
            ':r' => $rootId,
            ':d' => $depthLevel,
            ':id' => $personId,
        ]);

        $childrenStmt = $this->db->prepare('SELECT child_id FROM parent_child WHERE parent_id = :id');
        $childrenStmt->execute([':id' => $personId]);
        $children = $childrenStmt->fetchAll();
        foreach ($children as $child) {
            $cid = (int)$child['child_id'];
            $this->updatePersonPathRecursive($cid, rtrim($path, '/') . '/' . $cid . '/', $rootId, $depthLevel + 1);
        }
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

    private function getExplicitRelationship(array $base, array $other): string
    {
        $baseId = (int)$base['person_id'];
        $otherId = (int)$other['person_id'];

        if ($baseId === $otherId) {
            return 'Self';
        }

        $spouseStmt = $this->db->prepare(
            'SELECT 1
             FROM marriages
             WHERE (person1_id = :a1 AND person2_id = :b1) OR (person1_id = :a2 AND person2_id = :b2)
             LIMIT 1'
        );
        $spouseStmt->execute([
            ':a1' => $baseId,
            ':b1' => $otherId,
            ':a2' => $otherId,
            ':b2' => $baseId,
        ]);
        if ($spouseStmt->fetch()) {
            return 'Spouse';
        }

        $parentOfBase = $this->db->prepare(
            'SELECT parent_type
             FROM parent_child
             WHERE parent_id = :parent_id AND child_id = :child_id
             LIMIT 1'
        );
        $parentOfBase->execute([':parent_id' => $otherId, ':child_id' => $baseId]);
        $row = $parentOfBase->fetch();
        if ($row) {
            $ptype = $row['parent_type'];
            if ($ptype === 'father') return 'Father';
            if ($ptype === 'mother') return 'Mother';
            if ($ptype === 'adoptive') return 'Adoptive Parent';
            if ($ptype === 'step') return 'Step Parent';
        }

        $childOfBase = $this->db->prepare(
            'SELECT birth_order
             FROM parent_child
             WHERE parent_id = :parent_id AND child_id = :child_id
             LIMIT 1'
        );
        $childOfBase->execute([':parent_id' => $baseId, ':child_id' => $otherId]);
        $childRow = $childOfBase->fetch();
        if ($childRow) {
            $gender = (string)($other['gender'] ?? 'unknown');
            $childLabel = $gender === 'male' ? 'son' : ($gender === 'female' ? 'daughter' : 'child');
            $birthOrder = $childRow['birth_order'];
            if ($birthOrder !== null) {
                return $this->ordinal((int)$birthOrder) . ' ' . $childLabel;
            }
            return ucfirst($childLabel);
        }

        // In-law check: other is parent of base person's spouse.
        $spouseIds = [];
        $spousesStmt = $this->db->prepare(
            'SELECT CASE WHEN person1_id = :id1 THEN person2_id ELSE person1_id END AS spouse_id
             FROM marriages
             WHERE person1_id = :id2 OR person2_id = :id3'
        );
        $spousesStmt->execute([
            ':id1' => $baseId,
            ':id2' => $baseId,
            ':id3' => $baseId,
        ]);
        $spouses = $spousesStmt->fetchAll();
        if (!empty($spouses)) {
            $spouseIds = array_map(static fn($r) => (int)$r['spouse_id'], $spouses);
            $placeholders = implode(',', array_fill(0, count($spouseIds), '?'));
            $inLawStmt = $this->db->prepare(
                "SELECT parent_type
                 FROM parent_child
                 WHERE parent_id = ? AND child_id IN ($placeholders)
                 LIMIT 1"
            );
            $params = array_merge([$otherId], $spouseIds);
            $inLawStmt->execute($params);
            $inLaw = $inLawStmt->fetch();
            if ($inLaw) {
                $ptype = (string)$inLaw['parent_type'];
                if ($ptype === 'father') return 'Father-in-law';
                if ($ptype === 'mother') return 'Mother-in-law';
                return 'Parent-in-law';
            }
        }

        // Reverse in-law check: base is parent of other person's spouse.
        $otherSpousesStmt = $this->db->prepare(
            'SELECT CASE WHEN person1_id = :id1 THEN person2_id ELSE person1_id END AS spouse_id
             FROM marriages
             WHERE person1_id = :id2 OR person2_id = :id3'
        );
        $otherSpousesStmt->execute([
            ':id1' => $otherId,
            ':id2' => $otherId,
            ':id3' => $otherId,
        ]);
        $otherSpouses = $otherSpousesStmt->fetchAll();
        if (!empty($otherSpouses)) {
            $otherSpouseIds = array_map(static fn($r) => (int)$r['spouse_id'], $otherSpouses);
            $placeholders = implode(',', array_fill(0, count($otherSpouseIds), '?'));
            $childInLawStmt = $this->db->prepare(
                "SELECT 1
                 FROM parent_child
                 WHERE parent_id = ? AND child_id IN ($placeholders)
                 LIMIT 1"
            );
            $params = array_merge([$baseId], $otherSpouseIds);
            $childInLawStmt->execute($params);
            if ($childInLawStmt->fetch()) {
                $gender = (string)($other['gender'] ?? 'unknown');
                if ($gender === 'male') return 'Son-in-law';
                if ($gender === 'female') return 'Daughter-in-law';
                return 'Child-in-law';
            }
        }

        // Sibling-in-law check 1: other is sibling of base person's spouse.
        if (!empty($spouseIds)) {
            $placeholders = implode(',', array_fill(0, count($spouseIds), '?'));
            $siblingInLawStmt = $this->db->prepare(
                "SELECT 1
                 FROM parent_child a
                 INNER JOIN parent_child b ON a.parent_id = b.parent_id
                 WHERE a.child_id IN ($placeholders) AND b.child_id = ?
                 LIMIT 1"
            );
            $params = array_merge($spouseIds, [$otherId]);
            $siblingInLawStmt->execute($params);
            if ($siblingInLawStmt->fetch() && !in_array($otherId, $spouseIds, true)) {
                $gender = (string)($other['gender'] ?? 'unknown');
                if ($gender === 'male') return 'Brother-in-law';
                if ($gender === 'female') return 'Sister-in-law';
                return 'Sibling-in-law';
            }
        }

        // Sibling-in-law check 2: other is spouse of base person's sibling.
        $baseSiblingIds = [];
        $baseSiblingsStmt = $this->db->prepare(
            'SELECT DISTINCT b.child_id
             FROM parent_child a
             INNER JOIN parent_child b ON a.parent_id = b.parent_id
             WHERE a.child_id = :base AND b.child_id <> :base2'
        );
        $baseSiblingsStmt->execute([':base' => $baseId, ':base2' => $baseId]);
        $baseSiblingRows = $baseSiblingsStmt->fetchAll();
        if (!empty($baseSiblingRows)) {
            $baseSiblingIds = array_map(static fn($r) => (int)$r['child_id'], $baseSiblingRows);
            $placeholders = implode(',', array_fill(0, count($baseSiblingIds), '?'));
            $siblingSpouseStmt = $this->db->prepare(
                "SELECT 1
                 FROM marriages
                 WHERE ((person1_id IN ($placeholders) AND person2_id = ?)
                     OR (person2_id IN ($placeholders) AND person1_id = ?))
                 LIMIT 1"
            );
            $params = array_merge($baseSiblingIds, [$otherId], $baseSiblingIds, [$otherId]);
            $siblingSpouseStmt->execute($params);
            if ($siblingSpouseStmt->fetch()) {
                $gender = (string)($other['gender'] ?? 'unknown');
                if ($gender === 'male') return 'Brother-in-law';
                if ($gender === 'female') return 'Sister-in-law';
                return 'Sibling-in-law';
            }
        }

        // If two people share child(ren), they're co-parents even if no marriage row exists.
        $coParentStmt = $this->db->prepare(
            'SELECT 1
             FROM parent_child a
             INNER JOIN parent_child b ON a.child_id = b.child_id
             WHERE a.parent_id = :a AND b.parent_id = :b
             LIMIT 1'
        );
        $coParentStmt->execute([':a' => $baseId, ':b' => $otherId]);
        if ($coParentStmt->fetch()) {
            return 'Co-parent';
        }

        $siblingStmt = $this->db->prepare(
            'SELECT 1
             FROM parent_child a
             INNER JOIN parent_child b ON a.parent_id = b.parent_id
             WHERE a.child_id = :a AND b.child_id = :b
             LIMIT 1'
        );
        $siblingStmt->execute([':a' => $baseId, ':b' => $otherId]);
        if ($siblingStmt->fetch()) {
            $gender = (string)($other['gender'] ?? 'unknown');
            if ($gender === 'male') return 'Brother';
            if ($gender === 'female') return 'Sister';
            return 'Sibling';
        }

        $basePath = trim((string)$base['lineage_path'], '/');
        $otherPath = trim((string)$other['lineage_path'], '/');
        $baseIds = $basePath === '' ? [] : array_map('intval', explode('/', $basePath));
        $otherIds = $otherPath === '' ? [] : array_map('intval', explode('/', $otherPath));

        if (!empty($baseIds) && !empty($otherIds)) {
            if (in_array($otherId, $baseIds, true)) {
                $distance = count($baseIds) - array_search($otherId, $baseIds, true) - 1;
                if ($distance === 1) {
                    return ((string)($other['gender'] ?? '') === 'female') ? 'Mother' : 'Father';
                }
                if ($distance === 2) {
                    return ((string)($other['gender'] ?? '') === 'female') ? 'Grandmother' : 'Grandfather';
                }
                return 'Ancestor';
            }
            if (in_array($baseId, $otherIds, true)) {
                $distance = count($otherIds) - array_search($baseId, $otherIds, true) - 1;
                if ($distance === 1) {
                    return ((string)($other['gender'] ?? '') === 'female') ? 'Daughter' : 'Son';
                }
                if ($distance === 2) {
                    return ((string)($other['gender'] ?? '') === 'female') ? 'Granddaughter' : 'Grandson';
                }
                return 'Descendant';
            }
        }

        return 'No direct relationship';
    }

    private function describeLineageRelationship(int $gen1, int $gen2, string $otherGender): ?string
    {
        if ($gen1 <= 0 || $gen2 <= 0) {
            return null;
        }

        if ($gen1 === 1 && $gen2 === 1) {
            if ($otherGender === 'male') return 'Brother';
            if ($otherGender === 'female') return 'Sister';
            return 'Sibling';
        }

        $minGen = min($gen1, $gen2);
        $maxGen = max($gen1, $gen2);

        if ($minGen >= 2) {
            $degree = $minGen - 1;
            $removed = $maxGen - $minGen;
            $label = $this->ordinal($degree) . ' cousin';
            if ($removed > 0) {
                $label .= ' ' . $this->removedText($removed);
            }
            return $label;
        }

        return null;
    }

    private function removedText(int $n): string
    {
        if ($n === 1) return 'once removed';
        if ($n === 2) return 'twice removed';
        if ($n === 3) return 'thrice removed';
        return $n . ' times removed';
    }

    private function formatDateForView(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return '';
        }
        try {
            $dt = new DateTimeImmutable($date);
            return $dt->format('d M Y');
        } catch (Throwable $e) {
            return '';
        }
    }

    private function calculateAge(array $person): ?int
    {
        $dobRaw = trim((string)($person['date_of_birth'] ?? ''));
        $dodRaw = trim((string)($person['date_of_death'] ?? ''));

        if ($dobRaw !== '') {
            try {
                $dob = new DateTimeImmutable($dobRaw);
                $end = $dodRaw !== '' ? new DateTimeImmutable($dodRaw) : new DateTimeImmutable('today');
                if ($end < $dob) {
                    return null;
                }
                return $dob->diff($end)->y;
            } catch (Throwable $e) {
                return null;
            }
        }

        $birthYear = (int)($person['birth_year'] ?? 0);
        if ($birthYear > 0) {
            $endYear = (int)date('Y');
            if ($dodRaw !== '') {
                try {
                    $endYear = (int)(new DateTimeImmutable($dodRaw))->format('Y');
                } catch (Throwable $e) {
                    $endYear = (int)date('Y');
                }
            }
            $age = $endYear - $birthYear;
            return $age >= 0 ? $age : null;
        }

        return null;
    }

    private function ordinal(int $n): string
    {
        $words = [
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth',
            5 => 'fifth',
            6 => 'sixth',
            7 => 'seventh',
            8 => 'eighth',
            9 => 'ninth',
            10 => 'tenth',
        ];
        if (isset($words[$n])) {
            return $words[$n];
        }

        $mod100 = $n % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $n . 'th';
        }
        $mod10 = $n % 10;
        if ($mod10 === 1) return $n . 'st';
        if ($mod10 === 2) return $n . 'nd';
        if ($mod10 === 3) return $n . 'rd';
        return $n . 'th';
    }
}
