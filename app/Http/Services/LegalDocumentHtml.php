<?php

namespace App\Http\Services;

/** Rebuild a formatting-only fragment; never pass submitted HTML directly to a view. */
class LegalDocumentHtml
{
    public static function clean(string $html): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
            $body = $dom->getElementsByTagName('body')->item(0);
            return $body ? self::children($body) : '';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function children(\DOMNode $parent): string
    {
        $html = '';
        foreach ($parent->childNodes as $node) {
            if ($node instanceof \DOMText) { $html .= e($node->nodeValue); continue; }
            if (!$node instanceof \DOMElement) continue;
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script','style','iframe','object','embed','svg','math','template','form','input','button','textarea','select','img','video','audio','link','meta','base'], true)) continue;
            $inside = self::children($node);
            if (!in_array($tag, ['p','div','br','hr','h1','h2','h3','h4','h5','h6','strong','b','em','i','u','s','strike','span','font','ul','ol','li','blockquote','pre','code','table','thead','tbody','tfoot','tr','th','td','a','sup','sub'], true)) {
                $html .= $inside; continue;
            }
            $attributes = '';
            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                if (preg_match('~^(https?://|mailto:|tel:)[^\x00-\x20]+$~iD', $href)) {
                    $attributes .= ' href="'.e($href).'" rel="noopener noreferrer"';
                }
            }
            if (in_array($tag, ['td','th'], true)) {
                foreach (['colspan','rowspan'] as $key) {
                    $value = $node->getAttribute($key);
                    if (ctype_digit($value) && (int) $value > 0 && (int) $value <= 50) $attributes .= ' '.$key.'="'.$value.'"';
                }
            }
            $styles = [];
            foreach (explode(';', $node->getAttribute('style')) as $declaration) {
                $parts = explode(':', $declaration, 2);
                if (count($parts) !== 2) continue;
                [$property, $value] = array_map('trim', $parts);
                $property = strtolower($property);
                $valid = match ($property) {
                    'color', 'background-color' => preg_match('/^(#[a-f0-9]{3,8}|[a-z]{1,20}|rgba?\\([0-9.,% ]+\\))$/iD', $value),
                    'text-align' => in_array($value, ['left','right','center','justify'], true),
                    'font-weight' => preg_match('/^(normal|bold|[1-9]00)$/D', $value),
                    'font-style' => in_array($value, ['normal','italic'], true),
                    'text-decoration' => in_array($value, ['none','underline','line-through'], true),
                    'font-size' => preg_match('/^([1-9]|[1-6][0-9]|7[0-2])(px|pt)$/D', $value),
                    'margin-left', 'padding-left' => preg_match('/^[0-9]{1,2}(px|pt)$/D', $value),
                    default => false,
                };
                if ($valid) $styles[] = $property.':'.$value;
            }
            if ($tag === 'font' && preg_match('/^(#[a-f0-9]{3,8}|[a-z]{1,20})$/iD', $node->getAttribute('color'))) {
                $styles[] = 'color:'.$node->getAttribute('color');
            }
            if ($styles) $attributes .= ' style="'.e(implode(';', $styles)).'"';
            $html .= '<'.$tag.$attributes.'>'.$inside.(in_array($tag, ['br','hr'], true) ? '' : '</'.$tag.'>');
        }
        return $html;
    }
}
