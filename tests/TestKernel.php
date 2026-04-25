<?php

namespace Biblioverse\TypesenseBundle\Tests;

use Biblioverse\TypesenseBundle\BiblioverseTypesenseBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\VarExporter\ProxyHelper;

class TestKernel extends Kernel
{
    use MicroKernelTrait;
    public const CONFIG_KEY = 'biblioverse_typesense';

    /**
     * @param array{'configs'?: array{'biblioverse_typesense'?:string}, 'bundles'?:string[]} $settings
     */
    public function __construct(
        string $environment,
        bool $debug,
        private array $settings = [],
    ) {
        parent::__construct($environment, $debug);
    }

    public function boot(): void
    {
        if (!$this->booted) {
            $this->clearCache();
        }
        parent::boot();
    }

    /**
     * @ihneritDoc
     */
    public function registerBundles(): iterable
    {
        $bundles = array_merge([
            DoctrineBundle::class,
            BiblioverseTypesenseBundle::class,
            FrameworkBundle::class,
            DoctrineFixturesBundle::class,
        ], $this->settings['bundles'] ?? []);

        foreach ($bundles as $bundle) {
            $instance = new $bundle();

            if (!$instance instanceof BundleInterface) {
                throw new \InvalidArgumentException(sprintf('Bundle %s must be an instance of %s', $instance::class, BundleInterface::class));
            }
            yield $instance;
        }
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->settings['configs'][] = 'config/packages/doctrine.yaml';
        $this->settings['configs'][] = 'config/packages/framework.yaml';
        $this->settings['configs'][] = 'config/services.yaml';
        if (false === in_array(self::CONFIG_KEY, array_keys($this->settings['configs']))) {
            $this->settings['configs'][self::CONFIG_KEY] = 'config/packages/biblioverse_typesense.yaml';
        }
        foreach ($this->settings['configs'] as $config) {
            $loader->load(__DIR__.'/'.$config);
        }
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/cache';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/logs';
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/kernel';
    }

    public function shutdown(): void
    {
        parent::shutdown();

        $this->clearCache();
    }

    protected function build(ContainerBuilder $containerBuilder): void
    {
        // symfony/var-exporter v8 removed ProxyHelper::generateLazyGhost(); on
        // PHP 8.4+ Doctrine can fall back to native lazy objects instead.
        if (\PHP_VERSION_ID >= 80400 && !method_exists(ProxyHelper::class, 'generateLazyGhost')) { // @phpstan-ignore-line
            $containerBuilder->prependExtensionConfig('doctrine', [
                'orm' => ['enable_native_lazy_objects' => true],
            ]);
        }
    }

    private function clearCache(): void
    {
        $cacheDirectory = $this->getCacheDir();
        $logDirectory = $this->getLogDir();

        $filesystem = new Filesystem();

        if ($filesystem->exists($cacheDirectory)) {
            $filesystem->remove($cacheDirectory);
        }

        if ($filesystem->exists($logDirectory)) {
            $filesystem->remove($logDirectory);
        }
    }
}
