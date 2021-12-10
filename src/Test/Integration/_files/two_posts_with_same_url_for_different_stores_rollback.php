<?php

declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Model\Post;
use Mageplaza\Blog\Model\ResourceModel\Post as PostResource;

$objectManager = Bootstrap::getObjectManager();

/** @var PostResource $postResource */
$postResource = $objectManager->create(PostResource::class);

/** @var Post $post */
$post = $objectManager->create(Post::class);
$postResource->load($post, 'test1', PostInterface::URL_KEY);
$postResource->delete($post);

/** @var Post $post */
$post = $objectManager->create(Post::class);
$postResource->load($post, 'test2', PostInterface::URL_KEY);
$postResource->delete($post);
