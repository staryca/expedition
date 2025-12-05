<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\FileMarker;
use App\Entity\Report;
use App\Entity\ReportBlock;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
use Google\Client;
use Google\Service\YouTube;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function Symfony\Component\String\u;

class YoutubeService
{
    private const LANG_BE = 'be';
    public const MAX_LENGTH_TITLE = 100;
    public const MAX_LENGTH_DESCRIPTION = 5000;
    private const SHORTENER_TRUNCATE = 3;

    public function __construct(
        private readonly string $googleCredentials,
        private readonly TextHelper $textHelper,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getTitle(Report $report, FileMarker $fileMarker, int $shortener = 0): string
    {
        $localName = $fileMarker->getAdditionalLocalName();
        $baseName = $fileMarker->getAdditionalDance();
        $improvisation = $fileMarker->getAdditionalImprovisation();
        $danceType = $fileMarker->getAdditionalPack();
        $ritual = $fileMarker->getAdditionalValue(FileMarkerAdditional::RITUAL);
        $dateActionNotes = $fileMarker->getAdditionalValue(FileMarkerAdditional::DATE_ACTION_NOTES);

        $parts = [];

        // last shortener: show the part of local name only
        if ($shortener >= self::SHORTENER_TRUNCATE && mb_strlen($localName) < $shortener - 10) {
            return $localName;
        }

        // 3rd shortener: truncate the local name
        $part = $shortener < self::SHORTENER_TRUNCATE
            ? $localName
            : u($localName)->truncate(mb_strlen($localName) - $shortener + self::SHORTENER_TRUNCATE - 1, '...', false)
        ;
        $localNameText = str_replace(' ', '', mb_strtolower($localName));
        $baseNameText = str_replace(' ', '', mb_strtolower($baseName));
        // 1st shortener: hide the base name
        $part .= empty($baseName) || str_contains($localNameText, $baseNameText) || $shortener >= 1
                || $improvisation === FileMarkerAdditional::IMPROVISATION_MIKITA_CASE || mb_strlen($localName) > 20
            ? ''
            : ' (' . $baseName . ') ';
        if (!empty($part)) {
            $parts[] = $part;
        }

        if ($fileMarker->isCategoryDance() || $fileMarker->isCategoryQuadrille()) {
            $danceTypeOneWord = mb_substr($danceType, 1, 1) !== ' ' && mb_substr($danceType, 2, 1) !== ' ';
            $texts = [];
            if ($improvisation === FileMarkerAdditional::IMPROVISATION_VALUE) {
                $texts[] = $fileMarker->getCategory() === CategoryType::QUADRILLE ? mb_substr($improvisation, 0, -1) . 'ая' : $improvisation;
            }
            if ($danceType !== '' && $danceTypeOneWord) {
                $texts[] = $danceType;
            }
            $texts[] = mb_strtolower($fileMarker->getCategoryName());
            if (!$danceTypeOneWord) {
                $texts[] = $danceType;
            }
            if ($improvisation === FileMarkerAdditional::IMPROVISATION_MIKITA_CASE && $localName !== 'Мікіта') {
                $texts[] = FileMarkerAdditional::IMPROVISATION_MIKITA_CASE;
            }
            if ($improvisation !== FileMarkerAdditional::IMPROVISATION_MIKITA_CASE && $improvisation !== FileMarkerAdditional::IMPROVISATION_VALUE && !empty($improvisation)) {
                $texts[] = $improvisation;
            }
            $text = implode(' ', $texts);
            $parts[] = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        } else {
            $parts[] = $fileMarker->getCategoryName();
        }

        // 2nd shortener: hide the ritual
        if (!empty($ritual) && $shortener < 2) {
            $partsRitual = explode('#', $ritual);
            if (count($partsRitual) > 4) {
                $_last = array_pop($partsRitual);
                $ritual = array_pop($partsRitual) . ' (' . $_last . ')';
            } else {
                $ritual = array_pop($partsRitual);
            }
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

        $title = implode(' / ', $parts);

        $title = trim(preg_replace('!\s+!', ' ', $title));

        if (mb_strlen($title) > self::MAX_LENGTH_TITLE) {
            $step = $shortener < self::SHORTENER_TRUNCATE ? 1 : mb_strlen($title) - self::MAX_LENGTH_TITLE;

            return $this->getTitle($report, $fileMarker, $shortener + $step);
        }

        return $title;
    }

    public function getDescription(FileMarker $fileMarker, ?string $otherText = null): string
    {
        $localName = $fileMarker->getAdditionalLocalName();
        $baseName = $fileMarker->getAdditionalDance();
        $danceType = $fileMarker->getAdditionalPack();
        $improvisation = $fileMarker->getAdditionalImprovisation();
        $tradition = $fileMarker->getAdditionalValue(FileMarkerAdditional::TRADITION);
        $ritual = $fileMarker->getAdditionalValue(FileMarkerAdditional::RITUAL);
        $dateActionNotes = $fileMarker->getAdditionalValue(FileMarkerAdditional::DATE_ACTION_NOTES);
        $tmkb = $fileMarker->getAdditionalValue(FileMarkerAdditional::TMKB);

        $parts = [];

        if ($fileMarker->isCategoryDance() || $fileMarker->isCategoryQuadrille()) {
            $danceTypeOneWord = mb_substr($danceType, 1, 1) !== ' ' && mb_substr($danceType, 2, 1) !== ' ';
            $texts = [];
            $tradition_text = FileMarkerAdditional::getTradition($tradition);
            if (!empty($tradition_text)) {
                $texts[] = $tradition_text;
            }
            if (empty($ritual) && !$fileMarker->isCategoryQuadrille()) {
                $texts[] = 'пазаабрадавы';
            }
            if ($danceTypeOneWord && $improvisation === FileMarkerAdditional::IMPROVISATION_VALUE) {
                $texts[] = FileMarkerAdditional::IMPROVISATION_VALUE;
            }
            if ($danceType !== '' && $danceTypeOneWord) {
                $texts[] = $danceType;
            }
            $texts[] = $fileMarker->isCategoryDance()
                ? mb_strtolower($fileMarker->getCategoryName())
                : mb_strtolower(CategoryType::getSingleName(CategoryType::DANCE)) .  ' тыпу кадрылі';
            if (!$danceTypeOneWord) {
                $texts[] = $danceType;
            }
            if ($improvisation === FileMarkerAdditional::IMPROVISATION_MIKITA_CASE && $localName !== 'Мікіта') {
                $texts[] = FileMarkerAdditional::IMPROVISATION_MIKITA_CASE;
            }
            if ($improvisation !== FileMarkerAdditional::IMPROVISATION_MIKITA_CASE && $improvisation !== FileMarkerAdditional::IMPROVISATION_VALUE && !empty($improvisation)) {
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

        $informants = $fileMarker->getReportBlock()->getInformantsWithoutMusicians();
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
        $informants = $fileMarker->getReportBlock()->getMusicians();
        $persons = [];
        foreach ($informants as $informant) {
            $persons[] = $informant->getFirstName()
                . (null !== $informant->getYearBirth() ? ', ' . $informant->getYearBirth() . ' г.н.' : '')
                . (!empty($informant->getNotes()) ? ' (' . $informant->getNotes() . ')' : '');
        }
        if (!empty($persons)) {
            $parts[] = 'Музык' . (count($persons) === 1 ? 'а' : 'і') . ': ' . implode('; ', $persons); // todo
        }

        $geoPoint = $fileMarker->getReport()->getGeoPoint();
        $part = '';
        if (null !== $geoPoint) {
            $part = TextHelper::lettersToUpper($geoPoint->getPrefixBe())
                . ' ' . $geoPoint->getNameWordStressOrName() . ', ' . $geoPoint->getDistrict() . ', ' . $geoPoint->getRegion() . '.';
        }
        $date = !empty($dateActionNotes)
            ? $dateActionNotes
            : (empty($fileMarker->getReport()->getDateActionYear()) ? '' : $fileMarker->getReport()->getDateActionYear() . ' годзе.');
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

        $tags = [];
        if (!empty($localName) && substr_count($localName, ' ') <= 3) {
            $tag = false !== mb_stripos($localName, $categoryName) ? $localName : $categoryName . ' ' . $localName;
            $tags[] = '#' . $this->textHelper->getTagFormat($tag);
        }
        if (!empty($baseName) && $baseName !== $localName && substr_count($baseName, ' ') <= 3) {
            $tag = false !== mb_stripos($baseName, $categoryName) ? $baseName : $categoryName . ' ' . $baseName;
            $tags[] = '#' . $this->textHelper->getTagFormat($tag);
        }
        if (null !== $geoPoint) {
            $tags[] = '#' . $this->textHelper->getTagFormat($geoPoint->getPrefixBe() . ' ' . $geoPoint->getName());
            $tags[] = '#' . $this->textHelper->getTagFormat($geoPoint->getDistrict(), true);
            $tags[] = '#' . $this->textHelper->getTagFormat($geoPoint->getRegion(), true);
        }
        if ($fileMarker->getCategory() !== CategoryType::DANCE_MOVEMENTS) {
            $tags[] = '#' . $this->textHelper->getTagFormat($categoryNameMany . ' беларусаў');
        }
        $parts[] = implode(' ', $tags);

        if (!empty($tmkb)) {
            $parts[] = $tmkb;
        }

        $notes = 'Апрацоўку відэа рабіла валантэрская група М.А. Козенкі ў 2023-2025 гг. з дэвізам: "Я буду драцца за кожную бабулю!"';
        $notes .= ' Калі ласка, будзьце тактычныя і ўважлівыя пры напісанні вашых допісаў да відэа. Не дасылайце паведамленні, якія парушаюць закон, змяшчаюць пагрозы, абразы ці непрыстойнасці. Архіў мае за сабой права не публікаваць вашы каментары. Калі вы з гэтым не пагаджаецеся, калі ласка, не дасылайце іх.';
        $notes .= ' Калі вы кагосьці пазналі ці людзі, якіх вы дакладна ведаеце, не пазначаныя, то напішыце ў каментары.';
        $parts[] = $notes;

        if ($otherText) {
            $parts[] = $otherText;
        }

        return implode('<br><br>', $parts);
    }

    public function fixDescription(string $description): string
    {
        $description = str_replace(['<br>', '<', '>'], ["\n", '{', '}'], $description);

        return mb_substr($description, 0, 5000);
    }

    public function getGoogleClient(): Client
    {
        $client = new Client();
        $client->setApplicationName("Ethno-app");

        $client->setScopes([
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.force-ssl',
        ]);
        $client->setAuthConfig($this->googleCredentials);

        $redirect_uri = $this->urlGenerator->generate('user_profile', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $client->setRedirectUri($redirect_uri);

        return $client;
    }

    public function getYoutubeService(): YouTube
    {
        $client = $this->getGoogleClient();

        $token = $this->requestStack->getSession()->get('access_token');
        $client->setAccessToken($token);

        return new YouTube($client);
    }

    public function updateInYouTube(FileMarker $fileMarker): mixed
    {
        $youtube = $this->getYoutubeService();

        $videoId = $fileMarker->getAdditionalYoutube();
        if (empty($videoId)) {
            return 'No video item';
        }

        $listResponse = $youtube->videos->listVideos("snippet", ['id' => $videoId]);
        if ($listResponse === null) {
            return null;
        }
        if (0 === count($listResponse->getItems())) {
            return 'No videos found. Check out the list of available videos.';
        }

        $video = $listResponse->getItems()[0];
        $snippet = $video->getSnippet();
        $snippet->setTitle($this->getTitle($fileMarker->getReport(), $fileMarker));
        $snippet->setDefaultAudioLanguage(self::LANG_BE);

        $description = $this->getDescription($fileMarker);
        $snippet->setDescription($this->fixDescription($description));

        return $youtube->videos->update('snippet', $video);
    }
}
