<?php
declare(strict_types=1);

final class RelationshipEngine
{
    private PDO $db;
    private PersonModel $persons;

    public function __construct(PDO $db, PersonModel $persons)
    {
        $this->db = $db;
        $this->persons = $persons;
    }

    public function calculate(array $base, array $other): array
    {
        $baseId = (int)$base['person_id'];
        $otherId = (int)$other['person_id'];

        if ($baseId === $otherId) {
            return $this->result('Self', 'core', 'both', 0, 0, null, 0, 0);
        }

        if ($this->hasMarriage($baseId, $otherId)) {
            $label = ((string)($other['gender'] ?? '') === 'male') ? 'Husband' : (((string)($other['gender'] ?? '') === 'female') ? 'Wife' : 'Spouse');
            return $this->result($label, 'core', 'in_law', 1, 0, null, 0, 0);
        }

        $blood = $this->calculateBlood($base, $other, 6);
        if ($blood !== null) {
            return $blood;
        }

        // In-law via spouse relation.
        $spouseIds = $this->getSpouseIds($baseId);
        foreach ($spouseIds as $spouseId) {
            $spouse = $this->persons->getById($spouseId);
            if (!$spouse) {
                continue;
            }
            $spouseBlood = $this->calculateBlood($spouse, $other, 6);
            if ($spouseBlood === null) {
                continue;
            }
            $label = $this->toInLawLabel((string)$spouseBlood['label'], (string)($other['gender'] ?? 'unknown'));
            return $this->result(
                $label,
                'in_law',
                'in_law',
                (int)$spouseBlood['degree'],
                (int)$spouseBlood['generation_delta'],
                $spouseBlood['lca_person_id'] ?? null,
                (int)($spouseBlood['steps_from_person1'] ?? 0),
                (int)($spouseBlood['steps_from_person2'] ?? 0)
            );
        }

        return $this->result('No direct relationship', 'extended', 'both', 0, 0, null, 0, 0);
    }

    private function calculateBlood(array $base, array $other, int $maxDepth): ?array
    {
        $baseId = (int)$base['person_id'];
        $otherId = (int)$other['person_id'];
        $otherGender = (string)($other['gender'] ?? 'unknown');

        $baseMap = $this->buildAncestorMap($baseId, $maxDepth);
        $otherMap = $this->buildAncestorMap($otherId, $maxDepth);

        if (isset($baseMap[$otherId]) && (int)$baseMap[$otherId]['distance'] > 0) {
            $distance = (int)$baseMap[$otherId]['distance'];
            $side = (string)$baseMap[$otherId]['side'];
            $label = $this->ancestorDescendantLabel($distance, $otherGender, true, $side);
            return $this->result($label, 'ancestor', $side, $distance, -$distance, $otherId, $distance, 0);
        }

        if (isset($otherMap[$baseId]) && (int)$otherMap[$baseId]['distance'] > 0) {
            $distance = (int)$otherMap[$baseId]['distance'];
            $side = (string)$otherMap[$baseId]['side'];
            $label = $this->ancestorDescendantLabel($distance, $otherGender, false, $side);
            return $this->result($label, 'descendant', $side, $distance, $distance, $baseId, 0, $distance);
        }

        $common = array_values(array_intersect(array_keys($baseMap), array_keys($otherMap)));
        if (empty($common)) {
            return null;
        }

        $bestLca = null;
        $bestSum = PHP_INT_MAX;
        $bestMax = PHP_INT_MAX;
        foreach ($common as $ancestorId) {
            $d1 = (int)$baseMap[$ancestorId]['distance'];
            $d2 = (int)$otherMap[$ancestorId]['distance'];
            if ($d1 === 0 && $d2 === 0) {
                continue;
            }
            $sum = $d1 + $d2;
            $max = max($d1, $d2);
            if ($sum < $bestSum || ($sum === $bestSum && $max < $bestMax)) {
                $bestLca = (int)$ancestorId;
                $bestSum = $sum;
                $bestMax = $max;
            }
        }

        if ($bestLca === null) {
            return null;
        }

        $d1 = (int)$baseMap[$bestLca]['distance'];
        $d2 = (int)$otherMap[$bestLca]['distance'];
        $side = $this->mergeSide((string)$baseMap[$bestLca]['side'], (string)$otherMap[$bestLca]['side']);

        if ($d1 === 1 && $d2 === 1) {
            $label = $this->siblingLabel($base, $other);
            return $this->result($label, 'core', $side, 1, 0, $bestLca, $d1, $d2);
        }

        if ($d1 === 1 && $d2 >= 2) {
            $label = $this->uncleAuntLabel($d2, $otherGender, $side);
            return $this->result($label, 'extended', $side, $d2 - 1, 1 - $d2, $bestLca, $d1, $d2);
        }

        if ($d2 === 1 && $d1 >= 2) {
            $label = $this->nephewNieceLabel($d1, $otherGender, $side);
            return $this->result($label, 'extended', $side, $d1 - 1, $d1 - 1, $bestLca, $d1, $d2);
        }

        $minD = min($d1, $d2);
        if ($minD >= 2) {
            $degree = $minD - 1;
            $removed = abs($d1 - $d2);
            $label = ucfirst($this->ordinal($degree) . ' cousin');
            if ($removed > 0) {
                $label .= ' ' . $this->removedText($removed);
            }
            return $this->result($label, 'cousin', $side, $degree, $d1 - $d2, $bestLca, $d1, $d2);
        }

        return null;
    }

    private function buildAncestorMap(int $personId, int $maxDepth): array
    {
        $map = [
            $personId => ['distance' => 0, 'side' => 'both'],
        ];
        $queue = [
            ['id' => $personId, 'distance' => 0, 'side' => 'both'],
        ];
        $stmt = $this->db->prepare('SELECT parent_id, parent_type FROM parent_child WHERE child_id = :id');

        while (!empty($queue)) {
            $node = array_shift($queue);
            $id = (int)$node['id'];
            $distance = (int)$node['distance'];
            $side = (string)$node['side'];
            if ($distance >= $maxDepth) {
                continue;
            }

            $stmt->execute([':id' => $id]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $parentId = (int)$row['parent_id'];
                $parentType = (string)$row['parent_type'];
                $nextDistance = $distance + 1;
                $nextSide = $this->nextSide($side, $parentType, $distance);

                if (!isset($map[$parentId])) {
                    $map[$parentId] = ['distance' => $nextDistance, 'side' => $nextSide];
                    $queue[] = ['id' => $parentId, 'distance' => $nextDistance, 'side' => $nextSide];
                    continue;
                }

                $existingDistance = (int)$map[$parentId]['distance'];
                if ($nextDistance < $existingDistance) {
                    $map[$parentId] = ['distance' => $nextDistance, 'side' => $nextSide];
                    $queue[] = ['id' => $parentId, 'distance' => $nextDistance, 'side' => $nextSide];
                } elseif ($nextDistance === $existingDistance) {
                    $map[$parentId]['side'] = $this->mergeSide((string)$map[$parentId]['side'], $nextSide);
                }
            }
        }

        return $map;
    }

    private function hasMarriage(int $a, int $b): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM marriages
             WHERE (person1_id = :a1 AND person2_id = :b1)
                OR (person1_id = :a2 AND person2_id = :b2)
             LIMIT 1'
        );
        $stmt->execute([':a1' => $a, ':b1' => $b, ':a2' => $b, ':b2' => $a]);
        return (bool)$stmt->fetch();
    }

    private function getSpouseIds(int $personId): array
    {
        $stmt = $this->db->prepare(
            'SELECT CASE WHEN person1_id = :id1 THEN person2_id ELSE person1_id END AS spouse_id
             FROM marriages
             WHERE person1_id = :id2 OR person2_id = :id3'
        );
        $stmt->execute([':id1' => $personId, ':id2' => $personId, ':id3' => $personId]);
        $rows = $stmt->fetchAll();
        return array_values(array_unique(array_map(static fn($r) => (int)$r['spouse_id'], $rows)));
    }

    private function result(
        string $label,
        string $category,
        string $side,
        int $degree,
        int $generationDelta,
        ?int $lcaPersonId,
        int $stepsFromPerson1,
        int $stepsFromPerson2
    ): array {
        return [
            'label' => $label,
            'category' => $category,
            'side' => $side,
            'degree' => $degree,
            'generation_delta' => $generationDelta,
            'lca_person_id' => $lcaPersonId,
            'steps_from_person1' => $stepsFromPerson1,
            'steps_from_person2' => $stepsFromPerson2,
        ];
    }

    private function siblingLabel(array $base, array $other): string
    {
        $gender = (string)($other['gender'] ?? 'unknown');
        $baseOrder = $this->comparableBirthOrder($base);
        $otherOrder = $this->comparableBirthOrder($other);

        if ($gender === 'male') {
            if ($baseOrder !== null && $otherOrder !== null) {
                return $otherOrder < $baseOrder ? 'Elder Brother' : 'Younger Brother';
            }
            return 'Brother';
        }
        if ($gender === 'female') {
            if ($baseOrder !== null && $otherOrder !== null) {
                return $otherOrder < $baseOrder ? 'Elder Sister' : 'Younger Sister';
            }
            return 'Sister';
        }
        return 'Sibling';
    }

    private function comparableBirthOrder(array $person): ?int
    {
        $dob = trim((string)($person['date_of_birth'] ?? ''));
        if ($dob !== '') {
            $ts = strtotime($dob);
            if ($ts !== false) {
                return (int)$ts;
            }
        }
        $year = (int)($person['birth_year'] ?? 0);
        if ($year > 0) {
            return $year;
        }
        return null;
    }

    private function ancestorDescendantLabel(int $distance, string $gender, bool $ancestor, string $side): string
    {
        $male = $ancestor ? 'Father' : 'Son';
        $female = $ancestor ? 'Mother' : 'Daughter';
        $neutral = $ancestor ? 'Parent' : 'Child';
        $base = $gender === 'male' ? $male : ($gender === 'female' ? $female : $neutral);

        if ($distance === 1) {
            return $base;
        }

        if ($distance === 2) {
            $baseLower = strtolower($base);
            $label = 'Grand' . $baseLower;
            if ($ancestor && $side === 'paternal') {
                return 'Paternal ' . ucfirst($label);
            }
            if ($ancestor && $side === 'maternal') {
                return 'Maternal ' . ucfirst($label);
            }
            return ucfirst($label);
        }

        if ($distance === 3) {
            $prefix = $ancestor ? 'Great Grand' : 'Great Grand';
            $label = $prefix . strtolower($base);
            return ucfirst($label);
        }

        $nth = $this->ordinal($distance - 2);
        return ucfirst($nth . ' great grand' . strtolower($base));
    }

    private function uncleAuntLabel(int $distance, string $gender, string $side): string
    {
        $base = $gender === 'male' ? 'Uncle' : ($gender === 'female' ? 'Aunt' : 'Uncle/Aunt');
        if ($distance === 2) {
            if ($side === 'paternal') return "Paternal $base";
            if ($side === 'maternal') return "Maternal $base";
            return $base;
        }
        return str_repeat('Great ', max(0, $distance - 2)) . $base;
    }

    private function nephewNieceLabel(int $distance, string $gender, string $side): string
    {
        $base = $gender === 'male' ? 'Nephew' : ($gender === 'female' ? 'Niece' : 'Niece/Nephew');
        if ($distance === 2) {
            return $base;
        }
        return str_repeat('Great ', max(0, $distance - 2)) . $base;
    }

    private function toInLawLabel(string $bloodLabel, string $gender): string
    {
        if (str_contains($bloodLabel, 'Father') || str_contains($bloodLabel, 'Mother') || str_contains($bloodLabel, 'Parent')) {
            if ($gender === 'male') return 'Father-in-law';
            if ($gender === 'female') return 'Mother-in-law';
            return 'Parent-in-law';
        }
        if (str_contains($bloodLabel, 'Son') || str_contains($bloodLabel, 'Daughter') || str_contains($bloodLabel, 'Child')) {
            if ($gender === 'male') return 'Son-in-law';
            if ($gender === 'female') return 'Daughter-in-law';
            return 'Child-in-law';
        }
        if (str_contains($bloodLabel, 'Brother') || str_contains($bloodLabel, 'Sister') || str_contains($bloodLabel, 'Sibling')) {
            if ($gender === 'male') return 'Brother-in-law';
            if ($gender === 'female') return 'Sister-in-law';
            return 'Sibling-in-law';
        }
        if (str_contains($bloodLabel, 'Cousin')) {
            return 'Cousin-in-law';
        }
        if (str_contains($bloodLabel, 'Uncle') || str_contains($bloodLabel, 'Aunt')) {
            return str_contains($bloodLabel, 'Aunt') ? 'Aunt-in-law' : 'Uncle-in-law';
        }
        if (str_contains($bloodLabel, 'Nephew') || str_contains($bloodLabel, 'Niece')) {
            return str_contains($bloodLabel, 'Niece') ? 'Niece-in-law' : 'Nephew-in-law';
        }
        return 'In-law';
    }

    private function nextSide(string $currentSide, string $parentType, int $currentDistance): string
    {
        $parentSide = match ($parentType) {
            'father' => 'paternal',
            'mother' => 'maternal',
            default => 'both',
        };

        if ($currentDistance === 0) {
            return $parentSide;
        }
        if ($currentSide === 'both' || $parentSide === 'both') {
            return 'both';
        }
        return $currentSide === $parentSide ? $currentSide : 'both';
    }

    private function mergeSide(string $a, string $b): string
    {
        if ($a === $b) return $a;
        if ($a === 'in_law' || $b === 'in_law') return 'in_law';
        if ($a === 'both' || $b === 'both') return 'both';
        return 'both';
    }

    private function ordinal(int $n): string
    {
        $words = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth', 6 => 'sixth'];
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

    private function removedText(int $n): string
    {
        return match ($n) {
            1 => 'once removed',
            2 => 'twice removed',
            3 => 'thrice removed',
            default => $n . ' times removed',
        };
    }
}
