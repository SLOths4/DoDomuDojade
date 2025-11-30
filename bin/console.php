<?php
declare(strict_types=1);

use App\Application\UseCase\Quote\FetchQuoteUseCase;

chdir(__DIR__ . '/..');

require  __DIR__ . '/../vendor/autoload.php';
$container = require __DIR__ . '/../src/bootstrap/bootstrap.php';

$command = $argv[1] ?? null;

switch ($command) {
    case 'quote:fetch':
        $container->get(FetchQuoteUseCase::class)->execute();
        break;

    default:
        fwrite(STDERR, "Unknown command: $command\n");
        exit(1);
}
