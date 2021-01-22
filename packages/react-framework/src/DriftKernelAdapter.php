<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Application;
use Drift\Console\OutputPrinter;
use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Context\ServerContext;
use Drift\Server\Mime\MimeTypeChecker;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

class DriftKernelAdapter implements KernelAdapter
{
    private FilesystemInterface $filesystem;
    private ServerContext $serverContext;
    private MimeTypeChecker $mimeTypeChecker;
    private string $rootPath;
    private ContainerInterface $container;
    private Application $application;

    public function __construct()
    {
        $container = require 'config/container.php';
        $application = $container->get(Application::class);
        (require 'router/middleware.php')($application, $container);
        (require 'router/routes.php')($application, $container);
        $this->container = $container;
        $this->application = $application;
    }

    public static function create(
        LoopInterface $loop,
        string $rootPath,
        ServerContext $serverContext,
        FilesystemInterface $filesystem,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker
    ): PromiseInterface
    {

        $self = new self();

        $self->serverContext = $serverContext;
        $self->filesystem = $filesystem;
        $self->mimeTypeChecker = $mimeTypeChecker;
        $self->rootPath = $rootPath;

        return resolve($self);
    }

    public function handle(ServerRequestInterface $request, callable $resolve): PromiseInterface
    {
        return $resolve($this->application->handle($request));
    }

    public static function getStaticFolder(): ?string
    {
        return '/public';
    }

    public function shutDown(): PromiseInterface
    {
        return resolve('nothing to do');
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return ['src', 'public', 'templates'];
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array
    {
        return ['php', 'yml', 'yaml', 'xml', 'css', 'js', 'html', 'twig'];
    }

    /**
     * Get watcher ignoring folders.
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array
    {
        return ['var'];
    }
}