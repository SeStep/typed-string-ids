<?php declare(strict_types=1);

namespace SeStep\EntityIds\Generator;

use SeStep\EntityIds\CharSet;
use SeStep\EntityIds\IdGenerator;
use SeStep\EntityIds\Type\CheckSum;

final class EncodedTypeIdGenerator implements IdGenerator
{

    private $typeToCheckSumMap = [];
    private $checkSumToTypeMap = [];

    /** @var CharSet */
    private $charSet;
    /** @var CheckSum */
    private $checkSum;

    /** @var int */
    private $length;

    /**
     * @param CharSet $charSet
     * @param CheckSum $checkSum
     * @param array $typeMap map of types where keys are expected check sums
     * @param int $length
     */
    public function __construct(
        CharSet $charSet,
        CheckSum $checkSum,
        array $typeMap = [],
        int $length = 12
    ) {
        $this->charSet = $charSet;
        $this->length = $length;
        $this->checkSum = $checkSum;

        $typeLimit = $this->checkSum->getDistinctValues();

        foreach ($typeMap as $checkSum => $type) {
            if (!is_int($checkSum)) {
                throw new \InvalidArgumentException("CheckSum '$checkSum' is not an integer");
            }
            if (0 > $checkSum || $checkSum >= $typeLimit) {
                throw new \InvalidArgumentException("Checksum for type '$type' ($checkSum) is not within" .
                    " allowed range <0, {$typeLimit})");
            }
            $this->checkSumToTypeMap[$checkSum] = $type;
            $this->typeToCheckSumMap[$type] = $checkSum;
        }
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, $this->typeToCheckSumMap);
    }


    /**
     * Creates new ID of given type
     *
     * @param string|null $type
     * @return string
     */
    public function generateId(string $type = null): string
    {
        if ($type && !array_key_exists($type, $this->typeToCheckSumMap)) {
            throw new \InvalidArgumentException("Type '$type' is not registered");
        }

        $id = $this->generate();

        if ($type) {
            $id = $this->checkSum->adjustValueToSum($id, $this->typeToCheckSumMap[$type]);
        }

        return $id;
    }

    public function getType(string $id): ?string
    {
        $checkSum = $this->checkSum($id);
        return $this->checkSumToTypeMap[$checkSum] ?? null;
    }

    private function checkSum(string $id): int
    {
        if (strlen($id) != $this->length) {
            return -1;
        }

        return $this->checkSum->compute($id);
    }

    private function generate(): string
    {
        if (class_exists(Nette\Utils\Random::class)) {
            return Nette\Utils\Random::generate($this->length, $this->charSet->getChars());
        }

        $chars = $this->charSet->getBase();

        $id = '';
        for ($i = 0; $i < $this->length; $i++) {
            $id .= $this->charSet->valueToChar(random_int(0, $chars - 1));
        }

        return $id;
    }
}
