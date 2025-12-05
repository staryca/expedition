<?php

declare(strict_types=1);

namespace App\Helper;

class TextHelper
{
    public static function replaceLetters(string $string): string
    {
        return str_replace(
            ['i', 'a', 'o', 'e', 'c', 'y', 'x', 'p', 'B', 'A', 'I', 'O', 'E', 'C', 'X', 'P', ' ', '',
                'ы́', 'о́', 'а́', 'е́', 'э́', 'і́', 'у́', 'ю́', 'я́', 'О́', 'А́', 'Е́', 'Э́', 'І́', 'У́', 'Ю́', 'Я́'],
            ['і', 'а', 'о', 'е', 'с', 'у', 'х', 'р', 'В', 'А', 'І', 'О', 'Е', 'С', 'Х', 'Р', ' ', '',
                'ы', 'о', 'а', 'е', 'э', 'і', 'у', 'ю', 'я', 'О', 'А', 'Е', 'Э', 'І', 'У', 'Ю', 'Я'],
            trim($string)
        );
    }

    public static function lettersToUpper(string $text): string
    {
        $result = [];

        $parts = explode(' ', $text);
        foreach ($parts as $part) {
            $result[] = mb_ucfirst($part);
        }

        return implode(' ', $result);
    }

    /* Replace AbcdeF to Abcdef */
    public static function fixName(string $text): string
    {
        $name = mb_strtoupper(mb_substr($text, 0, 1)) . mb_strtolower(mb_substr($text, 1));
        $nameWithLast = mb_substr($name, 0, -1) . mb_strtoupper(mb_substr($name, -1));

        return $nameWithLast === $text ? $name : $text;
    }

    /**
     * Convert 'aaaa Bbbb cccc DDDD' to 'aaaaBbbbCcccDddd'
     *
     * @param string $text
     * @param bool $reverse
     * @return string
     */
    public function getTagFormat(string $text, bool $reverse = false): string
    {
        $text = $this::replaceLetters($text);

        if ($reverse) {
            $parts = explode(' ', $text);
            $parts = array_reverse($parts);
            $text = implode(' ', $parts);
        }

        $text = str_replace(['-', ',', '"'], ' ', $text);
        $text = preg_replace('/\d/', ' ', $text);
        $text = self::cleanManySpaces($text);
        $text = self::lettersToUpper(mb_strtolower($text));
        $text = str_replace(' ', '', $text);

        return mb_strtolower(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    /**
     * @param string $content
     * @return array<string>
     */
    public static function getNotes(string $content): array
    {
        return self::getParts($content, '(', ')');
    }

    /**
     * @param string $content
     * @return array<string>
     */
    public static function getNames(string $content): array
    {
        return self::getParts($content, '[', ']');
    }

    /**
     * @param string $content
     * @param string $start
     * @param string $end
     * @return array<string>
     */
    public static function getParts(string $content, string $start, string $end): array
    {
        $notes = '';
        $pos = mb_strpos($content, $start);
        if ($pos !== false) {
            $notes = mb_substr($content, $pos + 1);
            $content = mb_substr($content, 0, $pos);
            if (false !== ($pos = mb_strpos($notes, $end))) {
                $content .= ' ' . mb_substr($notes, $pos + 1);
                $notes = mb_substr($notes, 0, $pos);
            }
        }

        return [trim($content), trim($notes)];
    }

    /* return true for Nameofsomething */
    public static function isName(string $name): bool
    {
        $nameLower = mb_strtolower(mb_substr($name, 0, 1)) . mb_substr($name, 1);
        return $name !== $nameLower && $nameLower === mb_strtolower($name);
    }

    /* return true for Nameofsomething or (Nameofsomething) or Name-Something */
    public static function isNameWithBrackets(string $name): bool
    {
        if (str_starts_with($name, '(') && mb_substr($name, -1) === ')') {
            $name = mb_substr($name, 1, -1);
        }

        return self::isMultiName($name);
    }

    /* return true for Nameofsomething or Name-Something */
    public static function isMultiName(string $name): bool
    {
        $parts = explode('-', $name);
        foreach ($parts as $part) {
            if (!self::isName($part)) {
                return false;
            }
        }

        return count($parts) > 0;
    }

    /* return true for A.B. */
    public static function isShortNames(string $name): bool
    {
        $name = trim($name);

        $parts = explode('.', $name);
        if (count($parts) < 3 || !empty($parts[2])) {
            return false;
        }
        unset($parts[2]);

        foreach ($parts as $part) {
            if (mb_strlen($part) > 1 || mb_strtoupper($part) !== $part) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string> $separators
     * @param string $text
     * @return array<string>
     */
    public static function explodeWithBrackets(array $separators, string $text): array
    {
        $separatorBlock = '^^^';
        $key = 0;
        $blocks = [];
        $posA = mb_strpos($text, '(');
        while ($posA !== false) {
            $posB = mb_strpos($text, ')', $posA);
            if ($posB !== false) {
                $block = mb_substr($text, $posA + 1, $posB - $posA - 1);
                $text = mb_substr($text, 0, $posA) . $separatorBlock . '{' . $key . '}' . mb_substr($text, $posB + 1);
            } else {
                $block = mb_substr($text, $posA + 1);
                $text = mb_substr($text, 0, $posA);
            }
            $blocks[$key] = $block;
            $key++;
            $posA = mb_strpos($text, '(');
        }

        $separatorBase = '^^^^^^';
        foreach ($separators as $separator) {
            $text = str_replace($separator, $separatorBase, $text);
        }

        $result = [];
        $parts = explode($separatorBase, $text);
        foreach ($parts as $part) {
            $pos = null;
            while (false !== $pos) {
                $pos = mb_strpos($part, $separatorBlock . '{');
                if ($pos !== false) {
                    $key = (int) mb_substr($part, $pos + mb_strlen($separatorBlock) + 1);
                    if (isset($blocks[$key])) {
                        $part = str_replace($separatorBlock . '{' . $key . '}', '(' . $blocks[$key] . ')', $part);
                    } else {
                        $pos = false;
                    }
                }
            }

            $result[] = $part;
        }

        return $result;
    }

    public static function cleanManySpaces(string $text): string
    {
        $text = str_replace(["\r", "\n", ';;', ' ', ''], [";", ";", ';', ' ', ''], $text);

        return trim(preg_replace('!\s+!', ' ', $text));
    }

    /**
     * @param string $text
     * @return array<string>
     */
    public static function explodeByBrackets(string $text): array
    {
        $blocks = [];

        $posA = mb_strpos($text, '(');
        while ($posA !== false) {
            $posB = mb_strpos($text, ')', $posA);
            $posStart = 0;
            if ($posB !== false) {
                if ($posA > 0 && $posB - $posA === 2) {
                    $posStart = $posB;
                    $block = '';
                } else {
                    if ($posA > 0) {
                        $block = trim(mb_substr($text, 0, $posA - 1));
                        if (!empty($block)) {
                            $blocks[] = $block;
                        }
                    }
                    $block = mb_substr($text, $posA + 1, $posB - $posA - 1);
                    $text = mb_substr($text, $posB + 1);
                }
            } else {
                $block = mb_substr($text, $posA + 1);
                $text = mb_substr($text, 0, $posA);
            }
            if (!empty($block)) {
                $blocks[] = $block;
            }

            $posA = mb_strpos($text, '(', $posStart);
        }
        $text = trim($text);
        if (!empty($text)) {
            $blocks[] = $text;
        }

        return $blocks;
    }

    public static function explodeBySeparatorAndBrackets(string $separator, string $text): array
    {
        $parts = [];

        $blocks = self::explodeByBrackets($text);
        foreach ($blocks as $block) {
            $parts = [...$parts, ...explode($separator, $block)];
        }

        return $parts;
    }
}
