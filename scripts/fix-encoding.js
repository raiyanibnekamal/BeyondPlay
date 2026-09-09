/**
 * Fix UTF-8 mojibake across HTML and related frontend files.
 */
const fs = require("fs");
const path = require("path");

// Longest sequences first to avoid partial replacements.
const REPLACEMENTS = [
  [
    "\u00C3\u0192\u00C2\u00A2\u2014\u00C2\u009D",
    "\u2014",
  ],
  [
    "\u00C3\u0192\u00C2\u00A2\u00C3\u00A2\u00E2\u20AC\u0161\u00C2\u00AC\u00C3\u00A2\u00E2\u201A\u00AC\u00E2\u20AC\u009D",
    "\u2014",
  ],
  ["\u00C3\u00A2\u00E2\u20AC\u0161\u00C2\u00AC\u00C3\u00A2\u00E2\u201A\u00AC", "\u2014"],
  ["\u00C3\u00A2\u00E2\u201A\u00AC\u00E2\u20AC\u009D", "\u2014"],
  ["\u00C3\u00A2\u00E2\u201A\u00AC\u00C2\u009D", "\u2014"],
  ["\u00C3\u00A2\u00E2\u201A\u00AC\u00E2\u201E\u00A2", "'"],
  ["\u00C3\u0192\u00E2\u20AC\u201D", "\u00D7"],
  ["\u00E2\u20AC\u201D", "\u2014"],
  ["\u00E2\u20AC\u201C", "\u201c"],
  ["\u00E2\u20AC\u2122", "'"],
  ["\u00E2\u20AC\u2018", "'"],
  ["\u00E2\u20AC\u2019", "'"],
  ["\u00E2\u0080\u0094", "\u2014"],
  ["\u00E2\u0080\u0093", "\u2013"],
  ["\u00E2\u0080\u0099", "'"],
  ["\u00E2\u0080\u0098", "'"],
  ["\u00E2\u0080\u009C", "\u201c"],
  ["\u00E2\u0080\u009D", "\u201d"],
  ["\u00E2\u0080\u00A2", "\u2022"],
  ["\u00E2\u0080\u00A6", "\u2026"],
  ["\u00C2\u00A0", " "],
  ["&mdash;", "\u2014"],
  ["&ndash;", "\u2013"],
  ["&rsquo;", "'"],
  ["&lsquo;", "'"],
  ["&rdquo;", "\u201d"],
  ["&ldquo;", "\u201c"],
  ["&hellip;", "\u2026"],
  ["&bull;", "\u2022"],
  ["&times;", "\u00D7"],
  ["PRO Gamer Today !", "PRO Gamer Today!"],
  ["Today !", "Today!"],
];

function walk(dir, files = []) {
  for (const name of fs.readdirSync(dir)) {
    const full = path.join(dir, name);
    if (fs.statSync(full).isDirectory()) {
      if (["node_modules", "vendor", "tournament-backend", "scripts"].includes(name)) {
        continue;
      }
      walk(full, files);
    } else if (/\.(html|js|css|json|md|php)$/i.test(name)) {
      files.push(full);
    }
  }
  return files;
}

let changed = 0;
for (const file of walk(path.join(__dirname, ".."))) {
  let content = fs.readFileSync(file, "utf8");
  let next = content;
  for (const [from, to] of REPLACEMENTS) {
    next = next.split(from).join(to);
  }
  if (next !== content) {
    fs.writeFileSync(file, next, "utf8");
    changed++;
    console.log("fixed:", file);
  }
}
console.log("done,", changed, "files");
