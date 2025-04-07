<?php

declare(strict_types=1);

namespace App\Helper;

class TextHelper
{
    public static function replaceLetters(string $string): string
    {
        return str_replace(
            ['i', 'a', 'o', 'e', 'c', 'y', 'x', 'p', 'B', 'A', 'I', 'O', 'E', 'C', 'X', 'P', ' ',
                'ы́', 'о́', 'а́', 'е́', 'э́', 'і́', 'у́', 'ю́', 'я́', 'О́', 'А́', 'Е́', 'Э́', 'І́', 'У́', 'Ю́', 'Я́'],
            ['і', 'а', 'о', 'е', 'с', 'у', 'х', 'р', 'В', 'А', 'І', 'О', 'Е', 'С', 'Х', 'Р', ' ',
                'ы', 'о', 'а', 'е', 'э', 'і', 'у', 'ю', 'я', 'О', 'А', 'Е', 'Э', 'І', 'У', 'Ю', 'Я'],
            trim($string)
        );
    }

    public function lettersToUpper(string $string): string
    {
        $string = ' ' . trim($string);
        $pos = mb_strpos($string, ' ');
        while ($pos !== false) {
            $string = mb_substr($string, 0, $pos + 1) . mb_strtoupper(mb_substr($string, $pos + 1, 1)) . mb_substr($string, $pos + 2);
            $pos = mb_strpos($string, ' ', $pos + 1);
        }

        return trim($string);
    }

    /**
     * @param string $content
     * @return array<string>
     */
    public function getNotes(string $content): array
    {
        return $this->getParts($content, '(', ')');
    }

    /**
     * @param string $content
     * @return array<string>
     */
    public function getNames(string $content): array
    {
        return $this->getParts($content, '[', ']');
    }

    /**
     * @param string $content
     * @param string $start
     * @param string $end
     * @return array<string>
     */
    public function getParts(string $content, string $start, string $end): array
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
    public function isName(string $name): bool
    {
        $nameLower = mb_strtolower(mb_substr($name, 0, 1)) . mb_substr($name, 1);
        return $name !== $nameLower && $nameLower === mb_strtolower($name);
    }

    /* return true for Nameofsomething or (Nameofsomething) */
    public function isNameWithBrackets(string $name): bool
    {
        if (mb_substr($name, 0, 1) === '(' && mb_substr($name, -1) === ')') {
            $name = mb_substr($name, 1, -1);
        }

        return $this->isName($name);
    }

    /**
     * @param array<string> $separators
     * @param string $text
     * @return array<string>
     */
    public function explodeWithBrackets(array $separators, string $text): array
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
        $text = str_replace(["\r", "\n", ';;', ' '], [";", ";", ';', ' '], $text);

        return trim(preg_replace('!\s+!', ' ', $text));
    }
}
