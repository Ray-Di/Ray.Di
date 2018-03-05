<?php

declare(strict_types=1);
/**
 * This file is part of the Ray.Di package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
use Ray\Di\AbstractModule;
use Ray\Di\InjectionPointInterface;
use Ray\Di\Injector;
use Ray\Di\ProviderInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

interface FinderInterface
{
}

class LegacyFinder implements FinderInterface
{
}

class ModernFinder implements FinderInterface
{
}

class FinderModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FinderInterface::class)->toProvider(FinderProvider::class);
        $this->bind(MovieListerInterface::class)->to(ModernMovieLister::class);
    }
}

interface MovieListerInterface
{
}

class ModernMovieLister implements MovieListerInterface
{
    public $finder;

    public function __construct(FinderInterface $finder)
    {
        $this->finder = $finder;
    }
}

class FinderProvider implements ProviderInterface
{
    private $ip;

    public function __construct(InjectionPointInterface $ip)
    {
        $this->ip = $ip;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $consumer = $this->ip->getClass()->getName();
        // chooseb dependency(finder) by consumer
        $finder = ($consumer === 'ModernMovieLister') ? new ModernFinder : new LegacyFinder;

        return $finder;
    }
}

$injector = new Injector(new FinderModule);
$movieLister = $injector->getInstance(MovieListerInterface::class);
/* @var $movieLister MovieLister */
$works = ($movieLister->finder instanceof ModernFinder);

echo($works ? 'It works!' : 'It DOES NOT work!') . PHP_EOL;
