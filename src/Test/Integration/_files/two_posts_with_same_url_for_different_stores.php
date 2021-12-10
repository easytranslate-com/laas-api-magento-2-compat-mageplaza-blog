<?php

declare(strict_types=1);

use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Mageplaza\Blog\Model\Post;
use Mageplaza\Blog\Model\ResourceModel\Post as PostResource;

$objectManager = Bootstrap::getObjectManager();
//We only do this for Magento 2.3.6 compatibility
$pathToFixture            = 'Magento/Store/_files/second_store.php';
$fixtureResolverClassName = '\Magento\TestFramework\Workaround\Override\Fixture\Resolver';
if (class_exists($fixtureResolverClassName)) {
    try {
        $fixtureResolverClassName::getInstance()->requireDataFixture($pathToFixture);
    } catch (Exception $e) {
        require __DIR__ . '/../../../../../../dev/tests/integration/testsuite/' . $pathToFixture;
    }
}
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
$store           = $storeRepository->get('fixture_second_store');

/** @var PostResource $postResource */
$postResource = $objectManager->create(PostResource::class);

/** @var Post $post */
$post = $objectManager->create(Post::class);
$post->setName('Test1')
    ->setUrlKey('test1')
    ->setData('store_ids', 1)
    ->setPostContent('Test Content 1');

$postResource->save($post);

$post = $objectManager->create(Post::class);
$post->setName('Test2')
    ->setUrlKey('test2')
    ->setData('store_ids', $store->getId())
    ->setPostContent('Test Content 2');
$postResource->save($post);
