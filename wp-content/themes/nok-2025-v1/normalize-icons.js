#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const { optimize } = require('svgo');

const TARGET_SIZE = 16;
const INPUT_DIR = './assets/icons-source';
const OUTPUT_DIR = './assets/icons';

function parseViewBox(viewBox) {
    const [x, y, width, height] = viewBox.split(/\s+/).map(Number);
    return { x, y, width, height };
}

function addTransformGroup(svg) {
    const viewBoxMatch = svg.match(/viewBox=["']([^"']+)["']/);

    if (!viewBoxMatch) {
        console.warn('No viewBox found, cannot normalize');
        return svg;
    }

    const vb = parseViewBox(viewBoxMatch[1]);
    const maxDimension = Math.max(vb.width, vb.height);
    const scale = TARGET_SIZE / maxDimension;

    const scaledWidth = vb.width * scale;
    const scaledHeight = vb.height * scale;

    const offsetX = (TARGET_SIZE - scaledWidth) / 2 - (vb.x * scale);
    const offsetY = (TARGET_SIZE - scaledHeight) / 2 - (vb.y * scale);

    const svgMatch = svg.match(/<svg[^>]*>([\s\S]*)<\/svg>/);
    if (!svgMatch) {
        console.warn('Could not parse SVG structure');
        return svg;
    }

    const content = svgMatch[1];
    const svgOpen = svg.substring(0, svgMatch.index + svgMatch[0].indexOf('>') + 1);

    return `${svgOpen}<g transform="translate(${offsetX} ${offsetY}) scale(${scale})">${content}</g></svg>`;
}

function cleanupSvg(svg) {
    svg = svg.replace(/\s+style="[^"]*"/g, '');
    svg = svg.replace(/\s+fill="(?!none")[^"]*"/g, '');
    svg = svg.replace(/<g>\s*<\/g>/g, '');

    if (svg.match(/<svg[^>]*fill=/)) {
        svg = svg.replace(/(<svg[^>]*)fill="[^"]*"/, '$1fill="currentColor"');
    } else {
        svg = svg.replace(/<svg/, '<svg fill="currentColor"');
    }

    // Add width and height
    if (!svg.match(/<svg[^>]*width=/)) {
        svg = svg.replace(/<svg/, '<svg width="16"');
    }
    if (!svg.match(/<svg[^>]*height=/)) {
        svg = svg.replace(/<svg/, '<svg height="16"');
    }

    if (svg.match(/viewBox=/)) {
        svg = svg.replace(/viewBox="[^"]*"/, 'viewBox="0 0 16 16"');
    } else {
        svg = svg.replace(/<svg/, '<svg viewBox="0 0 16 16"');
    }

    if (svg.includes('class="nok-icon"')) {
        svg = svg.replace('class="nok-icon"', 'class="nok-icon %s"');
    } else {
        svg = svg.replace(/<svg/, '<svg class="nok-icon %s"');
    }

    return svg.trim();
}

const PASS1_CONFIG = {
    multipass: true,
    plugins: [
        {
            name: 'preset-default',
            params: {
                overrides: {
                    cleanupIds: {
                        remove: true,
                        minify: false
                    },
                    convertColors: {
                        currentColor: true
                    }
                }
            }
        },
        'removeDimensions',
        'removeTitle',
        'removeDesc',
        {
            name: 'removeAttrs',
            params: {
                attrs: '(style|id|data-.*|class)'
            }
        }
    ]
};

const PASS2_CONFIG = {
    multipass: true,
    plugins: [
        {
            name: 'preset-default',
            params: {
                overrides: {
                    convertPathData: {
                        floatPrecision: 2,
                        transformPrecision: 5
                    }
                }
            }
        },
        'convertTransform'
    ]
};

function processFile(inputPath, outputPath) {
    const svgContent = fs.readFileSync(inputPath, 'utf-8');

    // PASS 1: Clean and optimize
    const optimized = optimize(svgContent, {
        path: inputPath,
        ...PASS1_CONFIG
    });

    // Add transform group
    const withTransform = addTransformGroup(optimized.data);

    // PASS 2: Bake transforms into paths
    const baked = optimize(withTransform, {
        path: inputPath,
        ...PASS2_CONFIG
    });

    // Final cleanup
    const final = cleanupSvg(baked.data);

    fs.writeFileSync(outputPath, final, 'utf-8');
    console.log(`✓ ${path.basename(inputPath)}`);
}

function processDirectory() {
    if (!fs.existsSync(OUTPUT_DIR)) {
        fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    }

    const files = fs.readdirSync(INPUT_DIR);
    let processed = 0;

    files.forEach(file => {
        if (file.endsWith('.svg')) {
            const inputPath = path.join(INPUT_DIR, file);
            const outputPath = path.join(OUTPUT_DIR, file);
            processFile(inputPath, outputPath);
            processed++;
        }
    });

    console.log(`\n✓ Processed ${processed} icons`);
}

// Run
processDirectory();