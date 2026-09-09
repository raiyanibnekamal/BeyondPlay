const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "../..");
const cssTag = '<link rel="stylesheet" href="assets/css/arena-ui.css">';
const cssAfter = '<link rel="stylesheet" href="assets/css/style.css">';
const jsTag = '<script src="assets/js/arena-ui.js"></script>';
const jsBefore = '<script src="assets/js/main.js"></script>';
const preloaderBtn = '<button class="th-btn preloaderCls">CANCEL PRELOADER</button>';

function fixTitle(html) {
  return html
    .replace(/<title>Arena[^<]*Esports Tournament Platform \| ([^<]+)<\/title>/gi, "<title>Arena | $1</title>")
    .replace(/Arena\s*[\u00e2\u20ac\u201d\u00a2\u2014\u00a0]+/g, "Arena —")
    .replace(/Arena\s*â€[\u201c\u201d]?/g, "Arena —");
}

let fixed = 0;
for (const name of fs.readdirSync(root)) {
  if (!name.endsWith(".html")) continue;
  const file = path.join(root, name);
  let c = fs.readFileSync(file, "utf8");
  const orig = c;

  if (c.includes(preloaderBtn)) c = c.split(preloaderBtn).join("");
  if (!c.includes("arena-ui.css")) c = c.replace(cssAfter, cssAfter + cssTag);
  if (!c.includes("arena-ui.js")) {
    c = c.includes(jsBefore) ? c.replace(jsBefore, jsTag + jsBefore) : c.replace("</body>", jsTag + "</body>");
  }
  c = fixTitle(c);

  if (c !== orig) {
    fs.writeFileSync(file, c, "utf8");
    fixed++;
    console.log("Updated:", name);
  }
}
console.log("Done.", fixed, "files.");
