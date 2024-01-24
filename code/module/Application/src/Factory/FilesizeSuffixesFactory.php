<?php
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FilesizeSuffixesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $sz = 'BKMGTPEZYXSD';
        $extraLetter = $container->get('lang') == 'fr' ? 'o' : 'B';
        $array = str_split($sz);
        return array_map(function($v) use ($extraLetter) { return $v.$extraLetter;}, $array);
    }
}
