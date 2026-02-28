<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GeoPointSearchDto;
use App\Entity\GeoPoint;
use App\Entity\Type\GeoPointType;
use App\Helper\TextHelper;
use App\Repository\GeoPointRepository;

class LocationService
{
    public const string DISTRICT = 'раён';
    public const string SUBDISTRICT = 'сельскі Савет';
    public const string SUBDISTRICT_SHORT = 'с/с';
    public const string REGION = 'вобласць';

    public const float POINT_RADIUS = 0.35; // 0.1 = 22km
    public const float POINT_NEIGHBOR = 0.1; // 0.1 = 22km
    public const float POINT_NEAR = 0.18; // 0.1 = 22km
    public const float LAT_LON_RATE = 1.65;

    public function __construct(
        private readonly GeoPointRepository $geoPointRepository,
    ) {
    }

    private static function addComma(string $text, string $block): string
    {
        $pos = mb_strpos($text, $block);
        if ($pos !== false) {
            $pos2 = mb_strrpos(mb_substr($text, 0, $pos - 1), ' ');
            if ($pos2 !== false) {
                $text = mb_substr($text, 0, $pos2) . ',' . mb_substr($text, $pos2);
            }
        }

        $pos = mb_strpos($text, $block);
        if ($pos !== false) {
            $ending = mb_substr($text, $pos - 4, 3);
            if ('ага' === $ending) {
                $text = mb_substr($text, 0, $pos - 4) . 'і ' . mb_substr($text, $pos);
            }
        }

        return $text;
    }

    public function getSearchDtoByFullPlace(string $fullPlace): GeoPointSearchDto
    {
        $fullPlace = str_replace(
            ['р-н', 'раёна', 'сельсавет', 'вобл.', 'вобласці', '  ', ' - ', ' -', '- '],
            [self::DISTRICT, self::DISTRICT, self::SUBDISTRICT_SHORT, self::REGION, self::REGION, ' ', '-', '-', '-'],
            TextHelper::replaceLetters($fullPlace)
        );
        $fullPlace = self::addComma($fullPlace, self::DISTRICT);
        $fullPlace = self::addComma($fullPlace, self::SUBDISTRICT_SHORT);
        $fullPlace = self::addComma($fullPlace, self::REGION);

        $parts = TextHelper::explodeBySeparatorAndBrackets(',', $fullPlace);

        $district = null;
        $subDistrict = null;
        $region = null;
        $places = [];
        if (1 === count($parts)) {
            $places[] = $fullPlace;
        } else {
            foreach ($parts as $part) {
                if ('' === trim($part)) {
                    continue;
                }

                if (str_contains($part, self::DISTRICT)) {
                    $district = trim($part);
                } elseif (str_contains($part, self::SUBDISTRICT_SHORT)) {
                    $subDistrict = trim($part);
                } elseif (str_contains($part, self::REGION)) {
                    $region = str_replace('кай', 'кая', trim($part));
                } elseif (str_contains($part, 'Беларусь')) {
                    continue;
                } else {
                    $place = trim($part, " `\t\n\r\0\x0B");
                    if (!empty($place)) {
                        $places[] = $place;
                    }
                }
            }
        }

        $hasLocation = false;
        if (count($places) > 1) {
            foreach ($places as $key => $place) {
                if (!self::isLocation($place) || ($hasLocation && mb_substr($place, -3) === 'цкі')) {
                    unset($places[$key]); // skip notes after locations
                } else {
                    $hasLocation = true;
                }
            }
        }
        if (count($places) !== 1) {
            $dto = new GeoPointSearchDto();
            $dto->limit = 0;

            return $dto;
        }
        $place = current($places);

        if (null === $district) {
            $pos = mb_strpos($place, self::DISTRICT);
            if ($pos !== false) {
                $posDistrict = mb_strrpos(trim(mb_substr($place, 0, $pos)), ' ');
                if ($posDistrict !== false) {
                    $district = mb_substr($place, $posDistrict + 1, $pos + 4);
                    $place = mb_substr($place, 0, $posDistrict);
                }
            }
        }
        if ($district && ($pos = mb_strpos($district, self::DISTRICT)) > 0) {
            $district = trim(mb_substr($district, 0, $pos + 4));
        }

        if (null === $subDistrict) {
            $pos = mb_strpos($place, self::SUBDISTRICT_SHORT);
            if ($pos !== false) {
                $posSubDistrict = mb_strrpos(trim(mb_substr($place, 0, $pos)), ' ');
                if ($posSubDistrict !== false) {
                    $subDistrict = trim(mb_substr($place, $posSubDistrict + 1, $pos + 3));
                    $place = mb_substr($place, 0, $posSubDistrict);
                }
            }
        }
        if (isset(GeoPointSearchDto::SUBDISTRICTS[$subDistrict])) {
            $subDistrict = GeoPointSearchDto::SUBDISTRICTS[$subDistrict];
        }
        if ($subDistrict && ($pos = mb_strpos($subDistrict, self::SUBDISTRICT_SHORT)) > 0) {
            $subDistrict = trim(mb_substr($subDistrict, 0, $pos)) . ' ' . self::SUBDISTRICT;
        }

        return $this->getSearchDto($place, $district, $subDistrict, $region);
    }

    public function getSearchDto(
        string $place,
        ?string $district = null,
        ?string $subDistrict = null,
        ?string $region = null
    ): GeoPointSearchDto {
        $dto = new GeoPointSearchDto();
        $place = TextHelper::replaceLetters($place);

        if (!empty($district)) {
            $district = str_replace(
                ['р-н', "'"],
                [self::DISTRICT, '’'],
                TextHelper::replaceLetters($district)
            );
            $district = preg_replace('!\s+!', ' ', $district);
            if (isset(GeoPointSearchDto::DISTINCTS[$district])) {
                $district = GeoPointSearchDto::DISTINCTS[$district];
            } elseif (str_contains($district, '.')) {
                $search = str_replace('.', ')[^.]+(', '/(' . $district . ')/');
                foreach (GeoPointSearchDto::DISTINCTS as $old => $new) {
                    preg_match($search, $old, $matches);
                    if (!empty($matches)) {
                        $district = $new;
                        break;
                    }
                }
            }
            if (str_contains($place, $district)) {
                $district = null;
            }
        }

        $place = str_replace(
            array('и', 'Дя', 'тё', 'Бальшая ', ' е', '  ', "'", '`', 'ёв'),
            array('і', 'Дзя', 'цё', 'Вялікая ', ' Е', ' ', '’', '', 'оў'),
            $place
        );
        [$place, $place2] = TextHelper::getNames($place);

        $replacer_index = trim($place . ' ' . ($district ?? ''));
        if (isset(GeoPointSearchDto::REPLACER[$replacer_index])) {
            $place = GeoPointSearchDto::REPLACER[$replacer_index];
        }
        if (isset(GeoPointSearchDto::REPLACE_DISTRICT[$replacer_index])) {
            $district = GeoPointSearchDto::REPLACE_DISTRICT[$replacer_index];
            if (($pos = mb_strpos($place, '(')) > 3) {
                $place = trim(mb_substr($place, 0, $pos));
            }
        }

        foreach (GeoPointSearchDto::PREFIXES as $search => $prefix) {
            $place = str_replace($search, $prefix, $place);
        }

        $prefix = self::getPrefixForPlace($place);
        $shortPart = '';
        $village = $place;
        if (str_contains($village, '?')) {
            $village = '';
        }

        if ($prefix === GeoPointType::BE_VILLAGE_SHORT) {
            $dto->prefixes = GeoPointType::BE_VILLAGE_LONGS;
        } elseif ($prefix === GeoPointType::BE_SETTLEMENT_SHORT) {
            $dto->prefixes = GeoPointType::BE_SETTLEMENT_LONGS;
        } elseif (isset(GeoPointType::BE_SHORT_LONG[$prefix])) {
            $dto->prefixes[] = GeoPointType::BE_SHORT_LONG[$prefix];
            if ($prefix === GeoPointType::BE_URBAN_SETTLEMENT_SHORT) {
                $district = null; // For 'гарадскі пасёлак' district can be wrong
            }
        } else {
            $shortPart = $prefix;
            $dto->prefixes = GeoPointType::BE_VILLAGE_LONGS;
        }

        $dto->district = $district;

        $dto->subDistrict = $subDistrict
            ? str_replace("'", '’', TextHelper::replaceLetters($subDistrict))
            : null;

        $dto->region = $region;

        if (!empty($village)) {
            $village = TextHelper::lettersToUpper($village);
            if (mb_substr($village, -2) === 'чі') {
                $village = mb_substr($village, 0, -2) . 'чы';
            }
            if (mb_strpos($village, 'ті') > 0) {
                $village = str_replace('ті', 'ці', $village);
            }
            $dto->names[] = $village;
            $pos = mb_strpos($village, 'е');
            while ($pos !== false && mb_substr($village, $pos + 1, 1) !== ' ' && $pos <= mb_strlen($village)) {
                $dto->names[] = mb_substr($village, 0, $pos) . 'ё' . mb_substr($village, $pos + 1);
                $pos = mb_stripos($village, 'е', $pos + 1);
            }

            if (mb_substr($village, -1) === 'я') {
                $dto->names[] = mb_substr($village, 0, -1) . 'е';
            }
            if (mb_strpos($village, 'ей') > 0) {
                $dto->names[] = str_replace('ей', 'я', $village);
            }
            if (mb_strpos($village, 'Іо') > 0) {
                $dto->names[] = str_replace('Іо', 'Ё', $village);
            }
            if (mb_strpos($village, 'Чырвона') > 0) {
                $dto->names[] = str_replace('Чырвона', 'Красна', $village);
            }
            if (mb_substr($village, -2) === 'ае') {
                $dto->names[] = mb_substr($village, 0, -2) . 'ая';
            }
            if (mb_substr($village, -2) === 'цы') {
                $dto->names[] = mb_substr($village, 0, -2) . 'ца';
            }
            if (mb_substr($village, -2) === 'ці') {
                $dto->names[] = mb_substr($village, 0, -2) . 'цы';
            }
            if (mb_substr($village, -2) === 'жа') {
                $dto->names[] = mb_substr($village, 0, -2) . 'жы';
            }
            if (mb_substr($village, -2) === 'ла') {
                $dto->names[] = mb_substr($village, 0, -2) . 'лы';
            }
            if (mb_substr($village, -2) === 'чы') {
                $dto->names[] = mb_substr($village, 0, -2) . 'ча';
            }
            if (mb_substr($village, -2) === 'ча' && mb_substr($village, -3, 1) !== 'ч') {
                $dto->names[] = mb_substr($village, 0, -2) . 'чча';
            }
            if (mb_substr($village, -2) === 'вы') {
                $dto->names[] = mb_substr($village, 0, -2) . 'ва';
            }
            if (mb_substr($village, -2) === 'нь' || mb_substr($village, -2) === 'на') {
                $dto->names[] = mb_substr($village, 0, -1);
            }
            if (($pos = mb_strpos($village, 'о')) > 0) {
                $dto->names[] = mb_substr($village, 0, $pos) . 'а' . mb_substr($village, $pos + 1);
            }
            $pos = mb_strpos($village, 'а');
            if ($pos > 0 && $pos < mb_strlen($village) - 1) {
                $dto->names[] = mb_substr($village, 0, $pos) . 'о' . mb_substr($village, $pos + 1);
            }
            if (($pos = mb_strpos($village, 'е')) > 0) {
                $_variant = mb_substr($village, 0, $pos) . 'я' . mb_substr($village, $pos + 1);
                if (!in_array($_variant, $dto->names)) {
                    $dto->names[] = $_variant;
                }
            }
            if (($pos = mb_strpos($village, 'я')) > 0) {
                $_variant = mb_substr($village, 0, $pos) . 'е' . mb_substr($village, $pos + 1);
                if (!in_array($_variant, $dto->names)) {
                    $dto->names[] = $_variant;
                }
            }
            if (mb_strpos($village, 'ё') > 0) {
                $dto->names[] = str_replace('ё', 'е', $village);
            }
            if (mb_strpos($village, 'ш') > 0) {
                $dto->names[] = str_replace('ш', 'т', $village);
            }
            if (($pos = mb_strpos($village, 'ы')) > 0) {
                $_name = mb_substr($village, 0, $pos) . 'а' . mb_substr($village, $pos + 1);
                if (!in_array($_name, $dto->names)) {
                    $dto->names[] = $_name;
                }
            }
            if (($pos = mb_strpos($village, 'эле')) > 0) {
                $dto->names[] = mb_substr($village, 0, $pos) . 'эе' . mb_substr($village, $pos + 3);
            }
            if (($pos = mb_strpos($village, 'і')) > 0 && $pos < mb_strlen($village) - 1) {
                $dto->names[] = mb_substr($village, 0, $pos) . 'я' . mb_substr($village, $pos + 1);
            }
            if (mb_substr($village, -2) === 'кі') {
                $dto->names[] = mb_substr($village, 0, -2) . 'ка';
            }
            if (($pos = mb_strpos($village, 'сь')) > 0) {
                $dto->names[] = mb_substr($village, 0, $pos + 1) . mb_substr($village, $pos + 2);
            }
            if (mb_strpos($village, '.') > 0) {
                $dto->names[] = str_replace('.', '’', $village);
            }
            if ($shortPart === 'Ст') {
                $dto->names[] = 'Старое ' . $village;
                $dto->names[] = 'Стары ' . $village;
                $dto->names[] = 'Старая ' . $village;
            }
            if ($shortPart === 'В' || $shortPart === 'Б') {
                $dto->names[] = 'Вялікае ' . $village;
                $dto->names[] = 'Вялікі ' . $village;
                $dto->names[] = 'Вялікая ' . $village;
            }
            if (str_contains($village, 'Вялікая ') || str_contains($village, 'Малая ')) {
                $dto->names[] = str_replace(['Вялікая ', 'Малая '], '', $village);
            }
        }

        if ($place2 !== '') {
            $dto->names[] = $place2;
        }

        return $dto;
    }

    public static function getPrefixForPlace(string &$place): string
    {
        $prefix = '';

        $pos = mb_strpos($place, '.');
        if ($pos !== false) {
            $prefix = mb_substr($place, 0, $pos);
            $village = trim(mb_substr($place, $pos + 1));
        } else {
            $village = $place;
            foreach (GeoPointType::BE_SHORT_LONG as $prefix_short => $prefix_long) {
                if (str_starts_with($place, $prefix_long)) {
                    $prefix = $prefix_short;
                    $village = trim(mb_substr($place, mb_strlen($prefix_long)));
                    break;
                }
            }
        }
        $place = $village;

        return $prefix;
    }

    public static function isLocation(string $text): bool
    {
        $parts = [$text];
        $location = self::getLocationFromNotes($parts);

        return $location !== null;
    }

    public static function getLocationFromNotes(array &$notes): ?string
    {
        $locations = [];
        $hasVillage = false;

        $words = ['р-н', 'сельсавет', 'вобл.', 'вобласці', self::DISTRICT, self::SUBDISTRICT_SHORT, self::REGION];

        foreach ($notes as $key => $note) {
            if (str_contains($note, 'г.н')) {
                continue;
            }

            if (str_starts_with($note, 'з ')) {
                $note = mb_substr($note, 2);
            }

            $prefix = self::getPrefixForPlace($note);
            if (!empty($prefix)) {
                $note = $prefix . '. ' . $note;
                if (isset(GeoPointType::BE_SHORT_LONG[$prefix])) {
                    $locations[] = $note;
                    unset($notes[$key]);
                    $hasVillage = true;
                    continue;
                }
            }

            foreach ($words as $word) {
                if (str_contains($note, $word)) {
                    $locations[] = $note;
                    unset($notes[$key]);
                    break;
                }
            }

            if (!$hasVillage && !str_contains($note, ' ') && TextHelper::isName($note)) {
                $locations[] = $note;
                unset($notes[$key]);
            }
        }

        return empty($locations) ? null : implode(', ', $locations);
    }

    public function parsePlace(string $place, ?string $district, ?string $subDistrict, string &$locationText): ?GeoPoint
    {
        $place = TextHelper::cleanManySpaces($place);

        if (!empty($district)) {
            $district = str_replace(['р.', 'р-н'], LocationService::DISTRICT, $district);
            if (!str_contains($district, LocationService::DISTRICT)) {
                $district .= ' ' . LocationService::DISTRICT;
            }
            $district = TextHelper::cleanManySpaces($district);
        }

        $locationText = '';
        if (empty($place) && !empty($district)) {
            $locationText = $district;
        } elseif (!empty($place)) {
            if (empty($district) && empty($subDistrict)) {
                $place = str_replace(['р.', 'р-н'], LocationService::DISTRICT, $place);
                $location = $this->detectLocationByFullPlace($place);
            } else {
                $location = $this->detectLocation($place, $district, $subDistrict);
            }

            if ($location !== null) {
                 return $location;
            }

            $locationText = $place . ($district === '' ? '' : ', ') . $district;
        }

        return null;
    }

    public function detectLocation(string $place, ?string $district = null, ?string $subDistrict = null): ?GeoPoint
    {
        $dto = $this->getSearchDto($place, $district, $subDistrict);

        return $this->detectLocationBySearchDto($dto);
    }

    public function detectLocationByFullPlace(string $place, ?string $additionalDistrict = null): ?GeoPoint
    {
        $dto = $this->getSearchDtoByFullPlace($place);

        if ($additionalDistrict !== null && $dto->district === null) {
            $dto->district = $additionalDistrict;
        }

        return $this->detectLocationBySearchDto($dto);
    }

    private function detectLocationBySearchDto(GeoPointSearchDto $dto): ?GeoPoint
    {
        $dto->limit = 5; // Need only 1, but others need for logs
        $points = $this->geoPointRepository->findByNameAndDistrict($dto);

        // todo: Log for count more 1
        if (count($points) === 1) {
            return $points[0];
        }

        return null;
    }

    public static function getDistance(float $radius): float
    {
        return round(22 * $radius / 0.1);
    }

    /**
     * @param GeoPointSearchDto $dto
     * @return array<GeoPoint>
     */
    public function findVariantsBySearchDto(GeoPointSearchDto $dto): array
    {
        $name = current($dto->names);
        if (empty($name)) {
            return [];
        }

        $dto->limit = 700; // max 645 in 'Браслаўскі раён'
        $dto->names = [];
        $dto->prefixes = [];
        $dto->subDistrict = null;
        $points = $this->geoPointRepository->findByNameAndDistrict($dto);

        $scope = 500;
        $variants = [];
        foreach ($points as $point) {
            $index = TextHelper::compareWords($name, $point->getName());
            if ($index < $scope) {
                continue;
            }

            while ($index >= $scope && isset($variants[$index])) {
                if ($index === 2000) {
                    $scope = 2000;
                }
                $index--;
            }
            $variants[$index] = $point;

            if ($scope * 2 <= $index) {
                $scope = $index / 2;
            }
        }

        krsort($variants);
        $variants = array_slice($variants, 0, 6, true);
        $max = $scope < 2000 ? key($variants) : 3950;

        foreach ($variants as $index => $point) {
            if ($index * 2 < $max) {
                unset($variants[$index]);
            }
        }

        return $variants;
    }
}
