<?php

function sanitize_story_content(string $html): string
{
    $allowed_tags = [
        "p",
        "br",
        "strong",
        "em",
        "h3",
        "h4",
        "ul",
        "ol",
        "li",
        "a",
        "img",
        "span",
        "figure",
        "figcaption"
    ];

    $allowed_attributes = [
        "a" => ["href", "target", "rel"],
        "img" => ["src", "alt", "loading"],
        "span" => ["style"],
    ];

    $dom = new DOMDocument();

    libxml_use_internal_errors(true);

    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="story-content">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    libxml_clear_errors();

    $container = $dom->getElementById("story-content");

    if (!$container) {
        return "";
    }

    $clean = "";

    foreach ($container->childNodes as $node) {
        $clean .= sanitize_story_node(
            $node,
            $allowed_tags,
            $allowed_attributes
        );
    }

    return normalize_story_html($clean);
}

function sanitize_story_node(
    DOMNode $node,
    array $allowed_tags,
    array $allowed_attributes
): string {
    if ($node->nodeType === XML_TEXT_NODE) {
        return htmlspecialchars(
            $node->nodeValue,
            ENT_QUOTES | ENT_SUBSTITUTE,
            "UTF-8"
        );
    }

    if ($node->nodeType !== XML_ELEMENT_NODE) {
        return "";
    }

    $tag = strtolower($node->nodeName);

    /*
     * Browsers may generate these tags inside contenteditable.
     * Convert them to the canonical tags used by the site.
     */
    if ($tag === "b") {
        return "<strong>" .
            sanitize_story_children($node, $allowed_tags, $allowed_attributes) .
            "</strong>";
    }

    if ($tag === "i") {
        return "<em>" .
            sanitize_story_children($node, $allowed_tags, $allowed_attributes) .
            "</em>";
    }

    /*
     * A browser may use <div> for editing lines. Treat each top-level
     * div as a paragraph instead of simply stripping the div away.
     */
    if ($tag === "div") {
        $children = sanitize_story_children(
            $node,
            $allowed_tags,
            $allowed_attributes
        );

        /*
         * Browsers commonly represent an intentional blank line inside
         * contenteditable as <div><br></div>. Preserve that blank line
         * using the canonical <p><br></p> representation.
         */
        if (trim($children) === "<br>" || trim($children) === "<br/>") {
            return "<p><br></p>";
        }

        /*
         * A genuinely empty editing block has no content at all, so it
         * can safely be discarded.
         */
        if (trim($children) === "") {
            return "";
        }

        return "<p>" . $children . "</p>";
    }

    if ($tag === "br") {
        return "<br>";
    }

    if (!in_array($tag, $allowed_tags, true)) {
        return sanitize_story_children(
            $node,
            $allowed_tags,
            $allowed_attributes
        );
    }

    if ($tag === "img") {
        $src = $node->getAttribute("src");
        $alt = trim($node->getAttribute("alt"));

        if (str_starts_with($src, "../uploads/stories/")) {
            $src = "/" . ltrim(substr($src, 3), "/");
        }

        if (
            $alt === "" ||
            !preg_match(
                '#^/uploads/stories/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp|gif)$#i',
                $src
            )
        ) {
            return "";
        }

        return '<img src="' .
            htmlspecialchars($src, ENT_QUOTES, "UTF-8") .
            '" alt="' .
            htmlspecialchars($alt, ENT_QUOTES, "UTF-8") .
            '" loading="lazy">';
    }

    if ($tag === "a") {
        $href = $node->getAttribute("href");

        if (!preg_match('#^(https?://|mailto:)#i', $href)) {
            $href = "#";
        }

        return '<a href="' .
            htmlspecialchars($href, ENT_QUOTES, "UTF-8") .
            '" target="_blank" rel="noopener noreferrer">' .
            sanitize_story_children(
                $node,
                $allowed_tags,
                $allowed_attributes
            ) .
            '</a>';
    }

    if ($tag === "span") {
        $style = trim($node->getAttribute("style"));
        if (!preg_match('/^font-size\s*:\s*(8|9|1[0-9]|[2-6][0-9]|70|71|72)px\s*;?$/i', $style)) {
            return sanitize_story_children(
                $node,
                $allowed_tags,
                $allowed_attributes
            );
        }
        $size = preg_replace('/[^0-9]/', '', $style);
        return '<span style="font-size:' . $size . 'px">' .
            sanitize_story_children(
                $node,
                $allowed_tags,
                $allowed_attributes
            ) .
            '</span>';
    }

    $attributes = "";

    if (isset($allowed_attributes[$tag])) {
        foreach ($allowed_attributes[$tag] as $attribute) {
            if (!$node->hasAttribute($attribute)) {
                continue;
            }

            $value = $node->getAttribute($attribute);

            if ($attribute === "loading" && $value !== "lazy") {
                continue;
            }

            $attributes .= " " .
                $attribute .
                '="' .
                htmlspecialchars($value, ENT_QUOTES, "UTF-8") .
                '"';
        }
    }

    return "<" . $tag . $attributes . ">" .
        sanitize_story_children(
            $node,
            $allowed_tags,
            $allowed_attributes
        ) .
        "</" . $tag . ">";
}

function sanitize_story_children(
    DOMNode $node,
    array $allowed_tags,
    array $allowed_attributes
): string {
    $output = "";

    foreach ($node->childNodes as $child) {
        $output .= sanitize_story_node(
            $child,
            $allowed_tags,
            $allowed_attributes
        );
    }

    return $output;
}

function normalize_story_html(string $html): string
{
    /*
     * Keep intentional blank paragraphs. They are represented canonically
     * as <p><br></p>.
     *
     * Only genuinely empty paragraphs are removed.
     */
    $html = preg_replace(
        '#<p>\\s*</p>#i',
        "",
        $html
    );

    /*
     * Keep individual <br> elements intact. Do not collapse them here:
     * repeated line breaks can be intentional editor content, while blank
     * paragraphs are already represented separately as <p><br></p>.
     */

    return trim($html);
}
