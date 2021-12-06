<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Test\Integration\Model\Content\Generator;

use EasyTranslate\CompatMageplazaBlog\Model\Content\Generator\Posts as PostsGenerator;
use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts as CompatResourcePost;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Project;
use Magento\TestFramework\Helper\Bootstrap;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Model\Post;
use Mageplaza\Blog\Model\PostFactory;
use Mageplaza\Blog\Model\ResourceModel\Post as PostResource;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation  enabled
 */
class PostTest extends TestCase
{
    /**
     * @var int
     */
    private static $projectId;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var PostsGenerator
     */
    private $postsGenerator;

    /**
     * @var PostFactory
     */
    private $postFactory;

    /**
     * @var PostResource
     */
    private $postResource;

    /**
     * @var CompatResourcePost
     */
    private $compatPostResource;

    protected function setUp(): void
    {
        $objectManager            = Bootstrap::getObjectManager();
        $this->projectRepository  = $objectManager->create(ProjectRepositoryInterface::class);
        $this->postsGenerator     = $objectManager->create(PostsGenerator::class);
        $this->postFactory        = $objectManager->create(PostFactory::class);
        $this->postResource       = $objectManager->create(PostResource::class);
        $this->compatPostResource = $objectManager->create(CompatResourcePost::class);
    }

    /**
     * @magentoDataFixture    loadPostsFixture
     * @magentoDataFixture    loadProjectFixture
     * @magentoAppIsolation   enabled
     */
    public function testGetContent(): void
    {
        $urlKey             = 'test1';
        $storeId            = 1;
        $includedAttributes = ['name', 'url_key', 'post_content'];
        $this->assertContent($urlKey, $storeId, $includedAttributes, []);
    }

    /**
     * @magentoDataFixture   loadPostsFixture
     * @magentoDataFixture   loadProjectFixture
     * @magentoConfigFixture current_store easytranslate/mageplaza_blog_posts/attributes name
     */
    public function testGetContentRespectsSettings(): void
    {
        $identifier         = 'test1';
        $storeId            = 1;
        $includedAttributes = ['name'];
        $excludedAttributes = ['url_key', 'post_content'];
        $this->assertContent($identifier, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture   loadPostsFixture
     * @magentoDataFixture   loadProjectFixture
     * @magentoConfigFixture current_store easytranslate/mageplaza_blog_posts/attributes
     */
    public function testGetContentRespectsSettings2(): void
    {
        $identifier         = 'test1';
        $storeId            = 1;
        $includedAttributes = [];
        $excludedAttributes = ['name', 'url_key', 'post_content'];
        $this->assertContent($identifier, $storeId, $includedAttributes, $excludedAttributes);
    }

    private function assertContent(
        string $urlKey,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {

        $project = $this->projectRepository->get(self::$projectId);
        $post    = $this->loadPostPost($urlKey, $storeId);
        $this->compatPostResource->saveProjectPosts($project, [$post->getId()]);
        $project->setSourceStoreId($storeId);
        $generatedContents = $this->postsGenerator->getContent($project);
        foreach ($includedAttributes as $attributeCode) {
            $keyParts = [PostsGenerator::ENTITY_CODE, $post->getData(PostInterface::URL_KEY), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayHasKey($key, $generatedContents);
            $expectedContent = $post->getData($attributeCode);
            $actualContent   = $generatedContents[$key];
            self::assertEquals($expectedContent, $actualContent);
        }
        foreach ($excludedAttributes as $attributeCode) {
            $keyParts = [PostsGenerator::ENTITY_CODE, $post->getData(PostInterface::URL_KEY), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayNotHasKey($key, $generatedContents);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public static function loadProjectFixture(): void
    {
        include __DIR__ . '/../../../../../../../Connector/src/Test/Integration/_files/project.php';
        /** @var Project $project */
        // @phpstan-ignore-next-line
        self::$projectId = (int)$project->getId();
    }

    public static function loadPostsFixture(): void
    {
        include __DIR__ . '/../../../_files/two_posts_with_same_url_for_different_stores.php';
    }

    private function loadPostPost(string $urlKey, int $storeId): Post
    {
        $post = $this->postFactory->create();
        $post->setData('store_ids', $storeId);
        $this->postResource->load($post, $urlKey, PostInterface::URL_KEY);

        return $post;
    }
}
