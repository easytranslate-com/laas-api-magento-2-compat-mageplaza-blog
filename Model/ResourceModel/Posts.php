<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\ResourceModel;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Serialize\SerializerInterface;

class Posts extends AbstractDb
{
    protected $_eventPrefix = 'easytranslate_project';

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        RequestInterface $request,
        SerializerInterface $serializer,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->connection = $resource->getConnection();
        $this->request    = $request;
        $this->serializer = $serializer;
    }

    protected function _construct(): void
    {
        $this->_init('easytranslate_project', ProjectInterface::PROJECT_ID);
    }

    public function getPosts(ProjectModel $project): array
    {
        $select = $this->connection->select()
            ->from($this->getTable('easytranslate_project_posts'), ['post_id'])
            ->where('project_id = :project_id');
        $bind   = ['project_id' => (int)$project->getId()];

        return $this->connection->fetchCol($select, $bind);
    }

    public function saveProjectPosts(ProjectModel $project): void
    {
        $projectId = (int)$project->getId();
        $newPosts  = null;
        $posts     = $this->request->getParam('mageplaza_selected_posts');
        if ($posts !== null) {
            foreach ($this->serializer->unserialize($posts) as $post) {
                $newPosts[] = (int)$post;
            }
        }

        if ($newPosts === null) {
            return;
        }
        $oldPosts = [];
        foreach ($this->getPosts($project) as $oldPost) {
            $oldPosts[] = (int)$oldPost;
        }
        if (empty($newPosts) && empty($oldPosts)) {
            return;
        }

        $table  = $this->getTable('easytranslate_project_posts');
        $insert = array_diff($newPosts, $oldPosts);
        $delete = array_diff($oldPosts, $newPosts);

        if (!empty($delete)) {
            $where = [
                'post_id IN(?)' => $delete,
                'project_id=?'  => $projectId
            ];
            $this->connection->delete($table, $where);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $blogId) {
                $data[] = [
                    ProjectInterface::PROJECT_ID => $projectId,
                    'post_id'                    => (int)$blogId
                ];
            }
            $this->connection->insertMultiple($table, $data);
        }
    }
}
