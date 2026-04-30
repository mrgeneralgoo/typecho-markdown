<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$typechoVersion = getenv('TYPECHO_VERSION') ?: 'v1.2.0';
$typechoRoot = __DIR__ . '/.typecho/' . $typechoVersion;
$typechoVarDir = $typechoRoot . '/var';
$sentinel = $typechoVarDir . '/Typecho/Common.php';

if (!defined('__TYPECHO_ROOT_DIR__')) {
    define('__TYPECHO_ROOT_DIR__', $typechoRoot);
}

if (!is_file($sentinel)) {
    if (!is_dir(__DIR__ . '/.typecho')) {
        mkdir(__DIR__ . '/.typecho', 0777, true);
    }

    $command = sprintf(
        'git clone --depth 1 --branch %s https://github.com/typecho/typecho.git %s 2>&1',
        escapeshellarg($typechoVersion),
        escapeshellarg($typechoRoot)
    );

    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);

    if ($exitCode !== 0 || !is_file($sentinel)) {
        fwrite(STDERR, "Failed to clone Typecho {$typechoVersion}.\n");
        fwrite(STDERR, "Command: {$command}\n");
        fwrite(STDERR, implode("\n", $output) . "\n");
        fwrite(STDERR, "You can manually clone Typecho into tests/.typecho/{$typechoVersion}/\n");
        exit(1);
    }
}

spl_autoload_register(static function (string $class) use ($typechoVarDir): void {
    if (
        !str_starts_with($class, 'Typecho\\')
        && !str_starts_with($class, 'Widget\\')
        && !str_starts_with($class, 'IXR\\')
    ) {
        return;
    }

    $relative = str_replace('\\', '/', $class) . '.php';
    $path = $typechoVarDir . '/' . $relative;
    if (is_file($path)) {
        require_once $path;
    }
});

$required = [
    'Typecho\\Plugin\\PluginInterface',
    'Typecho\\Widget\\Helper\\Form',
    'Widget\\Options',
    'Typecho\\Plugin',
];

foreach ($required as $name) {
    if (!class_exists($name) && !interface_exists($name)) {
        throw new RuntimeException(
            "Required Typecho symbol not found after autoload: {$name} (TYPECHO_VERSION={$typechoVersion})"
        );
    }
}

require_once __DIR__ . '/../MarkdownParse.php';
