<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Test\Integration\Model;

/**
 * This class enables to require data fixtures in Magento 2.3 and 2.4. Whenever 2.3 is EOL, this can be removed,
 * because @link \Magento\TestFramework\Workaround\Override\Fixture\Resolver can then be used directly.
 */
class BcFixtureResolver
{
    public static function requireDataFixture(string $path, string $relativePathFromRoot): void
    {
        // use this class name as a string only to not break on Magento 2.3, where the class does not exist
        $fixtureResolverClassName = '\Magento\TestFramework\Workaround\Override\Fixture\Resolver';
        if (class_exists($fixtureResolverClassName)) {
            $fixtureResolverClassName::getInstance()->requireDataFixture($path);
        } else {
            $pathParts = [
                rtrim(BP, DS),
                ltrim($relativePathFromRoot, DS)
            ];
            require implode(DS, $pathParts);
        }
    }
}
