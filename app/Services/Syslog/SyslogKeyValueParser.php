<?php

namespace App\Services\Syslog;

class SyslogKeyValueParser
{
    /**
     * Extract structured key-value pairs from a message string.
     *
     * @return array<string, mixed>
     */
    public function extract(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        // 1. Check for CEF (Common Event Format)
        if (str_starts_with($text, 'CEF:')) {
            $cefData = $this->extractCef($text);
            if (!empty($cefData)) {
                return $cefData;
            }
        }

        // 2. Check for Full or Embedded JSON Object
        $jsonData = $this->extractJson($text);
        if (!empty($jsonData)) {
            return $jsonData;
        }

        $attributes = [];

        // 3. Extract Logfmt / Key-Value pairs with "="
        // Matches key="value with spaces", key='single quotes', or key=unquoted_value
        $kvPattern = '/(?:^|[\s,;(\[{])([a-zA-Z0-9_\.\-\@]+)\s*=\s*(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|([^\s,;()\]\}]+))/u';

        if (preg_match_all($kvPattern, $text, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $key = $match[1];

                // Value could be in match[2] (double quote), match[3] (single quote), or match[4] (unquoted)
                $rawValue = '';
                if (isset($match[2]) && $match[2] !== '') {
                    $rawValue = $match[2];
                } elseif (isset($match[3]) && $match[3] !== '') {
                    $rawValue = $match[3];
                } elseif (isset($match[4])) {
                    $rawValue = $match[4];
                }

                // Handle escaped quotes
                $unescaped = stripcslashes($rawValue);
                $attributes[$key] = $this->castValue($unescaped);
            }
        }

        // 4. Extract Key-Colon-Value pairs (e.g., user: admin, ip: 1.2.3.4)
        if (empty($attributes)) {
            $colonPattern = '/(?:^|[\s,;])([a-zA-Z0-9_\.\-]+)\s*:\s*(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|([a-zA-Z0-9_\.\-\@\/]+))(?=[\s,;]|$)/u';
            if (preg_match_all($colonPattern, $text, $matches, PREG_SET_ORDER) > 0) {
                // Only consider if we find at least 2 distinct key-value pairs or a known structured format
                $extracted = [];
                foreach ($matches as $match) {
                    $key = $match[1];
                    $val = '';
                    if (isset($match[2]) && $match[2] !== '') {
                        $val = $match[2];
                    } elseif (isset($match[3]) && $match[3] !== '') {
                        $val = $match[3];
                    } elseif (isset($match[4])) {
                        $val = $match[4];
                    }

                    if ($val !== '') {
                        $extracted[$key] = $this->castValue(stripcslashes($val));
                    }
                }

                if (count($extracted) >= 2) {
                    $attributes = $extracted;
                }
            }
        }

        return $attributes;
    }

    /**
     * Extract structured data from RFC 5424 structured data string.
     * E.g. [exampleSDID@32473 iut="3" eventSource="Application"][origin ip="1.2.3.4"]
     *
     * @return array<string, array<string, mixed>>
     */
    public function extractFromStructuredData(string $structuredData): array
    {
        $structuredData = trim($structuredData);

        if ($structuredData === '' || $structuredData === '-') {
            return [];
        }

        $result = [];
        $sdPattern = '/\[([a-zA-Z0-9_\.\-\@]+)((?:\s+[a-zA-Z0-9_\.\-\@]+="[^"]*")*)\]/u';

        if (preg_match_all($sdPattern, $structuredData, $blocks, PREG_SET_ORDER) > 0) {
            foreach ($blocks as $block) {
                $sdId = $block[1];
                $paramsString = $block[2];
                $params = [];

                if (preg_match_all('/([a-zA-Z0-9_\.\-\@]+)="([^"]*)"/u', $paramsString, $paramMatches, PREG_SET_ORDER) > 0) {
                    foreach ($paramMatches as $pm) {
                        $pKey = $pm[1];
                        $pVal = $pm[2];
                        $params[$pKey] = $this->castValue($pVal);
                    }
                }

                $result[$sdId] = $params;
            }
        }

        return $result;
    }

    /**
     * Extract CEF (Common Event Format) messages.
     *
     * @return array<string, mixed>
     */
    protected function extractCef(string $text): array
    {
        $pattern = '/^CEF:\s*(\d+)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*(.*)$/si';

        if (preg_match($pattern, $text, $matches) === 1) {
            $data = [
                'cef_version' => $this->castValue($matches[1]),
                'cef_vendor' => trim($matches[2]),
                'cef_product' => trim($matches[3]),
                'cef_product_version' => trim($matches[4]),
                'cef_signature_id' => trim($matches[5]),
                'cef_name' => trim($matches[6]),
                'cef_severity' => $this->castValue($matches[7]),
            ];

            // Extension key-values
            $extension = trim($matches[8]);
            if ($extension !== '') {
                $extPattern = '/([a-zA-Z0-9_\.\-]+)\s*=\s*(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|([^\s]+))/u';
                if (preg_match_all($extPattern, $extension, $extMatches, PREG_SET_ORDER) > 0) {
                    foreach ($extMatches as $em) {
                        $k = $em[1];
                        $v = '';
                        if (isset($em[2]) && $em[2] !== '') {
                            $v = $em[2];
                        } elseif (isset($em[3]) && $em[3] !== '') {
                            $v = $em[3];
                        } elseif (isset($em[4])) {
                            $v = $em[4];
                        }
                        $data[$k] = $this->castValue(stripcslashes($v));
                    }
                }
            }

            return $data;
        }

        return [];
    }

    /**
     * Extract JSON if present.
     *
     * @return array<string, mixed>
     */
    protected function extractJson(string $text): array
    {
        // 1. Whole string is JSON
        if ((str_starts_with($text, '{') && str_ends_with($text, '}')) || (str_starts_with($text, '[') && str_ends_with($text, ']'))) {
            try {
                $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    /** @var array<string, mixed> $decoded */
                    return $decoded;
                }
            } catch (\Throwable) {
                // Not valid JSON
            }
        }

        // 2. Embedded JSON in text (e.g. "prefix: {...}")
        if (preg_match('/(\{.*\})/s', $text, $jsonMatches) === 1) {
            try {
                $decoded = json_decode($jsonMatches[1], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded) && !empty($decoded)) {
                    /** @var array<string, mixed> $decoded */
                    return $decoded;
                }
            } catch (\Throwable) {
                // Not valid JSON
            }
        }

        return [];
    }

    /**
     * Cast string values into native types (int, float, bool, null) where appropriate.
     */
    protected function castValue(string $val): mixed
    {
        $trimmed = trim($val);

        if (strcasecmp($trimmed, 'true') === 0) {
            return true;
        }

        if (strcasecmp($trimmed, 'false') === 0) {
            return false;
        }

        if (strcasecmp($trimmed, 'null') === 0) {
            return null;
        }

        // Integer (e.g. 100, -5), excluding strings with leading zeros like "0123" unless "0"
        if (preg_match('/^-?(?:0|[1-9]\d*)$/', $trimmed) === 1) {
            return (int) $trimmed;
        }

        // Float (e.g. 12.5, -0.75)
        if (preg_match('/^-?(?:0|[1-9]\d*)\.\d+$/', $trimmed) === 1) {
            return (float) $trimmed;
        }

        return $val;
    }
}
