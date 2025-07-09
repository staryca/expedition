<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\FileMarker;
use App\Entity\Report;
use App\Entity\ReportBlock;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;

class YoutubeService
{
    public function __construct(
        private readonly TextHelper $textHelper,
    ) {
    }
    public function getTitle(Report $report, FileMarker $fileMarker): string
    {
        $localName = $fileMarker->getAdditionalValue(FileMarkerAdditional::LOCAL_NAME);
        $baseName = $fileMarker->getAdditionalValue(FileMarkerAdditional::BASE_NAME);
        $improvisation = $fileMarker->getAdditionalValue(FileMarkerAdditional::IMPROVISATION);
        $danceType = $fileMarker->getAdditionalValue(FileMarkerAdditional::DANCE_TYPE);
        $ritual = $fileMarker->getAdditionalValue(FileMarkerAdditional::RITUAL);
        $dateActionNotes = $fileMarker->getAdditionalValue(FileMarkerAdditional::DATE_ACTION_NOTES);

        $parts = [];

        $part = $localName;
        $part .= empty($baseName) || str_contains(mb_strtolower($localName), mb_strtolower($baseName)) || $improvisation === FileMarkerAdditional::IMPROVISATION_MIKITA
            ? ''
            : ' (' . $baseName . ') ';
        if (!empty($part)) {
            $parts[] = $part;
        }

        $part = $improvisation === FileMarkerAdditional::IMPROVISATION_VALUE ? $improvisation . ' ' : '';
        $part .= mb_substr($danceType, 1, 1) !== ' ' && mb_substr($danceType, 2, 1) !== ' ' && $improvisation === FileMarkerAdditional::IMPROVISATION_VALUE
            ? ($fileMarker->getCategory() === CategoryType::QUADRILLE ? mb_substr($improvisation, 0, -1) . 'ая' : $improvisation)
            : '';
        $part .= $danceType === '' || mb_substr($danceType, 1, 1) === ' ' || mb_substr($danceType, 2, 1) === ' '
            ? ''
            : ' ' . $danceType . ' ';
        $part .= empty($localName) && empty($baseName) && empty($danceType)
            ? $fileMarker->getCategoryName()
            : mb_strtolower($fileMarker->getCategoryName());
        $part .= mb_substr($danceType, 1, 1) === ' ' || mb_substr($danceType, 2, 1) === ' '
            ? ' ' . $danceType
            : '';
        $part .= $improvisation === FileMarkerAdditional::IMPROVISATION_MIKITA && $localName !== 'Мікіта'
            ? ' тыпу Мікіта'
            : '';
        $part .= $improvisation !== FileMarkerAdditional::IMPROVISATION_MIKITA && $improvisation !== FileMarkerAdditional::IMPROVISATION_VALUE && !empty($improvisation)
            ? ' ' . $improvisation
            : '';
        $parts[] = $part;

        if (!empty($ritual)) {
            $parts[] = $ritual;
        }

        $date = !empty($dateActionNotes)
            ? $dateActionNotes
            : (empty($report->getDateActionYear()) ? '' : $report->getDateActionYear() . ' г.');
        if (!empty($date)) {
            $parts[] = $date;
        }

        $geoPoint = $report->getGeoPoint();
        if (null !== $geoPoint) {
            $parts[] = $geoPoint->getName() . ', ' . $geoPoint->getDistrict();
        }

        return implode(' / ', $parts);
    }

    public function getDescription(Report $report, ReportBlock $reportBlock, FileMarker $fileMarker): string
    {
        $localName = $fileMarker->getAdditionalValue(FileMarkerAdditional::LOCAL_NAME);
        $baseName = $fileMarker->getAdditionalValue(FileMarkerAdditional::BASE_NAME);
        $danceType = $fileMarker->getAdditionalValue(FileMarkerAdditional::DANCE_TYPE);
        $improvisation = $fileMarker->getAdditionalValue(FileMarkerAdditional::IMPROVISATION);
        $tradition = $fileMarker->getAdditionalValue(FileMarkerAdditional::TRADITION);
        $ritual = $fileMarker->getAdditionalValue(FileMarkerAdditional::RITUAL);
        $dateActionNotes = $fileMarker->getAdditionalValue(FileMarkerAdditional::DATE_ACTION_NOTES);
        $tmkb = $fileMarker->getAdditionalValue(FileMarkerAdditional::TMKB);

        $parts = [];

        if ($fileMarker->getCategory() === CategoryType::DANCE) {
            $texts = [];
            if ($tradition === FileMarkerAdditional::TRADITION_LATE) {
                $texts[] = FileMarkerAdditional::TRADITION_LATE;
            }
            if (empty($ritual)) {
                $texts[] = 'пазаабрадавы';
            }
            if (mb_substr($danceType, 1, 1) !== ' ' && mb_substr($danceType, 2, 1) !== ' ' && $improvisation === FileMarkerAdditional::IMPROVISATION_VALUE) {
                $texts[] = FileMarkerAdditional::IMPROVISATION_VALUE;
            }
            if ($danceType !== '' && mb_substr($danceType, 1, 1) !== ' ' && mb_substr($danceType, 2, 1) !== ' ') {
                $texts[] = $danceType;
            }
            $texts[] = mb_strtolower($fileMarker->getCategoryName());
            if (mb_substr($danceType, 1, 1) === ' ' || mb_substr($danceType, 2, 1) === ' ') {
                $texts[] = $danceType;
            }
            if ($improvisation === FileMarkerAdditional::IMPROVISATION_MIKITA && $localName !== 'Мікіта') {
                $texts[] = 'тыпу Мікіта';
            }
            if ($improvisation !== FileMarkerAdditional::IMPROVISATION_MIKITA && $improvisation !== FileMarkerAdditional::IMPROVISATION_VALUE && !empty($improvisation)) {
                $texts[] = $improvisation;
            }
            if (empty($improvisation)) {
                $texts[] = 'з устойлівай кампазіцыяй';
            }

            $text = implode(' ', $texts);
            $parts[] = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1) . '.';
        }

        $notes = $fileMarker->getNotes();
        if (!empty($notes)) {
            $parts[] = $notes;
        }

        $informants = $reportBlock->getInformantsWithoutMusicians();
        $persons = [];
        foreach ($informants as $informant) {
            $persons[] = $informant->getFirstName()
                . (null !== $informant->getYearBirth() ? ', ' . $informant->getYearBirth() . ' г.н.' : '')
                . (!empty($informant->getNotes()) ? ' (' . $informant->getNotes() . ')' : '');
        }
        if (!empty($persons)) {
            $parts[] = 'Выконваюць: ' . implode('; ', $persons); // todo
        }

        // Musicians
        $informants = $reportBlock->getMusicians();
        $persons = [];
        foreach ($informants as $informant) {
            $persons[] = $informant->getFirstName()
                . (null !== $informant->getYearBirth() ? ', ' . $informant->getYearBirth() . ' г.н.' : '')
                . (!empty($informant->getNotes()) ? ' (' . $informant->getNotes() . ')' : '');
        }
        if (!empty($persons)) {
            $parts[] = 'Музык' . (count($persons) === 1 ? 'а' : 'і') . ': ' . implode('; ', $persons); // todo
        }

        $geoPoint = $report->getGeoPoint();
        $part = '';
        if (null !== $geoPoint) {
            $part = $this->textHelper->lettersToUpper($geoPoint->getPrefixBe())
                . ' ' . $geoPoint->getName() . ', ' . $geoPoint->getDistrict() . ', ' . $geoPoint->getRegion() . '.';
        }
        $date = !empty($dateActionNotes)
            ? $dateActionNotes
            : (empty($report->getDateActionYear()) ? '' : $report->getDateActionYear() . ' годзе.');
        if (!empty($date)) {
            if (!empty($part)) {
                $part .= '<br>';
            }
            $part .= $fileMarker->getCategory() !== CategoryType::FILM
                ? 'Запісана Козенкам М.А. у ' . $date
                : 'Запісаны ў ' . $date;
        }
        if (!empty($part)) {
            $parts[] = $part;
        }

        $texts = $fileMarker->getDecoding();
        if (!empty($texts)) {
            $parts[] = ($fileMarker->getCategory() === CategoryType::DANCE ? 'Прыпеўкі:' : 'Словы:')
                . (str_contains($texts, "\n") ? '<br>' : ' ')
                . str_replace("\n", '<br>', $texts); // todo
        }

        $category = $fileMarker->getCategory();
        $categoryName = CategoryType::getSingleName($category);
        $categoryNameMany = CategoryType::getManyOrSingleName($category);

        $tags = ['#' . $this->textHelper->getTagFormat($categoryNameMany . ' беларусаў')];
        if (!empty($localName)) {
            $tag = false !== mb_stripos($localName, $categoryName) ? $localName : $categoryName . ' ' . $localName;
            $tags[] = '#' . $this->textHelper->getTagFormat($tag);
        }
        if (!empty($baseName) && $baseName !== $localName) {
            $tag = false !== mb_stripos($baseName, $categoryName) ? $baseName : $categoryName . ' ' . $baseName;
            $tags[] = '#' . $this->textHelper->getTagFormat($tag);
        }
        if (null !== $geoPoint) {
            $tags[] = '#' . $this->textHelper->getTagFormat($geoPoint->getPrefixBe() . ' ' . $geoPoint->getName());
            $tags[] = '#' . $this->textHelper->getTagFormat($geoPoint->getDistrict(), true);
            $tags[] = '#' . $this->textHelper->getTagFormat($geoPoint->getRegion(), true);
        }
        $parts[] = implode(' ', $tags);

        if (!empty($tmkb)) {
            $parts[] = $tmkb;
        }

        $parts[] = 'Падрыхтоўку відэа рабіла валантэрская група М.А. Козенкі ў 2023-2025 гг. Калі ласка, будзьце тактычныя і ўважлівыя пры напісанні вашых допісаў да відэа. Не дасылайце паведамленні, якія парушаюць закон, змяшчаюць пагрозы, абразы ці непрыстойнасці. Архіў мае за сабой права не публікаваць вашы каментары. Калі вы з гэтым не пагаджаецеся, калі ласка, не дасылайце іх.';

        return implode('<br><br>', $parts);
    }
}
