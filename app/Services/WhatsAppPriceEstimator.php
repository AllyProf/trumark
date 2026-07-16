<?php

namespace App\Services;

use App\Models\SystemSetting;

class WhatsAppPriceEstimator
{
    /**
     * Parse order text and return line items + total using the bot price list in settings.
     */
    public static function estimate(string $orderText): array
    {
        $catalog = self::getCatalog();
        $lines = preg_split('/[\n,;]+/', strtolower($orderText)) ?: [];
        $breakdown = [];
        $total = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $matched = null;
            foreach ($catalog as $item) {
                foreach ($item['keywords'] as $keyword) {
                    if (str_contains($line, strtolower($keyword))) {
                        $matched = $item;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                continue;
            }

            $qty = self::extractQuantity($line);
            $lineTotal = $matched['unit_price'] * $qty;
            $total += $lineTotal;

            $breakdown[] = [
                'label' => $matched['label'],
                'quantity' => $qty,
                'unit_price' => $matched['unit_price'],
                'line_total' => $lineTotal,
            ];
        }

        $deliveryFee = null;
        if ($total > 0) {
            $deliveryFee = (int) SystemSetting::get('wa_delivery_fee_dar', '4000');
            $total += $deliveryFee;
        }

        return [
            'breakdown' => $breakdown,
            'delivery_fee' => $deliveryFee,
            'total' => $total > 0 ? $total : null,
            'matched' => count($breakdown) > 0,
        ];
    }

    public static function formatEstimateMessage(array $estimate): string
    {
        if (empty($estimate['matched'])) {
            return '';
        }

        $msg = "💰 *Makadirio ya Bei / Price Estimate*\n\n";
        foreach ($estimate['breakdown'] as $row) {
            $msg .= sprintf(
                "• %s x%d = TZS %s\n",
                $row['label'],
                $row['quantity'],
                number_format($row['line_total'])
            );
        }

        if (!empty($estimate['delivery_fee'])) {
            $msg .= sprintf("• Delivery (Dar) ≈ TZS %s\n", number_format($estimate['delivery_fee']));
        }

        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= sprintf("*Jumla makadirio: TZS %s*\n", number_format($estimate['total']));
        $msg .= "_(Bei halisi itathibitishwa na mhudumu)_";

        return $msg;
    }

    protected static function extractQuantity(string $line): int
    {
        if (preg_match('/(?:x|×|\*)\s*(\d+)/i', $line, $m)) {
            return max(1, (int) $m[1]);
        }
        if (preg_match('/(\d+)\s*(?:nakala|pcs|pieces|vitabu|boksi|box|ream|reams|pack)/i', $line, $m)) {
            return max(1, (int) $m[1]);
        }
        if (preg_match('/-\s*(\d+)\s*$/', $line, $m)) {
            return max(1, (int) $m[1]);
        }

        return 1;
    }

    public static function getCatalog(): array
    {
        $raw = SystemSetting::get('wa_bot_price_list', '');
        if (trim($raw) !== '') {
            $parsed = self::parseCatalogText($raw);
            if (!empty($parsed)) {
                return $parsed;
            }
        }

        return self::defaultCatalog();
    }

    protected static function parseCatalogText(string $raw): array
    {
        $catalog = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3) {
                continue;
            }

            $keywords = array_filter(array_map('trim', explode(',', $parts[0])));
            $label = $parts[1];
            $price = (int) preg_replace('/[^0-9]/', '', $parts[2]);

            if (empty($keywords) || $price <= 0) {
                continue;
            }

            $catalog[] = [
                'keywords' => array_values($keywords),
                'label' => $label,
                'unit_price' => $price,
            ];
        }

        return $catalog;
    }

    protected static function defaultCatalog(): array
    {
        return [
            ['keywords' => ['daftari 3 quire', 'counter 3 quire', '3 quire'], 'label' => 'Daftari Counter 3 Quire', 'unit_price' => 2500],
            ['keywords' => ['daftari 4 quire', 'counter 4 quire', '4 quire'], 'label' => 'Daftari Counter 4 Quire', 'unit_price' => 3200],
            ['keywords' => ['daftari 2 quire', 'counter 2 quire', '2 quire'], 'label' => 'Daftari Counter 2 Quire', 'unit_price' => 1800],
            ['keywords' => ['daftari 1 quire', 'counter 1 quire', '1 quire'], 'label' => 'Daftari Counter 1 Quire', 'unit_price' => 1200],
            ['keywords' => ['exercise book', 'daftari a5', 'exercise'], 'label' => 'Exercise Book A5', 'unit_price' => 500],
            ['keywords' => ['kalamu bic', 'bic boksi', 'kalamu boksi'], 'label' => 'Kalamu Bic (Boksi 50)', 'unit_price' => 9000],
            ['keywords' => ['a4 ream', 'karatasi a4', 'double a', 'paperone'], 'label' => 'Karatasi A4 Ream', 'unit_price' => 12000],
            ['keywords' => ['box file', 'faili la ofisi'], 'label' => 'Box File', 'unit_price' => 4000],
            ['keywords' => ['casio', 'calculator', 'kikokotoo'], 'label' => 'CASIO Scientific Calculator', 'unit_price' => 40000],
            ['keywords' => ['mathematical set', 'seti ya hesabu'], 'label' => 'Mathematical Set', 'unit_price' => 8000],
        ];
    }
}
