<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

class InstallSchema implements InstallSchemaInterface
{
    /**
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        if (!$setup->tableExists('easytranslate_project_mageplaza_blog_posts')) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable('easytranslate_project_mageplaza_blog_posts'))
                ->addColumn('project_id', Table::TYPE_INTEGER, null, [
                    'identity' => false,
                    'nullable' => false,
                    'unsigned' => true,
                    'primary'  => true,
                ], 'Project ID')
                ->addColumn('post_id', Table::TYPE_INTEGER, null, [
                    'nullable' => false,
                    'unsigned' => true,
                    'primary'  => true,
                    'padding'  => 10,
                ], 'Post ID')
                ->addIndex(
                    $setup->getIdxName(
                        'easytranslate_project_mageplaza_blog_posts',
                        'post_id',
                        'btree'
                    ),
                    'post_id'
                )
                ->addForeignKey(
                    $setup->getFkName(
                        'easytranslate_project_mageplaza_blog_posts',
                        'project_id',
                        'easytranslate_project',
                        'project_id'
                    ),
                    'project_id',
                    $setup->getTable('easytranslate_project'),
                    'project_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        'easytranslate_project_mageplaza_blog_posts',
                        'post_id',
                        'mageplaza_blog_post',
                        'post_id'
                    ),
                    'post_id',
                    $setup->getTable('mageplaza_blog_post'),
                    'post_id',
                    Table::ACTION_CASCADE
                );
            $setup->getConnection()->createTable($table);
            $setup->endSetup();
        }
    }
}
