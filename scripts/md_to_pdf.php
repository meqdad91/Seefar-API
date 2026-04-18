<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use League\CommonMark\GithubFlavoredMarkdownConverter;

$mdPath = $argv[1] ?? __DIR__ . '/../docs/API.md';
$pdfPath = $argv[2] ?? __DIR__ . '/../docs/API.pdf';

if (! is_file($mdPath)) {
    fwrite(STDERR, "Markdown file not found: $mdPath\n");
    exit(1);
}

$converter = new GithubFlavoredMarkdownConverter([
    'html_input' => 'allow',
    'allow_unsafe_links' => false,
]);

$bodyHtml = (string) $converter->convert(file_get_contents($mdPath));

$css = <<<'CSS'
@page { margin: 22mm 18mm; }
* { font-family: DejaVu Sans, sans-serif; }
body { font-size: 10.5pt; color: #1a1a1a; line-height: 1.45; }
h1 { font-size: 22pt; border-bottom: 2px solid #333; padding-bottom: 4px; margin-top: 0; }
h2 { font-size: 16pt; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-top: 22pt; }
h3 { font-size: 13pt; margin-top: 16pt; }
h4 { font-size: 11.5pt; margin-top: 12pt; }
code { font-family: DejaVu Sans Mono, monospace; background: #f3f3f3; padding: 1px 4px; border-radius: 3px; font-size: 9.5pt; }
pre { background: #f6f8fa; border: 1px solid #e1e4e8; border-radius: 4px; padding: 8px; font-size: 9pt; overflow-x: auto; }
pre code { background: none; padding: 0; }
table { border-collapse: collapse; width: 100%; margin: 8pt 0; font-size: 9.5pt; }
th, td { border: 1px solid #d0d0d0; padding: 5px 8px; text-align: left; vertical-align: top; }
th { background: #f3f3f3; }
hr { border: none; border-top: 1px solid #ccc; margin: 18pt 0; }
a { color: #0366d6; text-decoration: none; }
blockquote { border-left: 4px solid #ddd; margin: 0; padding: 0 12px; color: #555; }
ul, ol { margin: 6pt 0 6pt 16pt; }
CSS;

$html = "<!DOCTYPE html><html><head><meta charset='utf-8'><style>$css</style></head><body>$bodyHtml</body></html>";

$opts = new Options();
$opts->set('isRemoteEnabled', false);
$opts->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($opts);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

file_put_contents($pdfPath, $dompdf->output());
echo "Wrote: $pdfPath (" . number_format(filesize($pdfPath)) . " bytes)\n";
