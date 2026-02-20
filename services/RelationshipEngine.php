<?php
declare(strict_types=1);

final class RelationshipEngine
{
    private PDO $db;
    private array $people = [];
    private array $ancestorCache = [];
    private array $columns = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getRelationship(int $personAId, int $personBId): array
    {
        $this->loadPeople();
        $a = $this->people[$personAId] ?? null;
        $b = $this->people[$personBId] ?? null;
        if (!$a || !$b) {
            return $this->result('No relationship', null, null, 0, 'Direct', null, null);
        }

        if ($personAId === $personBId) {
            return $this->result('Self', 0, 0, 0, 'Direct', null, null);
        }

        $direct = $this->resolveDirect($a, $b);
        if ($direct !== null) {
            return $direct;
        }

        $inLaw = $this->resolveInLaw($a, $b);
        if ($inLaw !== null) {
            return $inLaw;
        }

        $lca = $this->findLca((int)$a['person_id'], (int)$b['person_id']);
        if ($lca === null) {
            return $this->result('No relationship', null, null, 0, 'Direct', null, null);
        }

        $x = (int)$lca['depth_a'];
        $y = (int)$lca['depth_b'];
        $generationDifference = $y - $x;
        $side = $this->sideFromEdge((string)$lca['edge_a']);
        $lcaId = (int)$lca['id'];
        $lcaName = (string)($this->people[$lcaId]['full_name'] ?? '');

        if ($y === 0 && $x > 0) {
            return $this->result(
                $this->ancestorTitle($x, (string)$b['gender'], $side),
                1,
                null,
                $generationDifference,
                $side,
                $lcaId,
                $lcaName
            );
        }
        if ($x === 0 && $y > 0) {
            return $this->result(
                $this->descendantTitle($y, (string)$b['gender']),
                1,
                null,
                $generationDifference,
                $side
            );
        }
        if ($x === 1 && $y === 1) {
            return $this->result($this->gendered((string)$b['gender'], 'Brother', 'Sister', 'Sibling'), 1, null, 0, $side, $lcaId, $lcaName);
        }
        if ($x === 1 && $y === 2) {
            return $this->result($this->gendered((string)$b['gender'], 'Uncle', 'Aunt', 'Uncle/Aunt'), 2, null, 1, $side, $lcaId, $lcaName);
        }
        if ($x === 2 && $y === 1) {
            return $this->result($this->gendered((string)$b['gender'], 'Nephew', 'Niece', 'Nephew/Niece'), 2, null, -1, $side, $lcaId, $lcaName);
        }

        $cousinLevel = min($x, $y) - 1;
        $removed = abs($x - $y);
        if ($cousinLevel >= 1) {
            $title = $this->ordinal($cousinLevel) . ' Cousin';
            if ($removed > 0) {
                $title .= ' ' . $this->removedText($removed);
            }
            return $this->result($title, max(1, $cousinLevel), $removed, $generationDifference, $side, $lcaId, $lcaName);
        }

        return $this->result('Relative', null, null, $generationDifference, $side, $lcaId, $lcaName);
    }

    public function getRelationshipMapForRoot(int $rootId, array $personIds): array
    {
        $out = [];
        foreach ($personIds as $pid) {
            $r = $this->getRelationship($rootId, (int)$pid);
            $out[(int)$pid] = [
                'id' => (int)$pid,
                'name' => (string)($this->people[(int)$pid]['full_name'] ?? ''),
                'relationship' => $r['title'],
                'generation_distance' => $r['generation_difference'],
                'cousin_level' => $r['cousin_level'],
                'removed' => $r['removed'],
                'side' => $r['side'],
            ];
        }
        return $out;
    }

    private function resolveDirect(array $a, array $b): ?array
    {
        $aid = (int)$a['person_id'];
        $bid = (int)$b['person_id'];

        if ((int)$a['father_id'] === $bid) {
            return $this->result('Father', 1, null, -1, 'Paternal');
        }
        if ((int)$a['mother_id'] === $bid) {
            return $this->result('Mother', 1, null, -1, 'Maternal');
        }
        if ((int)$b['father_id'] === $aid || (int)$b['mother_id'] === $aid) {
            return $this->result($this->gendered((string)$b['gender'], 'Son', 'Daughter', 'Child'), 1, null, 1, 'Direct');
        }
        if ($this->areMutualSpouses($aid, $bid)) {
            return $this->result($this->gendered((string)$b['gender'], 'Husband', 'Wife', 'Spouse'), 1, null, 0, 'Direct');
        }
        if ($this->areSiblings($aid, $bid)) {
            return $this->result($this->gendered((string)$b['gender'], 'Brother', 'Sister', 'Sibling'), 1, null, 0, 'Direct');
        }
        return null;
    }

    private function resolveInLaw(array $a, array $b): ?array
    {
        $aid = (int)$a['person_id'];
        $bid = (int)$b['person_id'];
        $spouseId = (int)$a['spouse_id'];
        if ($spouseId > 0 && $this->areMutualSpouses($aid, $spouseId)) {
            $spouse = $this->people[$spouseId] ?? null;
            if ($spouse) {
                if ((int)$spouse['father_id'] === $bid) {
                    return $this->result('Father-in-law', 1, null, -1, 'In-Law');
                }
                if ((int)$spouse['mother_id'] === $bid) {
                    return $this->result('Mother-in-law', 1, null, -1, 'In-Law');
                }
                if ($this->areSiblings($spouseId, $bid)) {
                    return $this->result($this->gendered((string)$b['gender'], 'Brother-in-law', 'Sister-in-law', 'Sibling-in-law'), 2, null, 0, 'In-Law');
                }
            }
        }

        if ($this->areSiblings($aid, $bid)) {
            return null;
        }

        $bSpouseId = (int)$b['spouse_id'];
        if ($bSpouseId > 0 && $this->areMutualSpouses($bid, $bSpouseId)) {
            if ($this->areSiblings($aid, $bSpouseId)) {
                return $this->result($this->gendered((string)$b['gender'], 'Brother-in-law', 'Sister-in-law', 'Sibling-in-law'), 2, null, 0, 'In-Law');
            }
            if ((int)$a['father_id'] === $bSpouseId || (int)$a['mother_id'] === $bSpouseId) {
                return $this->result($this->gendered((string)$b['gender'], 'Son-in-law', 'Daughter-in-law', 'Child-in-law'), 2, null, 1, 'In-Law');
            }
        }

        return null;
    }

    private function findLca(int $aId, int $bId): ?array
    {
        $aAnc = $this->ancestorMap($aId);
        $bAnc = $this->ancestorMap($bId);
        $common = array_intersect(array_keys($aAnc), array_keys($bAnc));
        if (empty($common)) {
            return null;
        }

        $best = null;
        foreach ($common as $id) {
            $x = (int)$aAnc[$id]['depth'];
            $y = (int)$bAnc[$id]['depth'];
            $score = $x + $y;
            if ($best === null || $score < $best['score'] || ($score === $best['score'] && max($x, $y) < max($best['depth_a'], $best['depth_b']))) {
                $best = [
                    'id' => (int)$id,
                    'depth_a' => $x,
                    'depth_b' => $y,
                    'edge_a' => (string)$aAnc[$id]['first_edge'],
                    'score' => $score,
                ];
            }
        }
        return $best;
    }

    private function ancestorMap(int $personId): array
    {
        if (isset($this->ancestorCache[$personId])) {
            return $this->ancestorCache[$personId];
        }
        $out = [
            $personId => ['depth' => 0, 'first_edge' => 'direct'],
        ];
        $queue = [[$personId, 0, 'direct']];
        while (!empty($queue)) {
            [$id, $depth, $firstEdge] = array_shift($queue);
            if ($depth >= 6) {
                continue;
            }
            $p = $this->people[$id] ?? null;
            if (!$p) {
                continue;
            }
            $parents = [
                ['id' => (int)$p['father_id'], 'edge' => 'father'],
                ['id' => (int)$p['mother_id'], 'edge' => 'mother'],
            ];
            foreach ($parents as $entry) {
                $pid = (int)$entry['id'];
                if ($pid <= 0 || $pid === $id) {
                    continue;
                }
                if (!isset($this->people[$pid])) {
                    continue;
                }
                $nextDepth = $depth + 1;
                $edge = $depth === 0 ? $entry['edge'] : $firstEdge;
                if (!isset($out[$pid]) || $nextDepth < (int)$out[$pid]['depth']) {
                    $out[$pid] = ['depth' => $nextDepth, 'first_edge' => $edge];
                    $queue[] = [$pid, $nextDepth, $edge];
                    continue;
                }
                if ($nextDepth === (int)$out[$pid]['depth'] && (string)$out[$pid]['first_edge'] !== $edge) {
                    $out[$pid]['first_edge'] = 'both';
                }
            }
        }
        $this->ancestorCache[$personId] = $out;
        return $out;
    }

    private function areSiblings(int $aId, int $bId): bool
    {
        $a = $this->people[$aId] ?? null;
        $b = $this->people[$bId] ?? null;
        if (!$a || !$b) {
            return false;
        }
        $fatherMatch = (int)$a['father_id'] > 0 && (int)$a['father_id'] === (int)$b['father_id'];
        $motherMatch = (int)$a['mother_id'] > 0 && (int)$a['mother_id'] === (int)$b['mother_id'];
        return $fatherMatch || $motherMatch;
    }

    private function areMutualSpouses(int $aId, int $bId): bool
    {
        $a = $this->people[$aId] ?? null;
        $b = $this->people[$bId] ?? null;
        if (!$a || !$b) {
            return false;
        }
        return (int)$a['spouse_id'] === $bId && (int)$b['spouse_id'] === $aId;
    }

    private function ancestorTitle(int $distance, string $gender, string $side): string
    {
        if ($distance === 1) {
            if ($side === 'Paternal' && $gender === 'male') return 'Father';
            if ($side === 'Maternal' && $gender === 'female') return 'Mother';
            return $this->gendered($gender, 'Father', 'Mother', 'Parent');
        }
        if ($distance === 2) {
            if ($side === 'Paternal') {
                return $this->gendered($gender, 'Paternal Grandfather', 'Paternal Grandmother', 'Paternal Grandparent');
            }
            if ($side === 'Maternal') {
                return $this->gendered($gender, 'Maternal Grandfather', 'Maternal Grandmother', 'Maternal Grandparent');
            }
            return $this->gendered($gender, 'Grandfather', 'Grandmother', 'Grandparent');
        }
        if ($distance === 3) {
            return $this->gendered($gender, 'Great Grandfather', 'Great Grandmother', 'Great Grandparent');
        }
        if ($distance === 4) {
            return $this->gendered($gender, 'Great Great Grandfather', 'Great Great Grandmother', 'Great Great Grandparent');
        }
        if ($distance === 5) {
            return $this->gendered($gender, 'Third Great Grandfather', 'Third Great Grandmother', 'Third Great Grandparent');
        }
        if ($distance === 6) {
            return $this->gendered($gender, 'Fourth Great Grandfather', 'Fourth Great Grandmother', 'Fourth Great Grandparent');
        }
        return $this->gendered($gender, 'Ancestor', 'Ancestor', 'Ancestor');
    }

    private function descendantTitle(int $distance, string $gender): string
    {
        if ($distance === 1) {
            return $this->gendered($gender, 'Son', 'Daughter', 'Child');
        }
        if ($distance === 2) {
            return $this->gendered($gender, 'Grandson', 'Granddaughter', 'Grandchild');
        }
        if ($distance === 3) {
            return $this->gendered($gender, 'Great Grandson', 'Great Granddaughter', 'Great Grandchild');
        }
        if ($distance === 4) {
            return $this->gendered($gender, 'Great Great Grandson', 'Great Great Granddaughter', 'Great Great Grandchild');
        }
        if ($distance === 5) {
            return $this->gendered($gender, 'Third Great Grandson', 'Third Great Granddaughter', 'Third Great Grandchild');
        }
        if ($distance === 6) {
            return $this->gendered($gender, 'Fourth Great Grandson', 'Fourth Great Granddaughter', 'Fourth Great Grandchild');
        }
        return $this->gendered($gender, 'Descendant', 'Descendant', 'Descendant');
    }

    private function sideFromEdge(string $edge): string
    {
        if ($edge === 'father') return 'Paternal';
        if ($edge === 'mother') return 'Maternal';
        if ($edge === 'both') return 'Both';
        return 'Direct';
    }

    private function gendered(string $gender, string $male, string $female, string $fallback): string
    {
        if ($gender === 'male') return $male;
        if ($gender === 'female') return $female;
        return $fallback;
    }

    private function result(
        string $title,
        ?int $degree,
        ?int $removed,
        int $generationDifference,
        string $side,
        ?int $lcaId = null,
        ?string $lcaName = null
    ): array {
        return [
            'title' => $title,
            'cousin_level' => $degree,
            'removed' => $removed,
            'generation_difference' => $generationDifference,
            'side' => $side,
            'lca_id' => $lcaId,
            'lca_name' => $lcaName,
        ];
    }

    private function loadPeople(): void
    {
        if (!empty($this->people)) {
            return;
        }
        $this->loadPersonColumns();
        $fatherExpr = isset($this->columns['father_id']) ? 'father_id' : 'NULL';
        $motherExpr = isset($this->columns['mother_id']) ? 'mother_id' : 'NULL';
        $spouseExpr = isset($this->columns['spouse_id']) ? 'spouse_id' : 'NULL';
        $stmt = $this->db->query(
            "SELECT person_id, full_name, gender,
                    {$fatherExpr} AS father_id,
                    {$motherExpr} AS mother_id,
                    {$spouseExpr} AS spouse_id
             FROM persons"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $id = (int)$row['person_id'];
            $fatherId = (int)($row['father_id'] ?? 0);
            $motherId = (int)($row['mother_id'] ?? 0);
            $spouseId = (int)($row['spouse_id'] ?? 0);
            if ($fatherId === $id) $fatherId = 0;
            if ($motherId === $id) $motherId = 0;
            if ($spouseId === $id) $spouseId = 0;
            $row['father_id'] = $fatherId;
            $row['mother_id'] = $motherId;
            $row['spouse_id'] = $spouseId;
            $this->people[$id] = $row;
        }
    }

    private function loadPersonColumns(): void
    {
        if (!empty($this->columns)) {
            return;
        }
        $stmt = $this->db->query('SHOW COLUMNS FROM persons');
        foreach ($stmt->fetchAll() as $row) {
            $field = strtolower((string)($row['Field'] ?? ''));
            if ($field !== '') {
                $this->columns[$field] = true;
            }
        }
    }

    private function ordinal(int $n): string
    {
        $words = [
            1 => 'First',
            2 => 'Second',
            3 => 'Third',
            4 => 'Fourth',
            5 => 'Fifth',
            6 => 'Sixth',
            7 => 'Seventh',
            8 => 'Eighth',
            9 => 'Ninth',
            10 => 'Tenth',
        ];
        if (isset($words[$n])) {
            return $words[$n];
        }
        return $n . 'th';
    }

    private function removedText(int $n): string
    {
        if ($n === 1) return 'Once Removed';
        if ($n === 2) return 'Twice Removed';
        if ($n === 3) return 'Thrice Removed';
        return $n . ' Times Removed';
    }
}

