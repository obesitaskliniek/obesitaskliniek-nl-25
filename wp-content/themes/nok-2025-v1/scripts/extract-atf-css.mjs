#!/usr/bin/env node

/**
 * ATF CSS Extraction Script
 *
 * Splits nok-components.css into two complementary files:
 * - nok-atf.css  (above-the-fold, inlined in <head>)
 * - nok-btf.css  (below-the-fold, loaded deferred)
 *
 * Uses PostCSS for AST parsing. NOT a PostCSS plugin — we need two outputs
 * from one input, which doesn't fit the plugin model.
 *
 * Usage:
 *   node scripts/extract-atf-css.mjs              # Normal extraction
 *   node scripts/extract-atf-css.mjs --dry-run    # Audit only, no file writes
 *   node scripts/extract-atf-css.mjs --verbose    # Extraction + detailed output
 */

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';
import { createRequire } from 'node:module';
import { atfTokens, atfBoundaryTokensList, atfExcludePatternsList, atfRebootSelectors, options, criticalTokens } from './atf-selectors.config.mjs';

const require = createRequire(import.meta.url);
const csso = require('csso');

const __dirname = dirname(fileURLToPath(import.meta.url));
const THEME_ROOT = resolve(__dirname, '..');

// ---------------------------------------------------------------------------
// CLI flags
// ---------------------------------------------------------------------------
const args = process.argv.slice(2);
const DRY_RUN = args.includes('--dry-run');
const VERBOSE = args.includes('--verbose');

// ---------------------------------------------------------------------------
// Paths
// ---------------------------------------------------------------------------
const INPUT_PATH = resolve(THEME_ROOT, 'assets/css/nok-components.css');
const FONT_FACE_PATH = resolve(THEME_ROOT, 'assets/css/nok-atf-reboot.css');
const ATF_OUTPUT = resolve(THEME_ROOT, 'assets/css/nok-atf.css');
const ATF_MIN_OUTPUT = resolve(THEME_ROOT, 'assets/css/nok-atf.min.css');
const BTF_OUTPUT = resolve(THEME_ROOT, 'assets/css/nok-btf.css');
const BTF_MIN_OUTPUT = resolve(THEME_ROOT, 'assets/css/nok-btf.min.css');

// ---------------------------------------------------------------------------
// Selector matching
// ---------------------------------------------------------------------------

/**
 * Strip pseudo-classes and pseudo-elements from a selector.
 * e.g. "a:hover" → "a", "*::before" → "*", ".btn:not(:disabled)" → ".btn"
 */
function stripPseudos(selector) {
  // Remove ::pseudo-element
  let s = selector.replace(/::[a-zA-Z-]+(\([^)]*\))?/g, '');
  // Remove :pseudo-class (including functional like :not(...), :has(...))
  s = s.replace(/:[a-zA-Z-]+(\([^)]*\))?/g, '');
  return s.trim();
}

// Pre-compile boundary token RegExps for performance
const boundaryRegexps = atfBoundaryTokensList.map(token => {
  // Escape regex special chars in token, then require NOT followed by [a-zA-Z0-9_-]
  const escaped = token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return { token, re: new RegExp(escaped + '(?![a-zA-Z0-9_-])') };
});

// Pre-compile exclusion pattern matchers.
// Bare identifiers (start with letter, no CSS special chars) get boundary matching
// to prevent 'section' from matching inside '.nok-section'.
// Patterns with '.', ':', '[', '#', space, or combinators keep substring matching.
const isBareIdentifier = /^[a-zA-Z][a-zA-Z0-9_-]*$/;
const excludeMatchers = atfExcludePatternsList.map(pattern => {
  if (isBareIdentifier.test(pattern)) {
    const escaped = pattern.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const re = new RegExp('(?<![a-zA-Z0-9_-])' + escaped + '(?![a-zA-Z0-9_-])');
    return { pattern, test: sel => re.test(sel) };
  }
  return { pattern, test: sel => sel.includes(pattern) };
});

/**
 * Check if a selector is excluded from ATF even if it matches a token.
 */
function isExcluded(selector) {
  for (const { test } of excludeMatchers) {
    if (test(selector)) return true;
  }
  return false;
}

/**
 * Check if a single selector matches ATF criteria.
 */
function selectorMatchesATF(selector) {
  // Check exclusion first — if excluded, it's BTF regardless of token match
  if (isExcluded(selector)) return false;

  const stripped = stripPseudos(selector);

  // Exact match against reboot selectors
  if (atfRebootSelectors.includes(stripped)) return true;

  // Substring match against ATF tokens
  for (const token of atfTokens) {
    if (selector.includes(token) || stripped.includes(token)) return true;
  }

  // Boundary match against boundary tokens
  for (const { re } of boundaryRegexps) {
    if (re.test(selector) || re.test(stripped)) return true;
  }

  return false;
}

/**
 * For --dry-run: find which token matched a selector (for reporting).
 * Returns null for unmatched, string for matched, string starting with [excluded] for excluded.
 */
function findMatchingToken(selector) {
  const excluded = isExcluded(selector);
  const stripped = stripPseudos(selector);
  const prefix = excluded ? '[excluded] ' : '';

  if (atfRebootSelectors.includes(stripped)) return `${prefix}[reboot] ${stripped}`;
  for (const token of atfTokens) {
    if (selector.includes(token) || stripped.includes(token)) return `${prefix}${token}`;
  }
  for (const { token, re } of boundaryRegexps) {
    if (re.test(selector) || re.test(stripped)) return `${prefix}[boundary] ${token}`;
  }
  return null;
}

// ---------------------------------------------------------------------------
// Node classification helpers
// ---------------------------------------------------------------------------

/** Check if a rule's selectors are all :root */
function isRootRule(rule) {
  return rule.selectors && rule.selectors.every(s => s.trim() === ':root');
}

/** Check if a comment is a license/banner comment (starts with !) */
function isLicenseComment(comment) {
  return comment.text.trimStart().startsWith('!');
}

/** Count declarations in a node tree (recursive) */
function countDeclarations(node) {
  let count = 0;
  if (node.type === 'decl') return 1;
  if (node.nodes) {
    for (const child of node.nodes) {
      count += countDeclarations(child);
    }
  }
  return count;
}

// ---------------------------------------------------------------------------
// Core splitting logic
// ---------------------------------------------------------------------------

/**
 * Split a single rule into ATF and BTF halves based on selector matching.
 * Returns { atf: Rule|null, btf: Rule|null }
 */
function splitRule(rule) {
  const atfSelectors = [];
  const btfSelectors = [];

  for (const sel of rule.selectors) {
    if (selectorMatchesATF(sel)) {
      atfSelectors.push(sel);
    } else {
      btfSelectors.push(sel);
    }
  }

  const atf = atfSelectors.length > 0 ? rule.clone({ selectors: atfSelectors }) : null;
  const btf = btfSelectors.length > 0 ? rule.clone({ selectors: btfSelectors }) : null;

  return { atf, btf };
}

/**
 * Process a container at-rule (@media, @supports) recursively.
 * Returns { atf: AtRule|null, btf: AtRule|null }
 */
function processContainerAtRule(atRule) {
  const atfChildren = [];
  const btfChildren = [];

  for (const child of atRule.nodes) {
    if (child.type === 'rule') {
      if (isRootRule(child)) {
        // :root inside @media/@supports → duplicate to both
        atfChildren.push(child.clone());
        btfChildren.push(child.clone());
      } else {
        const { atf, btf } = splitRule(child);
        if (atf) atfChildren.push(atf);
        if (btf) btfChildren.push(btf);
      }
    } else if (child.type === 'atrule' && (child.name === 'media' || child.name === 'supports')) {
      // Nested container — recurse
      const { atf, btf } = processContainerAtRule(child);
      if (atf) atfChildren.push(atf);
      if (btf) btfChildren.push(btf);
    } else if (child.type === 'comment') {
      // Comments: skip (don't duplicate to save size)
      continue;
    } else {
      // Declarations directly in at-rule, other nodes → both
      atfChildren.push(child.clone());
      btfChildren.push(child.clone());
    }
  }

  // Filter out comment-only children (no actual rules)
  const atfHasRules = atfChildren.some(n => n.type !== 'comment');
  const btfHasRules = btfChildren.some(n => n.type !== 'comment');

  let atf = null;
  let btf = null;

  if (atfHasRules) {
    atf = atRule.clone({ nodes: [] });
    for (const child of atfChildren) atf.append(child);
  }

  if (btfHasRules) {
    btf = atRule.clone({ nodes: [] });
    for (const child of btfChildren) btf.append(child);
  }

  return { atf, btf };
}

// ---------------------------------------------------------------------------
// Main extraction
// ---------------------------------------------------------------------------

function extract(inputCSS, fontFaceCSS) {
  const root = postcss.parse(inputCSS, { from: INPUT_PATH });

  const atfNodes = [];
  const btfNodes = [];

  // Dry-run tracking
  const matchedByToken = new Map();  // token → Set<selector>
  const unmatchedSelectors = new Set();

  for (const node of root.nodes) {
    switch (node.type) {
      case 'comment': {
        if (isLicenseComment(node)) {
          // License comments → both
          atfNodes.push(node.clone());
          btfNodes.push(node.clone());
        }
        // Regular comments: skip (don't duplicate to save size)
        break;
      }

      case 'atrule': {
        if (node.name === 'charset') {
          atfNodes.push(node.clone());
          btfNodes.push(node.clone());
        } else if (node.name === 'font-face') {
          // @font-face → ATF only
          atfNodes.push(node.clone());
        } else if (node.name === 'keyframes' || node.name === '-webkit-keyframes') {
          // @keyframes → BTF unless explicitly listed in config
          const keyframeName = node.params;
          if (options.atfKeyframes.includes(keyframeName)) {
            atfNodes.push(node.clone());
          } else {
            btfNodes.push(node.clone());
          }
        } else if (node.name === 'media' || node.name === 'supports') {
          // Container at-rules → recursive processing
          const { atf, btf } = processContainerAtRule(node);
          if (atf) atfNodes.push(atf);
          if (btf) btfNodes.push(btf);
        } else {
          // Unknown at-rules → BTF
          btfNodes.push(node.clone());
        }
        break;
      }

      case 'rule': {
        if (isRootRule(node)) {
          // :root → both (duplicate)
          atfNodes.push(node.clone());
          btfNodes.push(node.clone());
        } else {
          // Regular rule → split by selector
          const { atf, btf } = splitRule(node);
          if (atf) atfNodes.push(atf);
          if (btf) btfNodes.push(btf);

          // Track for dry-run reporting
          if (DRY_RUN || VERBOSE) {
            for (const sel of node.selectors) {
              const token = findMatchingToken(sel);
              if (token) {
                if (!matchedByToken.has(token)) matchedByToken.set(token, new Set());
                matchedByToken.get(token).add(sel);
              } else {
                unmatchedSelectors.add(sel);
              }
            }
          }
        }
        break;
      }

      default: {
        // Unknown top-level nodes → BTF
        btfNodes.push(node.clone());
      }
    }
  }

  // Build output ASTs
  const atfRoot = postcss.root();
  const btfRoot = postcss.root();

  // Prepend font-face CSS to ATF if available
  if (fontFaceCSS) {
    const fontRoot = postcss.parse(fontFaceCSS, { from: FONT_FACE_PATH });
    for (const node of fontRoot.nodes) {
      atfRoot.append(node.clone());
    }
    // Add separator comment
    atfRoot.append(postcss.comment({ text: ' End font-face / Begin extracted ATF ' }));
  }

  for (const node of atfNodes) atfRoot.append(node);
  for (const node of btfNodes) btfRoot.append(node);

  // Prune :root blocks in ATF to only keep variables referenced by ATF rules
  if (!options.duplicateRootVars) {
    pruneRootVars(atfRoot);
  }

  return {
    atfCSS: atfRoot.toResult({ map: false }).css,
    btfCSS: btfRoot.toResult({ map: false }).css,
    matchedByToken,
    unmatchedSelectors,
    inputRoot: root,
    atfRoot,
    btfRoot,
  };
}

// ---------------------------------------------------------------------------
// :root variable pruning — only keep vars referenced by ATF rules
// ---------------------------------------------------------------------------

/**
 * Collect all var(--name) references from non-:root rules in a tree.
 * Follows variable chains: if --a references var(--b), both are needed.
 */
function collectReferencedVars(root) {
  const varRefRegex = /var\(\s*(--[a-zA-Z0-9_-]+)/g;
  const directRefs = new Set();
  const allDecls = new Map(); // prop → value (for :root declarations only)

  // First pass: collect all :root declarations and all var() refs from non-:root rules
  root.walk(node => {
    if (node.type === 'decl') {
      const inRoot = node.parent && node.parent.type === 'rule' && node.parent.selector && node.parent.selector.trim() === ':root';
      if (inRoot) {
        allDecls.set(node.prop, node.value);
      } else {
        // Non-:root declaration — collect var() references
        let match;
        while ((match = varRefRegex.exec(node.value)) !== null) {
          directRefs.add(match[1]);
        }
        // Also check property (e.g. custom properties in non-:root rules)
        if (node.prop.startsWith('--')) {
          while ((match = varRefRegex.exec(node.value)) !== null) {
            directRefs.add(match[1]);
          }
        }
      }
    }
  });

  // Second pass: follow variable chains (if --a: var(--b), and --a is needed, --b is needed too)
  const needed = new Set(directRefs);
  let changed = true;
  while (changed) {
    changed = false;
    for (const varName of needed) {
      const value = allDecls.get(varName);
      if (!value) continue;
      let match;
      varRefRegex.lastIndex = 0;
      while ((match = varRefRegex.exec(value)) !== null) {
        if (!needed.has(match[1])) {
          needed.add(match[1]);
          changed = true;
        }
      }
    }
  }

  return needed;
}

/**
 * Remove :root declarations that aren't in the needed set.
 * Removes empty :root rules and empty @media wrappers after pruning.
 */
function pruneRootVars(root) {
  const needed = collectReferencedVars(root);

  root.walk(node => {
    if (node.type === 'rule' && node.selector && node.selector.trim() === ':root') {
      // Remove declarations not in the needed set
      const toRemove = [];
      node.walk(decl => {
        if (decl.type === 'decl' && decl.prop.startsWith('--') && !needed.has(decl.prop)) {
          toRemove.push(decl);
        }
      });
      for (const decl of toRemove) decl.remove();

      // Remove empty :root rule
      if (!node.nodes || node.nodes.length === 0) {
        node.remove();
      }
    }
  });

  // Remove empty @media/@supports wrappers
  root.walk(node => {
    if (node.type === 'atrule' && (node.name === 'media' || node.name === 'supports')) {
      const hasContent = node.nodes && node.nodes.some(n => n.type !== 'comment');
      if (!hasContent) node.remove();
    }
  });
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

function validate(result, inputCSS) {
  const errors = [];
  const warnings = [];

  const { atfRoot, btfRoot, inputRoot } = result;

  // 1. Declaration count integrity
  const inputDecls = countDeclarations(inputRoot);
  const atfDecls = countDeclarations(atfRoot);
  const btfDecls = countDeclarations(btfRoot);

  // Count :root declarations in input (these are duplicated to both)
  // Includes :root inside @media/@supports which also get duplicated
  let rootDeclCount = 0;
  inputRoot.walk(node => {
    if (node.type === 'rule' && isRootRule(node)) {
      node.walk(d => { if (d.type === 'decl') rootDeclCount++; });
    }
  });

  // Font-face declarations are ATF-only, so they reduce BTF count.
  // But they're from the input, so they're already counted.
  // The expected total: atf + btf = input + rootDeclCount (due to duplication)
  const expectedTotal = inputDecls + rootDeclCount;
  const actualTotal = atfDecls + btfDecls;

  const diff = actualTotal - expectedTotal;
  if (diff !== 0) {
    if (!options.duplicateRootVars && diff < 0) {
      // Negative diff expected when :root pruning is active — pruned vars are removed from ATF
      if (VERBOSE) {
        console.log(`  Note: ${-diff} :root declarations pruned from ATF (variable dependency analysis)`);
      }
    } else if (diff < 0) {
      errors.push(
        `Declaration count mismatch: ATF(${atfDecls}) + BTF(${btfDecls}) = ${actualTotal}, ` +
        `expected ${expectedTotal} (input ${inputDecls} + ${rootDeclCount} duplicated :root). ` +
        `Difference: ${diff}`
      );
    }
    // Positive diff is OK — comes from prepended font-face CSS and selector splitting
  }

  // 2. No empty @media blocks
  const checkEmptyAtRules = (root, label) => {
    root.walk(node => {
      if (node.type === 'atrule' && (node.name === 'media' || node.name === 'supports')) {
        const hasRules = node.nodes && node.nodes.some(n => n.type !== 'comment');
        if (!hasRules) {
          warnings.push(`Empty @${node.name} block in ${label}: @${node.name} ${node.params}`);
        }
      }
    });
  };
  checkEmptyAtRules(atfRoot, 'ATF');
  checkEmptyAtRules(btfRoot, 'BTF');

  // 3. Critical token coverage
  for (const token of criticalTokens) {
    let found = false;
    atfRoot.walk(node => {
      if (found) return;
      if (node.type === 'rule') {
        for (const sel of node.selectors) {
          if (sel.includes(token)) {
            found = true;
            return;
          }
        }
      }
    });
    if (!found) {
      errors.push(`Critical token "${token}" has no matching rule in ATF output`);
    }
  }

  // 4. Size budget (rough minified estimate: ~65% of expanded)
  const atfSize = Buffer.byteLength(result.atfCSS, 'utf8');
  const estimatedMinified = Math.round(atfSize * 0.65 / 1024);
  if (estimatedMinified > options.sizeBudgetKB) {
    warnings.push(
      `ATF size estimate ${estimatedMinified}KB exceeds budget of ${options.sizeBudgetKB}KB ` +
      `(expanded: ${Math.round(atfSize / 1024)}KB)`
    );
  }

  return { errors, warnings, stats: { inputDecls, atfDecls, btfDecls, rootDeclCount, atfSize } };
}

// ---------------------------------------------------------------------------
// Reporting
// ---------------------------------------------------------------------------

function printReport(result, validation) {
  const { matchedByToken, unmatchedSelectors } = result;
  const { errors, warnings, stats } = validation;

  console.log('\n=== ATF CSS Extraction Report ===\n');

  if (DRY_RUN) {
    console.log('MODE: Dry run (no files written)\n');

    // Print matched selectors grouped by token
    console.log('--- Matched selectors by token ---\n');
    const sortedTokens = [...matchedByToken.entries()].sort((a, b) => b[1].size - a[1].size);
    for (const [token, selectors] of sortedTokens) {
      console.log(`  ${token} (${selectors.size} selectors)`);
      if (VERBOSE) {
        for (const sel of [...selectors].sort()) {
          console.log(`    ${sel}`);
        }
      }
    }

    // Print potentially missed ATF selectors
    const suspectUnmatched = [...unmatchedSelectors].filter(sel =>
      /nav|hero|section|button|header|logo|sticky/i.test(sel)
    );
    if (suspectUnmatched.length > 0) {
      console.log('\n--- Potentially missed ATF selectors (review these) ---\n');
      for (const sel of suspectUnmatched.sort()) {
        console.log(`  ${sel}`);
      }
    }

    console.log(`\nTotal matched selectors: ${[...matchedByToken.values()].reduce((sum, s) => sum + s.size, 0)}`);
    console.log(`Total unmatched selectors: ${unmatchedSelectors.size}`);
  }

  // Stats
  const btfSize = Buffer.byteLength(result.btfCSS, 'utf8');
  console.log('\n--- Size report ---\n');
  console.log(`  Input:  ${Math.round(Buffer.byteLength(result.atfCSS, 'utf8') / 1024)}KB (ATF expanded)`);
  console.log(`  ATF:    ~${Math.round(stats.atfSize * 0.65 / 1024)}KB (minified est.)`);
  console.log(`  BTF:    ${Math.round(btfSize / 1024)}KB expanded / ~${Math.round(btfSize * 0.65 / 1024)}KB min est.`);
  console.log(`\n  Declarations: input=${stats.inputDecls}, ATF=${stats.atfDecls}, BTF=${stats.btfDecls}`);
  console.log(`  Duplicated :root declarations: ${stats.rootDeclCount}`);
  console.log(`  Expected ATF+BTF: ${stats.inputDecls + stats.rootDeclCount}, Actual: ${stats.atfDecls + stats.btfDecls}`);

  // Errors
  if (errors.length > 0) {
    console.log('\n--- ERRORS ---\n');
    for (const err of errors) console.error(`  ERROR: ${err}`);
  }

  // Warnings
  if (warnings.length > 0) {
    console.log('\n--- Warnings ---\n');
    for (const warn of warnings) console.warn(`  WARN: ${warn}`);
  }

  if (errors.length === 0 && warnings.length === 0) {
    console.log('\n  All checks passed.');
  }

  console.log('');
  return errors.length === 0;
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------

function main() {
  // Read input
  if (!existsSync(INPUT_PATH)) {
    console.error(`Input file not found: ${INPUT_PATH}`);
    console.error('Run "npm run css:build" first to compile nok-components.css');
    process.exit(1);
  }

  let inputCSS = readFileSync(INPUT_PATH, 'utf8');
  // Strip sourceMappingURL from input (sass compiler adds it)
  inputCSS = inputCSS.replace(/\/\*#\s*sourceMappingURL=[\s\S]*?\*\//g, '').trim();

  // Read font-face CSS (optional — may not exist yet during Phase 1)
  let fontFaceCSS = null;
  if (existsSync(FONT_FACE_PATH)) {
    fontFaceCSS = readFileSync(FONT_FACE_PATH, 'utf8');
    // Strip any sourceMappingURL (IDE file watchers may add inline source maps)
    fontFaceCSS = fontFaceCSS.replace(/\/\*#\s*sourceMappingURL=[\s\S]*?\*\//g, '').trim();
  }

  console.log(`Reading: ${INPUT_PATH} (${Math.round(Buffer.byteLength(inputCSS, 'utf8') / 1024)}KB)`);

  // Extract
  const result = extract(inputCSS, fontFaceCSS);

  // Validate
  const validation = validate(result, inputCSS);

  // Report
  const ok = printReport(result, validation);

  // Write output (unless dry-run or errors)
  if (!DRY_RUN) {
    if (!ok) {
      console.error('Errors found — not writing output files.');
      process.exit(1);
    }

    writeFileSync(ATF_OUTPUT, result.atfCSS, 'utf8');
    writeFileSync(BTF_OUTPUT, result.btfCSS, 'utf8');

    // Minify
    const atfMin = csso.minify(result.atfCSS, { comments: false }).css;
    const btfMin = csso.minify(result.btfCSS, { comments: false }).css;
    writeFileSync(ATF_MIN_OUTPUT, atfMin, 'utf8');
    writeFileSync(BTF_MIN_OUTPUT, btfMin, 'utf8');

    const atfMinKB = Math.round(Buffer.byteLength(atfMin, 'utf8') / 1024);
    const btfMinKB = Math.round(Buffer.byteLength(btfMin, 'utf8') / 1024);
    console.log(`Written: ${ATF_OUTPUT} → ${ATF_MIN_OUTPUT} (${atfMinKB}KB)`);
    console.log(`Written: ${BTF_OUTPUT} → ${BTF_MIN_OUTPUT} (${btfMinKB}KB)`);
  }
}

main();
