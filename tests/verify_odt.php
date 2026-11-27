<?php
/**
 * Portable CLI verification suite for ODT caption and reference rendering.
 *
 * Usage:
 *   php tests/verify_odt.php
 *
 * Environment variables (optional):
 *   DOKUWIKI_DIR      Path to a DokuWiki checkout (defaults to ../dokuwiki)
 *   DOKUWIKI_ODT_DIR  Path to dokuwiki-plugin-odt (defaults to DOKUWIKI_DIR/lib/plugins/odt or ../dokuwiki-plugin-odt)
 */

if (PHP_SAPI !== 'cli') {
    exit("This script must be run from the command line.\n");
}

/**
 * Recursively remove a directory created by this test (never follows junctions/symlinks).
 */
function caption_test_rmdir($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_link($path)) {
            @unlink($path);
        } elseif (is_dir($path)) {
            caption_test_rmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

$case = $argv[1] ?? 'all';

if ($case === 'all') {
    $captionDir = realpath(__DIR__ . '/..');
    $dokuwikiEnv = getenv('DOKUWIKI_DIR');
    $dokuwikiDir = ($dokuwikiEnv !== false && $dokuwikiEnv !== '')
        ? realpath($dokuwikiEnv)
        : realpath($captionDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'dokuwiki');

    if (!$dokuwikiDir || !is_dir($dokuwikiDir)) {
        fwrite(STDERR, "DokuWiki directory not found. Set DOKUWIKI_DIR or place 'dokuwiki' alongside 'dokuwiki-caption'.\n");
        exit(1);
    }

    $odtEnv = getenv('DOKUWIKI_ODT_DIR');
    if ($odtEnv !== false && $odtEnv !== '') {
        $odtDir = realpath($odtEnv);
    } elseif (is_dir($dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'odt')) {
        $odtDir = realpath($dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'odt');
    } else {
        $odtDir = realpath($captionDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'dokuwiki-plugin-odt');
    }

    if (!$odtDir || !is_dir($odtDir)) {
        fwrite(STDERR, "dokuwiki-plugin-odt directory not found. Set DOKUWIKI_ODT_DIR or place 'dokuwiki-plugin-odt' alongside 'dokuwiki-caption'.\n");
        exit(1);
    }

    $dwRoot = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'dokuwiki-caption-odt-test-' . uniqid('', true);
    $createdLinks = [];
    $allPassed = true;

    try {
        $dataDirs = [
            'pages', 'attic', 'media', 'media' . DIRECTORY_SEPARATOR . 'wiki',
            'media_attic', 'media_meta', 'meta', 'cache', 'index', 'locks', 'tmp', 'log',
        ];
        if (!mkdir($dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins', 0777, true)) {
            throw new RuntimeException("Failed to create temp lib/plugins directory under $dwRoot");
        }
        foreach ($dataDirs as $sub) {
            $dir = $dwRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw new RuntimeException("Failed to create temp data directory $dir");
            }
        }

        $linkMap = [
            $dwRoot . DIRECTORY_SEPARATOR . 'inc' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'inc',
            $dwRoot . DIRECTORY_SEPARATOR . 'vendor' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'vendor',
            $dwRoot . DIRECTORY_SEPARATOR . 'conf' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'conf',
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'images' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'images',
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'styles' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'styles',
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'tpl' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'tpl',
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'scripts' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'scripts',
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'exe' => $dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'exe',
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'odt' => $odtDir,
            $dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'caption' => $captionDir,
        ];

        foreach ($linkMap as $link => $target) {
            if (!is_dir($target)) {
                throw new RuntimeException("Expected directory does not exist: $target");
            }
            if (PHP_OS_FAMILY === 'Windows') {
                $mkOut = [];
                $mkCode = 0;
                exec('cmd /c mklink /J ' . escapeshellarg($link) . ' ' . escapeshellarg($target) . ' 2>&1', $mkOut, $mkCode);
                if ($mkCode !== 0) {
                    throw new RuntimeException("Failed to create junction $link -> $target: " . implode("\n", $mkOut));
                }
            } else {
                if (!symlink($target, $link)) {
                    throw new RuntimeException("Failed to create symlink $link -> $target");
                }
            }
            $createdLinks[] = $link;
        }

        $sampleLogo = $dokuwikiDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logo.png';
        if (file_exists($sampleLogo)) {
            copy($sampleLogo, $dwRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'wiki' . DIRECTORY_SEPARATOR . 'dokuwiki-128.png');
        }

        foreach (['1', '2', '3', '4'] as $c) {
            $out = [];
            $code = 0;
            exec(
                escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($c) . ' ' . escapeshellarg($dwRoot) . ' 2>&1',
                $out,
                $code
            );
            echo "[Case $c] exit=$code\n" . implode("\n", $out) . "\n";
            if ($code !== 0 || strpos(implode("\n", $out), "CASE $c PASSED") === false) {
                $allPassed = false;
                break;
            }
        }
    } finally {
        foreach (array_reverse($createdLinks) as $link) {
            if (PHP_OS_FAMILY === 'Windows') {
                @rmdir($link);
            } else {
                @unlink($link);
            }
        }
        caption_test_rmdir($dwRoot . DIRECTORY_SEPARATOR . 'data');
        @rmdir($dwRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins');
        @rmdir($dwRoot . DIRECTORY_SEPARATOR . 'lib');
        @rmdir($dwRoot);
    }

    if (!$allPassed) {
        exit(1);
    }
    echo "ALL ODT VERIFICATION CHECKS PASSED\n";
    exit(0);
}

// Child case execution mode
$dwRoot = rtrim($argv[2] ?? '', '/\\') . DIRECTORY_SEPARATOR;
if ($dwRoot === DIRECTORY_SEPARATOR || !is_dir($dwRoot)) {
    fwrite(STDERR, "Invalid temp DokuWiki root passed to test case.\n");
    exit(1);
}

error_reporting(E_ALL);
$phpWarnings = [];
set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$phpWarnings) {
    if ($errno & (E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR)) {
        if (strpos($errfile, 'caption') !== false || strpos($errstr, 'Undefined') !== false) {
            $phpWarnings[] = "$errstr in $errfile:$errline";
        }
    }
    return false;
});

chdir($dwRoot);
define('NOSESSION', 1);
define('DOKU_INC', $dwRoot);
define('DOKU_PLUGIN', $dwRoot . 'lib/plugins/');
define('DOKU_CONF', $dwRoot . 'conf/');

ob_start();
require_once DOKU_INC . 'inc/init.php';
ob_end_clean();

global $conf, $ID, $REV;
$conf['datadir'] = $dwRoot . 'data/pages';
$conf['cachedir'] = $dwRoot . 'data/cache';
$conf['tmpdir'] = $dwRoot . 'data/tmp';
$conf['metadir'] = $dwRoot . 'data/meta';
$conf['mediadir'] = $dwRoot . 'data/media';
$conf['lockdir'] = $dwRoot . 'data/locks';
$conf['olddir'] = $dwRoot . 'data/attic';
$conf['indexdir'] = $dwRoot . 'data/index';
$conf['lang'] = 'en';

function renderPageToOdtXml($pageId, $wikitext, $mode = 'odt_page') {
    global $ID, $REV, $caption_labels, $dwRoot;
    $ID = $pageId;
    $REV = '';
    $caption_labels = [];

    $pageFile = wikiFN($pageId);
    io_saveFile($pageFile, $wikitext);

    $instructions = p_cached_instructions($pageFile, false, $pageId);

    // Ensure renderer class is loaded, then instantiate fresh (ODT renderers are singletons whose ZIP closes at document_end)
    plugin_load('renderer', $mode);
    $class = 'renderer_plugin_' . $mode;
    /** @var renderer_plugin_odt_page $renderer */
    $renderer = new $class();
    foreach ($instructions as $instruction) {
        if (method_exists($renderer, $instruction[0])) {
            call_user_func_array([&$renderer, $instruction[0]], $instruction[1] ?: []);
        }
    }

    $odtFile = $dwRoot . 'data/tmp/' . str_replace(':', '_', $pageId) . '_' . $mode . '.odt';
    io_saveFile($odtFile, $renderer->doc);

    $unpackDir = $dwRoot . 'data/tmp/unpack_' . str_replace(':', '_', $pageId) . '_' . $mode;
    if (is_dir($unpackDir)) {
        io_rmdir($unpackDir, true);
    }
    $zip = new \splitbrain\PHPArchive\Zip();
    $zip->open($odtFile);
    $zip->extract($unpackDir);

    $contentXml = file_get_contents($unpackDir . '/content.xml');
    $dom = new DOMDocument();
    $prevUseErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($contentXml);
    $xmlErrors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($prevUseErrors);
    if (!$loaded) {
        $msgs = array_map(fn($e) => trim($e->message), $xmlErrors);
        throw new RuntimeException("Invalid XML in $pageId ($mode): " . implode('; ', $msgs));
    }
    if (strpos($contentXml, '<locallink') !== false) {
        throw new RuntimeException("Unreplaced <locallink> found in $pageId ($mode)");
    }

    return $contentXml;
}

function assertContains($haystack, $needle, $msg) {
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException("ASSERTION FAILED: $msg\nExpected to find: $needle\nIn XML:\n" . $haystack);
    }
}

$wikitext1 = <<<'DW'
<figure firstfig>
{{ :wiki:dokuwiki-128.png |}}
<caption>First figure</caption>
</figure>

<figure eniac>
{{ :wiki:dokuwiki-128.png |}}
<caption>Programadoras operam o primeiro computador eletrônico da história, o ENIAC. \\ FONTE: [(:harvard:CNN-ENIAC>>direct:yes)].</caption>
</figure>

Electronic Numerical Integrator and Computer (ENIAC - em português: computador integrador numérico eletrônico) foi o primeiro computador digital eletrônico de grande escala (Figura {{ref>eniac}}).
DW;

$wikitext2 = <<<'DW'
See forward figure {{ref>later}} and table {{ref>later_tab}} and stepped {{ref>stepped}}.

  * Nested forward ref {{ref>later}}

<figure earlier>
<caption>Earlier figure</caption>
</figure>

<figure later>
<caption>Later figure</caption>
</figure>

{{setcounter figure=10}}

<figure stepped>
<caption>Stepped figure</caption>
</figure>

<table later_tab>
<caption>Later table</caption>
^ A ^ B ^
| 1 | 2 |
</table>
DW;

$wikitext3 = <<<'DW'
<figure>
<caption>Unlabeled figure</caption>
</figure>

<figure MyFig>
<caption>Uppercase label figure</caption>
</figure>

{{setcounter figure=5}}
<figure f5>
<caption>Five</caption>
</figure>

{{setcounter figure=0}}
<figure f0>
<caption>Zero</caption>
</figure>

<table mytab>
<caption>My table</caption>
^ Col1 ^ Col2 ^
| x    | y    |
</table>

<codeblock mycode>
<caption>My code</caption>
<code>
echo "hi";
</code>
</codeblock>

<fileblock myfile>
<caption>My file</caption>
<file txt foo.txt>
hello
</file>
</fileblock>

Refs: fig={{ref>MyFig}}, five={{ref>f5}}, zero={{ref>f0}}, tab={{ref>mytab}}, code={{ref>mycode}}, file={{ref>myfile}}, missing={{ref>missing}}.
DW;

if ($case === '1') {
    $xml1 = renderPageToOdtXml('test:issue30', $wikitext1, 'odt_page');
    assertContains($xml1, '<text:bookmark text:name="plugin_caption_eniac"/>', 'Issue 30 bookmark present');
    assertContains($xml1, 'Figure 2: ', 'Issue 30 figure 2 caption prefix');
    assertContains($xml1, 'xlink:href="#plugin_caption_eniac"', 'Issue 30 reference href');
    assertContains($xml1, '>2</text:a>', 'Issue 30 reference number 2');
    echo "CASE 1 PASSED\n";
} elseif ($case === '2') {
    $xml2 = renderPageToOdtXml('test:forward', $wikitext2, 'odt_page');
    assertContains($xml2, 'xlink:href="#plugin_caption_later"', 'Forward ref href');
    assertContains($xml2, '>2</text:a>', 'Forward ref number 2');
    assertContains($xml2, 'xlink:href="#plugin_caption_later_tab"', 'Forward table ref href');
    assertContains($xml2, '>1</text:a>', 'Forward table ref number 1');
    assertContains($xml2, 'xlink:href="#plugin_caption_stepped"', 'Stepped ref href');
    assertContains($xml2, '>10</text:a>', 'Stepped ref number 10');
    echo "CASE 2 PASSED\n";
} elseif ($case === '3') {
    $xml3 = renderPageToOdtXml('test:alltypes', $wikitext3, 'odt_page');
    assertContains($xml3, '<text:bookmark text:name="plugin_caption_myfig"/>', 'Uppercase bookmark lowercased');
    assertContains($xml3, 'xlink:href="#plugin_caption_myfig"', 'Uppercase ref href');
    assertContains($xml3, '>2</text:a>', 'Uppercase ref number 2');
    assertContains($xml3, 'Figure 5: ', 'Setcounter 5 caption');
    assertContains($xml3, '>5</text:a>', 'Setcounter 5 ref');
    assertContains($xml3, 'Figure 0: ', 'Setcounter 0 caption');
    assertContains($xml3, 'xlink:href="#plugin_caption_f0"', 'Setcounter 0 ref href');
    assertContains($xml3, '>0</text:a>', 'Setcounter 0 ref number 0');
    assertContains($xml3, 'Table 1: ', 'Table caption');
    assertContains($xml3, 'xlink:href="#plugin_caption_mytab"', 'Table ref href');
    assertContains($xml3, 'Listing 1: My code', 'Codeblock caption');
    assertContains($xml3, 'xlink:href="#plugin_caption_mycode"', 'Codeblock ref href');
    assertContains($xml3, 'Listing 1: My file', 'Fileblock caption');
    assertContains($xml3, 'xlink:href="#plugin_caption_myfile"', 'Fileblock ref href');
    assertContains($xml3, '##REF:missing##', 'Unresolved ref fallback');
    echo "CASE 3 PASSED\n";
} elseif ($case === '4') {
    $xml4 = renderPageToOdtXml('test:pdfmode', $wikitext1, 'odt_pagepdf');
    assertContains($xml4, '<text:bookmark text:name="plugin_caption_eniac"/>', 'odt_pdf bookmark present');
    assertContains($xml4, '>2</text:a>', 'odt_pdf reference number 2');
    echo "CASE 4 PASSED\n";
} else {
    fwrite(STDERR, "Unknown test case: $case\n");
    exit(1);
}

if (!empty($phpWarnings)) {
    throw new RuntimeException("PHP Warnings detected:\n" . implode("\n", $phpWarnings));
}
