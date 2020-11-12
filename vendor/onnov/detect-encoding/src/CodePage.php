<?php declare(strict_types=1);
/**
 * User: onnov
 * Date: 02.09.2019
 * Time: 18:25
 */

namespace Onnov\DetectEncoding;

/**
 * Class CodePage
 *
 * @package Onnov\DetectEncoding
 */
class CodePage
{
    /**
     * Method to get a custom encoding range
     *
     * @param string $uppercaseLetters
     * @param string $lowercaseLetters
     * @param string $encoding
     *
     * @return array<string, array<string, string>>
     */
    public function getRange(
        string $uppercaseLetters,
        string $lowercaseLetters,
        string $encoding
    ): array {
        return [
            $encoding => [
                'upper' => $this->getRangeStr($this->getLetterArr(
                    $uppercaseLetters,
                    $encoding
                )),
                'lower' => $this->getRangeStr($this->getLetterArr(
                    $lowercaseLetters,
                    $encoding
                )),
            ],
        ];
    }

    /**
     * @param array $array
     *
     * @return string
     */
    private function getRangeStr(array $array): string
    {
        $ranges = [];
        $last = null;
        foreach ($array as $current) {
            if ($current > $last + 1) {
                $lastKey = array_key_last($ranges);
                if (null !== $lastKey) {
                    $ranges[$lastKey][1] = $last;
                }
                $ranges[] = [$current, null];
            }
            $last = $current;
        }
        $lastKey = array_key_last($ranges);
        $ranges[$lastKey][1] = $last;

        $stringIntervals = [];
        foreach ($ranges as $interval) {
            if (current($interval) < end($interval)) {
                $stringIntervals[] = implode('-', $interval);
                continue;
            }
            $stringIntervals[] = array_pop($interval);
        }
        $string = implode(', ', $stringIntervals);

        return $string;
    }

    /**
     * @param string $strLetters
     * @param string $encoding
     *
     * @return array<int, int|string>
     */
    private function getLetterArr(string &$strLetters, string $encoding): array
    {
        $res = [];
        $str = iconv('utf-8', $encoding . '//IGNORE', $strLetters);
        if (is_string($str)) {
            $res = array_keys(count_chars($str, 1));
        }

        return $res;
    }
}
