<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab;

use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Helper\Data;
use Magento\Framework\Data\Collection as CollectionData;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Helper\Data as MageplazaHelper;
use Mageplaza\Blog\Model\ResourceModel\Post\Collection;
use Mageplaza\Blog\Model\ResourceModel\Post\CollectionFactory;

class MageplazaBlogs extends AbstractEntity
{
    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var MageplazaHelper
     */
    private $mageplazaHelper;

    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        ProjectGetter $projectGetter,
        MageplazaHelper $mageplazaHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->setId('easytranslate_mageplaza_blogs');
        $this->collectionFactory = $collectionFactory;
        $this->projectGetter     = $projectGetter;
        $this->mageplazaHelper   = $mageplazaHelper;
    }

    /**
     * @param Column $column
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in blogs flag
        if ($column->getId() === 'mageplaza_blogs') {
            $mageplazaBlogIds = $this->getSelectedMageplazaBlogIds();
            if (empty($mageplazaBlogIds)) {
                $mageplazaBlogIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('main_table.post_id', ['in' => $mageplazaBlogIds]);
            } elseif (!empty($mageplazaBlogIds)) {
                $this->getCollection()->addFieldToFilter('main_table.post_id', ['nin' => $mageplazaBlogIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    protected function _prepareCollection()
    {
        /** @var Collection $mageplazaBlogCollection */
        $mageplazaBlogCollection = $this->collectionFactory->create();
        $mageplazaBlogCollection->addAttributeToSelect(PostInterface::NAME);
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            // join stores in which blogs have already been added to a project / translated
            $projectMageplazaBlogTable = $mageplazaBlogCollection->getTable('easytranslate_project_mageplaza_blog');
            $projectTargetStoreTable   = $mageplazaBlogCollection->getTable('easytranslate_project_target_store');
            $mageplazaBlogCollection->getSelect()->joinLeft(
                ['etpmpb' => $projectMageplazaBlogTable],
                'etpmpb.blog_id=main_table.post_id',
                ['project_ids' => 'GROUP_CONCAT(DISTINCT etpmpb.project_id)']
            );
            $mageplazaBlogCollection->getSelect()->joinLeft(
                ['etpts' => $projectTargetStoreTable],
                'etpts.project_id=etpmpb.project_id',
                ['translated_stores' => 'GROUP_CONCAT(DISTINCT target_store_id)']
            );
        } else {
            $selectedCmsBlockIds = $this->getSelectedMageplazaBlogIds();
            $mageplazaBlogCollection->addFieldToFilter('main_table.post_id', ['in' => $selectedCmsBlockIds]);
        }
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($storeId > 0) {
            $this->mageplazaHelper->addStoreFilter($mageplazaBlogCollection, $storeId);
        }
        $this->setCollection($mageplazaBlogCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn('mageplaza_posts', [
                'header_css_class' => 'a-center',
                'inline_css'       => 'in-project',
                'type'             => 'checkbox',
                'name'             => 'mageplaza_posts',
                'values'           => $this->getSelectedMageplazaBlogIds(),
                'align'            => 'center',
                'index'            => PostInterface::POST_ID
            ]);
        }
        $this->addColumn(PostInterface::POST_ID, [
            'header'           => __('ID'),
            'sortable'         => true,
            'index'            => PostInterface::POST_ID,
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
        ]);
        $this->addColumn('title', [
            'header' => __('Name'),
            'index'  => 'name'
        ]);

        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(
                'translated_stores',
                [
                    'header'                    => __('Already Translated In'),
                    'width'                     => '250px',
                    'index'                     => 'translated_stores',
                    'type'                      => 'store',
                    'store_view'                => true,
                    'sortable'                  => false,
                    'store_all'                 => true,
                    'filter_condition_callback' => [$this, 'filterTranslatedCondition'],
                ]
            );
        }

        return parent::_prepareColumns();
    }

    private function getSelectedMageplazaBlogIds(): array
    {
        $mageplazaBlogs = $this->getRequest()->getPost('included_mageplaza_blogs');
        if ($mageplazaBlogs === null) {
            //TODO implement getMagePlazaBlogs()
            //            if ($this->projectGetter->getProject()) {
            //                return $this->projectGetter->getProject()->getMagePlazaBlogs();
            //            }

            return [];
        }

        return explode(',', $mageplazaBlogs);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/project_mageplazaBlogs/grid', ['_current' => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    protected function filterTranslatedCondition(CollectionData $collection, Column $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value) {
            $collection->getSelect()->where('etpts.target_store_id=?', $value);
        }
    }
}
