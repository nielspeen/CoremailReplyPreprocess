<?php

namespace Modules\CoremailReplyPreprocess\Support;

final class BodyPreprocessor
{
    public static function preprocess(string $body): string
    {
        $lower   = \mb_strtolower($body, 'UTF-8');
        $isHtml  = \str_contains($lower, '<html') || \str_contains($lower, '<div') || \str_contains($lower, '<br');

        if ($isHtml) {
            return self::processHtml($body);
        }

        return self::processText($body);
    }

    private static function processHtml(string $html): string
    {
        // Gate: only act if Coremail’s quote container exists.
        if (!\preg_match('/<div[^>]+class=("|\')ntes-mailmaster-quote\1[^>]*>/i', $html, $m, \PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $cutPos = $m[0][1];
        $head   = \substr($html, 0, $cutPos);

        // Trim trivial tails introduced by the cut; no other transformations.
        $head = \preg_replace('#(</body>.*|</html>.*)$#is', '', $head) ?? $head;

        return \trim($head);
    }

    private static function processText(string $text): string
    {
        $text = \preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;

        $quoteLinePos = self::posOfExactLine($text, '---- Replied Message ----');
        if ($quoteLinePos === null) {
            return $text;
        }

        // Gate requires the characteristic vertical-bar “contact card” shortly below.
        if (!self::hasCoremailContactCardNear($text, $quoteLinePos, 20)) {
            return $text;
        }

        $visible = \substr($text, 0, $quoteLinePos);

        // Optional: trim trailing contact-card fragments if user typed right above it.
        $visible = self::trimTrailingBars($visible);

        // Normalize whitespace only lightly.
        $visible = \preg_replace("/\n{3,}/", "\n\n", \trim($visible)) ?? \trim($visible);

        return $visible;
    }

    private static function posOfExactLine(string $text, string $needle): ?int
    {
        $offset = 0;
        while (($pos = \mb_strpos($text, $needle, $offset, 'UTF-8')) !== false) {
            // Ensure it’s a full line match.
            $startOk = $pos === 0 || $text[$pos - 1] === "\n";
            $endPos  = $pos + \mb_strlen($needle, 'UTF-8');
            $endOk   = $endPos >= \strlen($text) || $text[$endPos] === "\n";
            if ($startOk && $endOk) {
                return (int) $pos;
            }
            $offset = (int) $endPos;
        }
        return null;
    }

    private static function hasCoremailContactCardNear(string $text, int $fromPos, int $scanLines): bool
    {
        $tail   = \substr($text, $fromPos);
        $lines  = \explode("\n", $tail);
        $limit  = \min($scanLines, \count($lines));

        $barCount = 0;
        $labelsHit = 0;

        for ($i = 0; $i < $limit; $i++) {
            $line = \trim($lines[$i]);

            if ($line === '' || $line === '|') {
                continue;
            }

            if ($line[0] === '|') {
                $barCount++;

                // Count common labels seen in Coremail’s grid; English or Chinese.
                if (\stripos($line, 'from') !== false || \str_contains($line, '发件人')) {
                    $labelsHit++;
                }
                if (\stripos($line, 'date') !== false || \str_contains($line, '日期')) {
                    $labelsHit++;
                }
                if (\stripos($line, 'to ') !== false || \str_contains($line, '收件人')) {
                    $labelsHit++;
                }
                if (\stripos($line, 'subject') !== false || \str_contains($line, '主题')) {
                    $labelsHit++;
                }
            }
        }

        // Require several vertical-bar rows and at least two expected labels to qualify.
        return $barCount >= 3 && $labelsHit >= 2;
    }

    private static function trimTrailingBars(string $text): string
    {
        $lines = \explode("\n", \rtrim($text));
        $i     = \count($lines) - 1;

        while ($i >= 0) {
            $line = \trim($lines[$i]);
            if ($line === '' || $line === '|' || $line[0] === '|') {
                $i--;
                continue;
            }
            break;
        }

        return \implode("\n", \array_slice($lines, 0, $i + 1));
    }
}
