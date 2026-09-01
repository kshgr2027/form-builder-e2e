/**
 * Builds the Google Drive submission folder from the repo:
 *   node scripts/build-drive-package.mjs
 * Renders the Markdown docs to PDF via headless Chromium (no Word / pandoc needed).
 */
import { chromium } from '@playwright/test';
import { execSync } from 'node:child_process';
import * as fs from 'node:fs';
import * as path from 'node:path';

const mdToHtml = (md) =>
  execSync('npx --yes marked@12 --gfm', { input: md, encoding: 'utf8', maxBuffer: 20 * 1024 * 1024 });

const repo = path.resolve('.');
const drive = path.resolve('..', 'Senior-QA-Automation-Assessment');
fs.rmSync(drive, { recursive: true, force: true });
for (const d of ['01-GitHub', '02-Documentation', '03-Evidence/Defects', '03-Evidence/Execution', '04-Supporting'])
  fs.mkdirSync(path.join(drive, d), { recursive: true });

const CSS = `
body{font-family:Calibri,"Segoe UI",Arial,sans-serif;font-size:10.5pt;color:#1f2933;line-height:1.4;margin:0}
h1{font-size:17pt;color:#1a3d5c;border-bottom:2px solid #cdd9e3;padding-bottom:3pt;margin:16pt 0 6pt}
h2{font-size:13pt;color:#1a3d5c;margin:13pt 0 4pt}
h3{font-size:11pt;color:#21618c;margin:10pt 0 3pt}
h4{font-size:10pt;color:#21618c;margin:8pt 0 2pt}
code{background:#eef2f5;padding:0 3px;border-radius:2px;font-size:9pt}
pre{background:#f5f8fb;border:1px solid #cdd9e3;padding:8pt;border-radius:4px;overflow-x:auto;font-size:8.5pt;line-height:1.35;white-space:pre-wrap;word-break:break-word}
pre code{background:none;padding:0}
table{border-collapse:collapse;width:100%;margin:6pt 0;font-size:8.5pt}
th,td{border:0.75pt solid #b7c4d0;padding:3pt 5pt;vertical-align:top;text-align:left}
th{background:#e8eef4;color:#1a3d5c}
blockquote{border-left:3px solid #b7c4d0;margin:6pt 0;padding:2pt 10pt;color:#425466;background:#f7f9fb}
a{color:#1f5fa8}
hr{border:0;border-top:1px solid #cdd9e3;margin:10pt 0}
`;

const docs = [
  ['README.md', '02-Documentation/README.pdf'],
  ['docs/test-strategy.md', '02-Documentation/Test-Strategy.pdf'],
  ['docs/test-cases.md', '02-Documentation/Test-Cases.pdf'],
  ['docs/defect-report.md', '02-Documentation/Defect-Report.pdf'],
  ['docs/coverage-matrix.md', '02-Documentation/Coverage-Matrix.pdf'],
  ['docs/execution-summary.md', '02-Documentation/Execution-Summary.pdf'],
  ['PROGRESS.md', '04-Supporting/PROGRESS.pdf'],
  ['docs/self-review.md', '04-Supporting/Self-Review.pdf'],
];

const browser = await chromium.launch();
const page = await browser.newPage();
for (const [src, out] of docs) {
  const md = fs.readFileSync(path.join(repo, src), 'utf8').replace(/^﻿/, '');
  const html = `<!doctype html><meta charset="utf-8"><style>${CSS}</style>${mdToHtml(md)}`;
  await page.setContent(html, { waitUntil: 'load' });
  await page.pdf({
    path: path.join(drive, out),
    format: 'A4',
    margin: { top: '16mm', bottom: '16mm', left: '15mm', right: '15mm' },
    printBackground: true,
  });
  console.log('PDF:', out);
}
await browser.close();

// evidence
const cp = (from, to) => fs.cpSync(from, to, { recursive: true });
cp(path.join(repo, 'evidence/defects'), path.join(drive, '03-Evidence/Defects'));
cp(path.join(repo, 'evidence/execution'), path.join(drive, '03-Evidence/Execution'));
fs.copyFileSync(path.join(repo, 'evidence/README.md'), path.join(drive, '03-Evidence/README.md'));

// repo link
const sha = execSync('git rev-parse HEAD', { cwd: repo }).toString().trim();
fs.writeFileSync(path.join(drive, '01-GitHub/Repository-Link.txt'),
`Senior QA Automation Assessment - GitHub repository
===================================================

Repository : <PASTE YOUR GITHUB URL HERE AFTER PUSH>
Branch     : main
Commit SHA : ${sha}

The repo is already initialised and committed. To publish:

    cd "${repo}"
    git remote add origin https://github.com/<you>/<repo>.git
    git push -u origin main

Contents: src/ (POM + API + DB + fixtures + data), tests/ (12 spec files / 49 tests),
docs/, evidence/, .github/workflows/ci.yml, README.md, PROGRESS.md.
`);

console.log('\nDrive package:', drive);
for (const f of fs.readdirSync(drive, { recursive: true })) if (path.extname(f)) console.log('  ' + f);
process.exit(0);
