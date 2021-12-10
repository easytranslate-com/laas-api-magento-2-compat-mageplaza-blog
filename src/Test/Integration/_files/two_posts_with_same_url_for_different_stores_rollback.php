<?php

declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Model\Post;
use Mageplaza\Blog\Model\ResourceModel\Post as PostResource;

require __DIR__ . '/../../../../../../../dev/tests/integration/testsuite/Magento/Store/_files/second_store.php';

$objectManager  = Bootstrap::getObjectManager();

/** @var PostResource $postResource */
$postResource = $objectManager->create(PostResource::class);
$post         = $objectManager->create(Post::class);

$post1 = $postResource->load($post, 'test1', PostInterface::URL_KEY);
$post2 = $postResource->load($post, 'test2', PostInterface::URL_KEY);
/** @var Magento\Framework\Model\AbstractModel $post1 */
$postResource->delete($post1);
/** @var Magento\Framework\Model\AbstractModel $post2 */
$postResource->delete($post2);
