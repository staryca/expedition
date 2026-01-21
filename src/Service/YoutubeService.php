<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Category;
use App\Entity\FileMarker;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
use App\Repository\FileMarkerRepository;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Exception;
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

    /**
     * Only from 1 expedition!
     */
    private bool $isSetMarkers = false;
    /** @var array<FileMarker>|null $markersByPlace */
    private ?array $markersByPlace = null;
    /** @var array<FileMarker>|null $markersByType */
    private ?array $markersByType = null;

    public function __construct(
        private readonly string $googleCredentials,
        private readonly TextHelper $textHelper,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly FileMarkerRepository $fileMarkerRepository,
    ) {
    }

    public function getTitle(FileMarker $fileMarker, int $shortener = 0): string
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
        } elseif ($fileMarker->isCategoryNotOther()) {
            $categoryName = $fileMarker->getCategoryName();
            $nameHasType = !empty($localName) && !empty($categoryName) && str_contains(mb_strtolower($localName), mb_strtolower($categoryName));
            if (!$nameHasType) {
                $parts[] = $categoryName;
            }
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

        $report = $fileMarker->getReport();
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

            return $this->getTitle($fileMarker, $shortener + $step);
        }

        return $title;
    }

    public function getDescription(FileMarker $fileMarker): string
    {
        $localName = $fileMarker->getAdditionalLocalName();
        $baseName = $fileMarker->getAdditionalDance();
        $dateActionNotes = $fileMarker->getAdditionalValue(FileMarkerAdditional::DATE_ACTION_NOTES);
        $tmkb = $fileMarker->getAdditionalValue(FileMarkerAdditional::TMKB);

        $parts = [];

        $markerDescription = $this->getMarkerDescription($fileMarker);
        if (!empty($markerDescription)) {
            $parts[] = $markerDescription . '.';
        }

        $notes = $fileMarker->getNotes();
        if (!empty($notes)) {
            $parts[] = $notes;
        }

        $part = '';
        // Date
        $date = !empty($dateActionNotes)
            ? $dateActionNotes
            : (empty($fileMarker->getReport()->getDateActionYear()) ? '' : $fileMarker->getReport()->getDateActionYear() . ' годзе.');
        if (!empty($date)) {
            $part .= $fileMarker->getCategory() !== CategoryType::FILM
                ? 'Відэа запісана Козенкам М.А. у ' . $date
                : 'Відэа запісаны ў ' . $date;
        }

        // Location
        $geoPoint = $fileMarker->getReport()->getGeoPoint();
        if (null !== $geoPoint) {
            if (!empty($part)) {
                $part .= '<br>';
            }
            $texts = [
                TextHelper::lettersToUpper($geoPoint->getPrefixBe()) . ' ' . $geoPoint->getNameWordStressOrName(),
            ];
            if ($geoPoint->getShortSubdistrict()) {
                $texts[] = $geoPoint->getShortSubdistrict();
            }
            $texts[] = $geoPoint->getDistrict();
            $texts[] = $geoPoint->getRegion();

            $part .= implode(', ', $texts) . '.';
        }
        if (!empty($part)) {
            $parts[] = $part;
        }

        $partPersons = '';
        $organization = $fileMarker->getReportBlock()?->getOrganization();
        if ($organization) {
            $partPersons .= $organization->getName() . '.';
        }

        $informants = $fileMarker->getReportBlock()->getInformantsWithoutMusicians();
        $persons = [];
        foreach ($informants as $informant) {
            $persons[] = $informant->getFirstName()
                . (null !== $informant->getYearBirth() ? ', ' . $informant->getYearBirth() . ' г.н.' : '')
                . (!empty($informant->getNotes()) ? ' (' . $informant->getNotes() . ')' : '');
        }
        if (!empty($persons)) {
            if (!empty($partPersons)) {
                $partPersons .= '<br>';
            }
            $partPersons = 'Выконва' . (count($persons) === 1 ? 'е' : 'юць') . ': ' . implode('; ', $persons) . '.'; // todo
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
            if (!empty($partPersons)) {
                $partPersons .= '<br>';
            }
            $partPersons .= 'Музык' . (count($persons) === 1 ? 'а' : 'і') . ': ' . implode('; ', $persons) . '.'; // todo
        }
        if (!empty($partPersons)) {
            $parts[] = $partPersons;
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
        if (!empty($localName) && substr_count($localName, ' ') <= 3 && !$fileMarker->isCategoryStory()) {
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

        $notes = 'Відэа падрыхтавана да публікацыі валанцёрскай групай ў 2023-2026 гг.';
        $notes .= ' Калі ласка, будзьце тактычныя і ўважлівыя пры напісанні вашых допісаў да відэа. Калі вы кагосьці пазналі ці людзі, якіх вы дакладна ведаеце, не пазначаныя, то напішыце ў каментары.';
        $notes .= ' Не дасылайце паведамленні, якія парушаюць закон, змяшчаюць пагрозы, абразы ці непрыстойнасці. Архіў мае за сабой права не публікаваць вашы каментары. Калі вы з гэтым не пагаджаецеся, калі ласка, не дасылайце іх.';
        $parts[] = $notes;

        if (!$this->isSetMarkers) {
            $markers = $this->fileMarkerRepository->getMarkersWithFullObjects(
                $fileMarker->getReport()->getExpedition()
            );
            $this->createLinks($markers);
            $this->isSetMarkers = true;
        }
        $descriptionLinks = '';

        // Other YouTube link by place
        $linkKey = $fileMarker->getReport()->getMiddleGeoPlace();
        $linkPlaceMarker = isset($this->markersByPlace[$linkKey])
            ? self::getRandomMarker($this->markersByPlace[$linkKey], $fileMarker->getId(), $fileMarker->getPublishDate())
            : null;
        if ($linkPlaceMarker && !empty($linkPlaceMarker->getAdditionalYoutubeLink())) {
            $descriptionLinks .= 'Глядзіце яшчэ ';
            $descriptionLinks .= $linkPlaceMarker->isCategoryNotOther()
                ? mb_strtolower($linkPlaceMarker->getCategoryName())
                : '';
            $descriptionLinks .= ' "' . $linkPlaceMarker->getAdditionalLocalName() . '"';
            $descriptionLinks .= ' адсюль жа (' . $linkPlaceMarker->getReport()->getShortGeoPlace(true) . ')';
            $descriptionLinks .= ': ' . $linkPlaceMarker->getAdditionalYoutubeLink();
        }

        // Other YouTube link by type
        $linkKey = self::getLinkKey($fileMarker);
        $linkTypeMarker = isset($this->markersByType[$linkKey])
            ? self::getRandomMarker($this->markersByType[$linkKey], $fileMarker->getId(), $fileMarker->getPublishDate())
            : null;
        if ($linkTypeMarker && !empty($linkTypeMarker->getAdditionalYoutubeLink())) {
            if (!empty($descriptionLinks)) {
                $descriptionLinks .= '<br>';
            }
            $descriptionLinks .= 'Глядзіце яшчэ ';
            $descriptionLinks .= $linkTypeMarker->isCategoryNotOther()
                ? mb_strtolower($linkTypeMarker->getCategoryName())
                : '';
            $descriptionLinks .= ' "' . $linkTypeMarker->getAdditionalLocalName() . '"';
            if ($linkTypeMarker->getReport()->getId() !== $fileMarker->getReport()->getId()) {
                $descriptionLinks .= ', ' . $linkTypeMarker->getReport()->getMiddleGeoPlace(false);
            }
            $descriptionLinks .= ': ' . $linkTypeMarker->getAdditionalYoutubeLink();
        }
        if (!empty($descriptionLinks)) {
            $parts[] = $descriptionLinks;
        }

        $tags = [];
        if (CategoryType::asDanceType($fileMarker->getCategory())) {
            $texts = ['традыцыйны танец', 'беларускі народны танец', 'побытавы танец', 'фальклор Беларусі',
                'традиционный танец', 'бытовой танец', 'фольклор Беларуси', 'traditional dance', 'folklore Belarus'];
            foreach ($texts as $text) {
                $tags[] = '#' . $this->textHelper->getTagFormat($text);
            }
        }
        if (!empty($tags)) {
            $parts[] = implode(' ', $tags);
        }

        return implode('<br><br>', $parts);
    }

    public function fixDescription(string $description): string
    {
        $description = str_replace(['<br>', '<', '>'], ["\n", '{', '}'], $description);

        return mb_substr($description, 0, 5000);
    }

    /**
     * @throws \Google\Exception
     */
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

    /**
     * @throws \Google\Exception
     */
    public function getYoutubeService(): YouTube
    {
        $client = $this->getGoogleClient();

        $token = $this->requestStack->getSession()->get('access_token');
        $client->setAccessToken($token);

        return new YouTube($client);
    }

    /**
     * @throws Exception
     * @throws \Google\Exception
     */
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
        $snippet->setTitle($this->getTitle($fileMarker));
        $snippet->setDefaultAudioLanguage(self::LANG_BE);

        $description = $this->getDescription($fileMarker);
        $snippet->setDescription($this->fixDescription($description));

        $result = $youtube->videos->update('snippet', $video);

        if (!is_string($result)) {
            $fileMarker->addAdditional(FileMarkerAdditional::STATUS_UPDATED, '1');
        }

        return $result;
    }

    /**
     * @throws Exception
     * @throws \Google\Exception
     */
    public function showInYouTube(FileMarker $fileMarker): mixed
    {
        $youtube = $this->getYoutubeService();

        $videoId = $fileMarker->getAdditionalYoutube();
        if (empty($videoId)) {
            return 'No video item';
        }

        $listResponse = $youtube->videos->listVideos("status", ['id' => $videoId]);
        if ($listResponse === null) {
            return null;
        }
        if (0 === count($listResponse->getItems())) {
            return 'No videos found. Check out the list of available videos.';
        }

        $video = $listResponse->getItems()[0];
        $status = $video->getStatus();
        $status->setPrivacyStatus('public');

        $result = $youtube->videos->update('status', $video);

        if (!is_string($result)) {
            $fileMarker->addAdditional(FileMarkerAdditional::STATUS_ACTIVE, '1');
        }

        return $result;
    }

    /**
     * @param array<FileMarker> $markers
     */
    private function createLinks(array $markers): void
    {
        foreach ($markers as $marker) {
            if (!empty($marker->getAdditionalYoutube())) {
                $this->markersByPlace[$marker->getReport()->getMiddleGeoPlace()][] = $marker;
                $this->markersByType[self::getLinkKey($marker)][] = $marker;
            }
        }
    }

    private static function getLinkKey(FileMarker $marker): string
    {
        return match (true) {
            $marker->getCategory() === CategoryType::DANCE => CategoryType::DANCE . '-' . $marker->getAdditionalDance(),
            default => $marker->getCategoryName(),
        };
    }

    /**
     * @param array<FileMarker> $array
     * @param int $exceptId
     * @param Carbon|null $publishDate
     * @return FileMarker|null
     */
    private static function getRandomMarker(array $array, int $exceptId, ?Carbon $publishDate): ?FileMarker
    {
        shuffle($array);

        foreach ($array as $marker) {
            if (!$publishDate && $marker->getPublish()) {
                continue;
            }
            if ($publishDate && $marker->getPublish() > $publishDate) {
                continue;
            }

            if ($marker->getId() !== $exceptId && !empty($marker->getAdditionalYoutube())) {
                return $marker;
            }
        }

        return null;
    }

    /**
     * @param string $playlist
     * @param array<FileMarker> $markers
     * @return array
     * @throws Exception
     * @throws \Google\Exception
     */
    public function addMarkersInPlaylist(string $playlist, array $markers): array
    {
        $youtube = $this->getYoutubeService();

        $videoIds = [];
        foreach ($markers as $marker) {
            $videoId = $marker->getAdditionalYoutube();
            if (empty($videoId)) {
                continue;
            }

            $videoIds[$marker->getId()] = $videoId;
        }

        $result = [];
        $result['amount'] = count($videoIds);
        $result['videoIds'] = $videoIds;

        $result['in_playlist'] = 0;
        $result['deleted_from_playlist'] = 0;
        $result['added_to_playlist'] = 0;

        try {
            $list = $youtube->playlistItems->listPlaylistItems('snippet', ['playlistId' => $playlist]);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['error_playlist'] = $playlist;

            return $result;
        }

        $items = $list->getItems();
        foreach ($items as $item) {
            $videoId = $item->getSnippet()->getResourceId()->getVideoId();

            $markerId = array_search($videoId, $videoIds);
            if ($markerId !== false) {
                $result['in_playlist']++;
                unset($videoIds[$markerId]);
            } else {
                try {
                    $youtube->playlistItems->delete($item->id);
                } catch (\Exception $e) {
                    $result['error'] = $e->getMessage();
                    $result['error_video'] = $videoId;

                    return $result;
                }

                $result['deleted_from_playlist']++;
            }
        }

        foreach ($videoIds as $markerId => $videoId) {
            $resource = new YouTube\ResourceId();
            $resource->setVideoId($videoId);
            $resource->setKind('youtube#video');

            $snippet = new YouTube\PlaylistItemSnippet();
            $snippet->setPlaylistId($playlist);
            $snippet->setResourceId($resource);

            $item = new YouTube\PlaylistItem();
            $item->setSnippet($snippet);

            try {
                $youtube->playlistItems->insert('snippet', $item);
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['error_video'] = $videoId;
                $result['error_marker'] = $markerId;

                break;
            }
            $result['added_to_playlist']++;
        }

        return $result;
    }

    public static function getPlaylistLink(string $playlistId): string
    {
        return 'https://www.youtube.com/playlist?list=' . $playlistId;
    }

    public function getMarkerDescription(FileMarker $fileMarker): string
    {
        $localName = $fileMarker->getAdditionalLocalName();
        $danceType = $fileMarker->getAdditionalPack();
        $improvisation = $fileMarker->getAdditionalImprovisation();
        $tradition = $fileMarker->getAdditionalValue(FileMarkerAdditional::TRADITION);
        $ritual = $fileMarker->getAdditionalValue(FileMarkerAdditional::RITUAL);

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
            if (!empty($localName)) {
                $texts[] = TextHelper::getTextWithQuotation($localName);
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

            return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        } elseif ($fileMarker->isCategoryNotOther()) {
            $texts = [];
            $tradition_text = FileMarkerAdditional::getTradition($tradition);
            if (!empty($tradition_text)) {
                $texts[] = $tradition_text;
            }
            if ($danceType !== '') {
                $texts[] = $danceType;
            }
            if ($improvisation !== '') {
                $texts[] = $improvisation;
            }

            $categoryName = $fileMarker->getCategoryName();
            $nameHasType = !empty($localName) && !empty($categoryName) && str_contains(mb_strtolower($localName), mb_strtolower($categoryName));
            if (!empty($categoryName) && !$nameHasType) {
                if ($fileMarker->isCategoryDanceMovements()) {
                    $texts = [
                        CategoryType::getDanceMovementName($texts)
                    ];
                } else {
                    $texts[] = $categoryName;
                }
            }
            if (!empty($localName)) {
                $texts[] = $nameHasType ? $localName : TextHelper::getTextWithQuotation($localName);
            }

            $text = implode(' ', $texts);

            return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        }

        return '';
    }
}
