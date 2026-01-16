<?php
/**
 * NOK Color System Reference
 *
 * Standalone page for viewing all available color classes.
 * Load via: /wp-content/themes/nok-2025-v1/assets/css/color-reference.php
 */

// Bootstrap WordPress to access theme functions
require_once dirname(__DIR__, 5) . '/wp-load.php';

use NOK2025\V1\Assets;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NOK Color Reference</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./color_tests-v2.css">
    <style>
        :root {
            --grid-gap: 0.364em;
            --card-min: 11em;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 1em;
            font-family: "JetBrains Mono", monospace;
            font-size: 14px; /* Base size - change this to scale everything */
            line-height: 1.4;
            background: #f5f5f5;
            color: #333;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #1a1a2e;
                color: #e0e0e0;
            }
        }

        h1 {
            font-size: 1.5em;
            font-weight: 600;
            margin: 0 0 0.5em;
        }

        .intro {
            margin-bottom: 1em;
            padding: 0.75em;
            background: rgba(0,0,0,0.05);
            border-radius: 0.364em;
            font-size: 0.909em;
        }

        @media (prefers-color-scheme: dark) {
            .intro {
                background: rgba(255,255,255,0.05);
            }
        }

        .intro code {
            background: rgba(0,0,0,0.1);
            padding: 0.125em 0.25em;
            border-radius: 0.182em;
        }

        @media (prefers-color-scheme: dark) {
            .intro code {
                background: rgba(255,255,255,0.1);
            }
        }

        /* Section headers */
        .section {
            margin-bottom: 1.5em;
        }

        .section-title {
            font-size: 1.1em;
            font-weight: 600;
            margin: 0 0 0.5em;
            padding-bottom: 0.25em;
            border-bottom: 1px solid rgba(128,128,128,0.3);
            display: flex;
            align-items: center;
            gap: 0.5em;
        }

        .section-title .badge {
            font-size: 0.818em;
            font-weight: 500;
            padding: 0.125em 0.375em;
            border-radius: 0.273em;
            background: rgba(0,0,0,0.1);
        }

        /* Color grids */
        .color-grid {
            display: grid;
            gap: var(--grid-gap);
            grid-template-columns: repeat(auto-fill, minmax(var(--card-min), 1fr));
        }

        .color-grid--wide {
            --card-min: 13em;
        }

        /* Individual color cards */
        .color-card {
            position: relative;
            aspect-ratio: 1.5;
            min-height: 5.5em;
            border-radius: 0.364em;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 0.545em;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
            border: 1px solid rgba(128,128,128,0.15);
        }

        .color-card:hover {
            transform: translateY(-0.182em);
            box-shadow: 0 0.364em 1.09em rgba(0,0,0,0.15);
            z-index: 10;
        }

        .color-card:active {
            transform: translateY(0);
        }

        .color-card__name {
            font-size: 0.818em;
            font-weight: 500;
            word-break: break-all;
            line-height: 1.2;
            opacity: 0.9;
        }

        .color-card__class {
            font-size: 0.727em;
            opacity: 0.7;
            margin-top: 0.182em;
        }

        .color-card__hex {
            position: absolute;
            top: 0.364em;
            right: 0.364em;
            font-size: 0.727em;
            opacity: 0.6;
            background: rgba(0,0,0,0.2);
            padding: 0.091em 0.364em;
            border-radius: 0.182em;
        }

        /* Border color cards need special treatment */
        .color-card--border {
            background: transparent !important;
            border-width: 0.273em;
            border-style: solid;
        }

        .color-card--border .color-card__name,
        .color-card--border .color-card__class {
            color: inherit;
        }

        /* Text color cards */
        .color-card--text {
            background: #fff !important;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        @media (prefers-color-scheme: dark) {
            .color-card--text {
                background: #1a1a2e !important;
            }
        }

        .color-card--text .color-card__name {
            font-size: 1em;
            font-weight: 600;
        }

        /* Variants row */
        .variant-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.182em;
            margin-bottom: var(--grid-gap);
        }

        .variant-group .color-card {
            aspect-ratio: 2;
            min-height: 4.545em;
        }

        .variant-group .color-card--main {
            grid-column: 1 / -1;
            aspect-ratio: 4;
        }

        /* Color family groups */
        .color-family {
            display: flex;
            flex-direction: column;
            gap: 0.182em;
            background: rgba(128,128,128,0.1);
            padding: 0.364em;
            border-radius: 0.545em;
        }

        .color-family__header {
            font-size: 0.909em;
            font-weight: 600;
            padding: 0.364em 0.545em;
            margin-bottom: 0.182em;
        }

        .color-family__row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.182em;
        }

        /* Utility classes section */
        .utility-grid {
            display: grid;
            gap: var(--grid-gap);
            grid-template-columns: repeat(auto-fill, minmax(16.4em, 1fr));
        }

        .utility-card {
            padding: 0.727em;
            border-radius: 0.364em;
            border: 1px solid rgba(128,128,128,0.2);
            font-size: 0.909em;
        }

        .utility-card code {
            display: block;
            font-weight: 600;
            margin-bottom: 0.364em;
            &.inline {
                display: inline-block;
            }
        }

        .utility-card .desc {
            opacity: 0.7;
            font-size: 0.818em;
        }

        /* Icon demo */
        .icon-demo {
            display: flex;
            gap: 0.5em;
            align-items: center;
            margin-top: 0.5em;
        }
        .icon-demo svg {
            width: 2em;
            height: 2em;
        }

        /* Alpha demo */
        .alpha-demo {
            display: flex;
            gap: 0.25em;
            margin-top: 0.5em;
        }
        .alpha-demo__swatch {
            width: 3em;
            height: 2em;
            border-radius: 0.25em;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7em;
            font-weight: 500;
        }

        /* State demo */
        .state-demo {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.25em;
            margin-top: 0.5em;
        }
        .state-demo__item {
            padding: 0.5em;
            border-radius: 0.25em;
            text-align: center;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.1s ease, opacity 0.1s ease;
        }
        .state-demo__item:hover {
            opacity: 0.85;
        }
        .state-demo__item:active {
            transform: scale(0.98);
        }

        /* Copy feedback */
        .copied {
            position: fixed;
            bottom: 1em;
            right: 1em;
            background: #333;
            color: #fff;
            padding: 0.5em 1em;
            border-radius: 0.364em;
            font-size: 1em;
            opacity: 0;
            transform: translateY(0.909em);
            transition: all 0.2s ease;
            pointer-events: none;
        }

        .copied.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.364em;
            margin-bottom: 1em;
            border-bottom: 1px solid rgba(128,128,128,0.3);
            padding-bottom: 0.364em;
        }

        .tab {
            padding: 0.375em 0.75em;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            border-radius: 0.364em 0.364em 0 0;
            background: transparent;
            border: none;
            color: inherit;
            opacity: 0.6;
            transition: all 0.15s ease;
        }

        .tab:hover {
            opacity: 0.8;
            background: rgba(128,128,128,0.1);
        }

        .tab.active {
            opacity: 1;
            background: rgba(128,128,128,0.15);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Legend */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
            font-size: 0.818em;
            margin-top: 1em;
            padding-top: 0.5em;
            border-top: 1px solid rgba(128,128,128,0.2);
            opacity: 0.7;
        }

        .legend span {
            display: flex;
            align-items: center;
            gap: 0.364em;
        }

        .legend-swatch {
            width: 1.09em;
            height: 1.09em;
            border-radius: 0.182em;
            border: 1px solid rgba(128,128,128,0.3);
        }
    </style>
</head>
<body>

<h1>NOK Color System Reference</h1>

<div class="intro">
    <strong>Class pattern:</strong>
    <code>.nok-{type}-{color}</code> or <code>.nok-{type}-{color}--{variant}</code><br>
    <strong>Types:</strong> <code>bg</code> (background), <code>text</code>, <code>fill</code> (SVG)<br>
    <strong>Variants:</strong> <code>--darker</code>, <code>--lighter</code><br>
    <strong>Tip:</strong> Click any card to copy the class name
</div>

<div class="tabs">
    <button class="tab active" data-tab="backgrounds">Backgrounds</button>
    <button class="tab" data-tab="text">Text Colors</button>
    <button class="tab" data-tab="utilities">Utilities</button>
    <button class="tab" data-tab="variables">CSS Variables</button>
</div>

<!-- BACKGROUNDS TAB -->
<div id="backgrounds" class="tab-content active">
    <div class="section">
        <div class="section-title">Blues <span class="badge">Primary palette</span></div>
        <div class="color-grid">
            <div class="color-family">
                <div class="color-family__header">lightblue</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-lightblue--darker" data-class="nok-bg-lightblue--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-lightblue" data-class="nok-bg-lightblue">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-lightblue--lighter" data-class="nok-bg-lightblue--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">darkblue</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-darkblue--darker" data-class="nok-bg-darkblue--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-darkblue" data-class="nok-bg-darkblue">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-darkblue--lighter" data-class="nok-bg-darkblue--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">darkerblue</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-darkerblue--darker" data-class="nok-bg-darkerblue--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-darkerblue" data-class="nok-bg-darkerblue">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-darkerblue--lighter" data-class="nok-bg-darkerblue--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">darkestblue</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-darkestblue--darker" data-class="nok-bg-darkestblue--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-darkestblue" data-class="nok-bg-darkestblue">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-darkestblue--lighter" data-class="nok-bg-darkestblue--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Greens & Teals</div>
        <div class="color-grid">
            <div class="color-family">
                <div class="color-family__header">green</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-green--darker" data-class="nok-bg-green--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-green" data-class="nok-bg-green">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-green--lighter" data-class="nok-bg-green--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">greenyellow</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-greenyellow--darker" data-class="nok-bg-greenyellow--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-greenyellow" data-class="nok-bg-greenyellow">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-greenyellow--lighter" data-class="nok-bg-greenyellow--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">greenblue</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-greenblue--darker" data-class="nok-bg-greenblue--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-greenblue" data-class="nok-bg-greenblue">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-greenblue--lighter" data-class="nok-bg-greenblue--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">lightgreenblue</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-lightgreenblue--darker" data-class="nok-bg-lightgreenblue--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-lightgreenblue" data-class="nok-bg-lightgreenblue">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-lightgreenblue--lighter" data-class="nok-bg-lightgreenblue--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Accents & Utility</div>
        <div class="color-grid">
            <div class="color-family">
                <div class="color-family__header">yellow</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-yellow--darker" data-class="nok-bg-yellow--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-yellow" data-class="nok-bg-yellow">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-yellow--lighter" data-class="nok-bg-yellow--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">error</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-error--darker" data-class="nok-bg-error--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-error" data-class="nok-bg-error">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-error--lighter" data-class="nok-bg-error--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">body</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-body--darker" data-class="nok-bg-body--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-body" data-class="nok-bg-body">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-body--lighter" data-class="nok-bg-body--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">lightgrey</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-lightgrey--darker" data-class="nok-bg-lightgrey--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-lightgrey" data-class="nok-bg-lightgrey">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-lightgrey--lighter" data-class="nok-bg-lightgrey--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Neutrals</div>
        <div class="color-grid">
            <div class="color-family">
                <div class="color-family__header">white</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-white--darker" data-class="nok-bg-white--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-white" data-class="nok-bg-white">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-white--lighter" data-class="nok-bg-white--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
            <div class="color-family">
                <div class="color-family__header">black</div>
                <div class="color-family__row">
                    <div class="color-card nok-bg-black--darker" data-class="nok-bg-black--darker">
                        <span class="color-card__name">--darker</span>
                    </div>
                    <div class="color-card nok-bg-black" data-class="nok-bg-black">
                        <span class="color-card__name">base</span>
                    </div>
                    <div class="color-card nok-bg-black--lighter" data-class="nok-bg-black--lighter">
                        <span class="color-card__name">--lighter</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TEXT COLORS TAB -->
<div id="text" class="tab-content">
    <div class="section">
        <div class="section-title">Text Colors <span class="badge">Use on any background</span></div>
        <div class="color-grid color-grid--wide">
            <div class="color-card color-card--text nok-text-lightblue" data-class="nok-text-lightblue">
                <span class="color-card__name">lightblue</span>
                <span class="color-card__class">.nok-text-lightblue</span>
            </div>
            <div class="color-card color-card--text nok-text-darkblue" data-class="nok-text-darkblue">
                <span class="color-card__name">darkblue</span>
                <span class="color-card__class">.nok-text-darkblue</span>
            </div>
            <div class="color-card color-card--text nok-text-darkerblue" data-class="nok-text-darkerblue">
                <span class="color-card__name">darkerblue</span>
                <span class="color-card__class">.nok-text-darkerblue</span>
            </div>
            <div class="color-card color-card--text nok-text-darkestblue" data-class="nok-text-darkestblue">
                <span class="color-card__name">darkestblue</span>
                <span class="color-card__class">.nok-text-darkestblue</span>
            </div>
            <div class="color-card color-card--text nok-text-green" data-class="nok-text-green">
                <span class="color-card__name">green</span>
                <span class="color-card__class">.nok-text-green</span>
            </div>
            <div class="color-card color-card--text nok-text-greenblue" data-class="nok-text-greenblue">
                <span class="color-card__name">greenblue</span>
                <span class="color-card__class">.nok-text-greenblue</span>
            </div>
            <div class="color-card color-card--text nok-text-yellow" data-class="nok-text-yellow">
                <span class="color-card__name">yellow</span>
                <span class="color-card__class">.nok-text-yellow</span>
            </div>
            <div class="color-card color-card--text nok-text-error" data-class="nok-text-error">
                <span class="color-card__name">error</span>
                <span class="color-card__class">.nok-text-error</span>
            </div>
            <div class="color-card color-card--text nok-text-black" data-class="nok-text-black">
                <span class="color-card__name">black</span>
                <span class="color-card__class">.nok-text-black</span>
            </div>
            <div class="color-card color-card--text nok-text-lightgrey" data-class="nok-text-lightgrey">
                <span class="color-card__name">lightgrey</span>
                <span class="color-card__class">.nok-text-lightgrey</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Text on Colored Backgrounds <span class="badge">Auto-contrast</span></div>
        <div class="color-grid color-grid--wide">
            <div class="color-card nok-bg-lightblue nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-lightblue</span>
            </div>
            <div class="color-card nok-bg-darkblue nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-darkblue</span>
            </div>
            <div class="color-card nok-bg-darkerblue nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-darkerblue</span>
            </div>
            <div class="color-card nok-bg-darkestblue nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-darkestblue</span>
            </div>
            <div class="color-card nok-bg-green nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-green</span>
            </div>
            <div class="color-card nok-bg-greenblue nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-greenblue</span>
            </div>
            <div class="color-card nok-bg-yellow nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-yellow</span>
            </div>
            <div class="color-card nok-bg-error nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-error</span>
            </div>
            <div class="color-card nok-bg-black nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-black</span>
            </div>
            <div class="color-card nok-bg-lightgrey nok-text-contrast" data-class="nok-text-contrast">
                <span class="color-card__name">.nok-text-contrast</span>
                <span class="color-card__class">on .nok-bg-lightgrey</span>
            </div>
        </div>
    </div>
</div>

<!-- UTILITIES TAB -->
<div id="utilities" class="tab-content">
    <div class="section">
        <div class="section-title">Utility Classes</div>
        <div class="utility-grid">
            <div class="utility-card nok-bg-darkblue nok-text-contrast">
                <code>.nok-text-contrast</code>
                <span class="desc">Automatically picks light/dark text based on background color</span>
                <div class="nok-bg-yellow nok-text-contrast" style="padding: 0.25em 0.5em; border-radius: 0.25em; margin-top: 0.25em;">And here on <code class="inline">nok-bg-yellow</code></div>
            </div>
            <div class="utility-card nok-bg-darkblue nok-text-contrast">
                <code>.nok-bg-contrast</code>
                <span class="desc">Child inherits contrasting background from parent:</span>
                <div class="nok-bg-contrast nok-text-contrast" style="padding: 0.25em 0.5em; border-radius: 0.25em; margin-top: 0.25em;">Contrasts with the <code class="inline">nok-bg-darkblue</code> parent block (and uses <code class="inline">nok-text-contrast</code> for the text)</div>
            </div>
            <div class="utility-card">
                <code>.nok-bg-transparent</code>
                <span class="desc">Transparent background with hover/active states disabled</span>
            </div>
            <div class="utility-card nok-bg-darkblue nok-text-contrast">
                <code>.nok-fill-contrast</code>
                <span class="desc">SVG fill using contrast color (for icons on colored bg)</span>
                <div class="icon-demo">
                    <span class="nok-fill-contrast"><?= Assets::getIcon('logo_nok') ?></span>
                    <span class="nok-fill-contrast"><?= Assets::getIcon('ui_arrow-right') ?></span>
                    <span class="nok-fill-contrast"><?= Assets::getIcon('ui_arrow-up-long') ?></span>
                    <span class="nok-fill-contrast"><?= Assets::getIcon('ui_calendar') ?></span>
                    <span class="nok-fill-contrast"><?= Assets::getIcon('nok_darmen') ?></span>
                </div>
                <div class="nok-bg-yellow nok-text-contrast" style="padding: 0.25em 0.5em; border-radius: 0.25em; margin-top: 0.25em;">
                    <div class="icon-demo">
                        <span class="nok-fill-contrast"><?= Assets::getIcon('logo_nok') ?></span>
                        <span class="nok-fill-contrast"><?= Assets::getIcon('ui_arrow-right') ?></span>
                        <span class="nok-fill-contrast"><?= Assets::getIcon('ui_arrow-up-long') ?></span>
                        <span class="nok-fill-contrast"><?= Assets::getIcon('ui_calendar') ?></span>
                        <span class="nok-fill-contrast"><?= Assets::getIcon('nok_darmen') ?></span>
                    </div>
                </div>
            </div>
            <div class="utility-card nok-bg-darkblue nok-text-contrast nok-dark-bg-body">
                <code>.nok-dark-*</code>
                <span class="desc">Prefix for dark-mode-only variants (e.g., .nok-dark-bg-body)</span>
                <div class="nok-dark-bg-yellow nok-text-contrast" style="padding: 0.25em 0.5em; border-radius: 0.25em; margin-top: 0.25em;">Using <code class="inline">nok-dark-bg-yellow</code>, I should be yellow in dark mode</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Alpha/Transparency Control</div>
        <div class="utility-grid">
            <div class="utility-card">
                <code>--bg-alpha-value</code>
                <span class="desc">Local: Control background opacity on element</span>
                <div class="alpha-demo">
                    <div class="alpha-demo__swatch nok-bg-darkblue nok-text-contrast" style="--bg-alpha-value: 1;">1.0</div>
                    <div class="alpha-demo__swatch nok-bg-darkblue nok-text-contrast" style="--bg-alpha-value: 0.75;">0.75</div>
                    <div class="alpha-demo__swatch nok-bg-darkblue nok-text-contrast" style="--bg-alpha-value: 0.5;">0.5</div>
                    <div class="alpha-demo__swatch nok-bg-darkblue nok-text-contrast" style="--bg-alpha-value: 0.25;">0.25</div>
                </div>
            </div>
            <div class="utility-card">
                <code>--text-alpha-value</code>
                <span class="desc">Local: Control text opacity on element</span>
                <div class="alpha-demo">
                    <div class="alpha-demo__swatch nok-text-darkblue" style="--text-alpha-value: 1; font-weight: 700;">1.0</div>
                    <div class="alpha-demo__swatch nok-text-darkblue" style="--text-alpha-value: 0.75; font-weight: 700;">0.75</div>
                    <div class="alpha-demo__swatch nok-text-darkblue" style="--text-alpha-value: 0.5; font-weight: 700;">0.5</div>
                    <div class="alpha-demo__swatch nok-text-darkblue" style="--text-alpha-value: 0.25; font-weight: 700;">0.25</div>
                </div>
            </div>
            <div class="utility-card">
                <code>--global-bg-alpha-value</code>
                <span class="desc">Global override (auto-set by prefers-reduced-transparency)</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">State Colors <span class="badge">Built-in</span></div>
        <p>The color system uses local CSS custom properties set by the background class. When you apply a class like <code class="inline">nok-bg-darkblue</code>, it sets the background color
        and defines local CSS variables for state variants: <code class="inline">--bg-color--hover</code>, <code class="inline">--bg-color--active</code>,
            <code class="inline">--bg-color--grayscale</code> and <code class="inline">--bg-color--contrast</code>. The same applies to <code class="inline">nok-text-*</code> classes.</p>
        <p>Examples:</p>
        <div class="utility-grid">
            <div class="utility-card">
                <code>nok-bg-darkblue</code>
                <span class="desc">Base + auto-generated state variants</span>
                <div class="state-demo nok-text-contrast">
                    <div class="state-demo__item nok-bg-darkblue nok-text-contrast"><code>--bg-color</code></div>
                    <div class="state-demo__item nok-bg-darkblue nok-text-contrast" style="background: var(--bg-color--hover);"><code>--bg-color--hover</code></div>
                    <div class="state-demo__item nok-bg-darkblue nok-text-contrast" style="background: var(--bg-color--active);"><code>--bg-color--active</code></div>
                    <div class="state-demo__item nok-bg-darkblue nok-text-contrast" style="background: var(--bg-color--grayscale);"><code>--bg-color--grayscale</code></div>
                    <div class="state-demo__item nok-bg-darkblue nok-text-contrast" style="background: var(--bg-color--contrast); color: var(--bg-color);"><code>--bg-color--contrast</code></div>
                </div>
            </div>
            <div class="utility-card">
                <code>nok-bg-lightblue</code>
                <span class="desc">Base + auto-generated state variants</span>
                <div class="state-demo nok-text-contrast">
                    <div class="state-demo__item nok-bg-lightblue nok-text-contrast"><code>--bg-color</code></div>
                    <div class="state-demo__item nok-bg-lightblue nok-text-contrast" style="background: var(--bg-color--hover);"><code>--bg-color--hover</code></div>
                    <div class="state-demo__item nok-bg-lightblue nok-text-contrast" style="background: var(--bg-color--active);"><code>--bg-color--active</code></div>
                    <div class="state-demo__item nok-bg-lightblue nok-text-contrast" style="background: var(--bg-color--grayscale);"><code>--bg-color--grayscale</code></div>
                    <div class="state-demo__item nok-bg-lightblue nok-text-contrast" style="background: var(--bg-color--contrast); color: var(--bg-color);"><code>--bg-color--contrast</code></div>
                </div>
            </div>
            <div class="utility-card">
                <code>nok-bg-yellow</code>
                <span class="desc">Base + auto-generated state variants</span>
                <div class="state-demo nok-text-contrast">
                    <div class="state-demo__item nok-bg-yellow nok-text-contrast"><code>--bg-color</code></div>
                    <div class="state-demo__item nok-bg-yellow nok-text-contrast" style="background: var(--bg-color--hover);"><code>--bg-color--hover</code></div>
                    <div class="state-demo__item nok-bg-yellow nok-text-contrast" style="background: var(--bg-color--active);"><code>--bg-color--active</code></div>
                    <div class="state-demo__item nok-bg-yellow nok-text-contrast" style="background: var(--bg-color--grayscale);"><code>--bg-color--grayscale</code></div>
                    <div class="state-demo__item nok-bg-yellow nok-text-contrast" style="background: var(--bg-color--contrast); color: var(--bg-color);"><code>--bg-color--contrast</code></div>
                </div>
            </div>
        </div>
        <div class="utility-grid">
            <div class="utility-card">
                <code>nok-text-darkblue</code>
                <span class="desc">Base + auto-generated state variants</span>
                <div class="state-demo nok-text-darkblue">
                    <div class="state-demo__item"><code>--text-color</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--hover);"><code>--text-color--hover</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--active);"><code>--text-color--active</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--grayscale);"><code>--text-color--grayscale</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--contrast); background-color: var(--text-color);"><code>--text-color--contrast</code></div>
                </div>
            </div>
            <div class="utility-card">
                <code>nok-text-lightblue</code>
                <span class="desc">Base + auto-generated state variants</span>
                <div class="state-demo nok-text-lightblue">
                    <div class="state-demo__item"><code>--text-color</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--hover);"><code>--text-color--hover</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--active);"><code>--text-color--active</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--grayscale);"><code>--text-color--grayscale</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--contrast); background-color: var(--text-color);"><code>--text-color--contrast</code></div>
                </div>
            </div>
            <div class="utility-card">
                <code>nok-text-yellow</code>
                <span class="desc">Base + auto-generated state variants</span>
                <div class="state-demo nok-text-yellow">
                    <div class="state-demo__item"><code>--text-color</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--hover);"><code>--text-color--hover</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--active);"><code>--text-color--active</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--grayscale);"><code>--text-color--grayscale</code></div>
                    <div class="state-demo__item" style="color: var(--text-color--contrast); background-color: var(--text-color);"><code>--text-color--contrast</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS VARIABLES TAB -->
<div id="variables" class="tab-content">
    <div class="section">
        <div class="section-title">Available CSS Custom Properties</div>
        <div class="intro">
            All colors expose these CSS variables for direct use:<br><br>
            <code>--nok-{color}</code> — The color value<br>
            <code>--nok-{color}-rgb</code> — RGB components (for rgba())<br>
            <code>--nok-{color}-contrast</code> — Auto light/dark contrast color<br><br>
            <strong>Example:</strong> <code>background: rgba(var(--nok-darkblue-rgb), 0.5);</code>
        </div>

        <div class="utility-grid" style="margin-top: 1em;">
            <div class="utility-card">
                <code>--nok-lightblue</code>
                <span class="desc">#00b0e4</span>
            </div>
            <div class="utility-card">
                <code>--nok-darkblue</code>
                <span class="desc">#14477c</span>
            </div>
            <div class="utility-card">
                <code>--nok-darkerblue</code>
                <span class="desc">#0b2355</span>
            </div>
            <div class="utility-card">
                <code>--nok-darkestblue</code>
                <span class="desc">#00132f</span>
            </div>
            <div class="utility-card">
                <code>--nok-yellow</code>
                <span class="desc">#ffd41f</span>
            </div>
            <div class="utility-card">
                <code>--nok-green</code>
                <span class="desc">#54b085</span>
            </div>
            <div class="utility-card">
                <code>--nok-greenyellow</code>
                <span class="desc">#CCCC33</span>
            </div>
            <div class="utility-card">
                <code>--nok-greenblue</code>
                <span class="desc">#35aba5</span>
            </div>
            <div class="utility-card">
                <code>--nok-lightgreenblue</code>
                <span class="desc">#93e1f4</span>
            </div>
            <div class="utility-card">
                <code>--nok-error</code>
                <span class="desc">#d82510</span>
            </div>
            <div class="utility-card">
                <code>--nok-body</code>
                <span class="desc">#f3f4f9 (light) / #00132f (dark)</span>
            </div>
            <div class="utility-card">
                <code>--nok-white</code>
                <span class="desc">#FFF</span>
            </div>
            <div class="utility-card">
                <code>--nok-black</code>
                <span class="desc">#222</span>
            </div>
            <div class="utility-card">
                <code>--nok-lightgrey</code>
                <span class="desc">#CCC</span>
            </div>
        </div>
    </div>
</div>

<div class="legend">
    <span><span class="legend-swatch nok-bg-lightblue"></span> Primary brand color</span>
    <span><span class="legend-swatch nok-bg-darkblue"></span> Secondary brand color</span>
    <span><span class="legend-swatch nok-bg-yellow"></span> Accent/CTA color</span>
    <span><span class="legend-swatch nok-bg-error"></span> Error/warning states</span>
</div>

<div class="copied" id="copied">Copied!</div>

<script>
// Tab switching
function activateTab(tabId) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    const tab = document.querySelector(`.tab[data-tab="${tabId}"]`);
    const content = document.getElementById(tabId);
    if (tab && content) {
        tab.classList.add('active');
        content.classList.add('active');
    }
}

document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const tabId = tab.dataset.tab;
        activateTab(tabId);
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        history.replaceState(null, '', url);
    });
});

// Restore tab from URL on load
const params = new URLSearchParams(window.location.search);
const savedTab = params.get('tab');
if (savedTab) {
    activateTab(savedTab);
}

// Copy class on click
document.querySelectorAll('[data-class]').forEach(card => {
    card.addEventListener('click', () => {
        const className = card.dataset.class;
        navigator.clipboard.writeText(className).then(() => {
            const copied = document.getElementById('copied');
            copied.textContent = `Copied: ${className}`;
            copied.classList.add('show');
            setTimeout(() => copied.classList.remove('show'), 1500);
        });
    });
});

// Copy CSS variable from state demo items
document.querySelectorAll('.state-demo__item').forEach(item => {
    item.addEventListener('click', () => {
        const code = item.querySelector('code');
        if (code) {
            const varName = code.textContent;
            navigator.clipboard.writeText(`var(${varName})`).then(() => {
                const copied = document.getElementById('copied');
                copied.textContent = `Copied: var(${varName})`;
                copied.classList.add('show');
                setTimeout(() => copied.classList.remove('show'), 1500);
            });
        }
    });
});
</script>

</body>
</html>