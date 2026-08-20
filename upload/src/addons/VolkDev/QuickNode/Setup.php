<?php

namespace VolkDev\QuickNode;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->schemaManager()->createTable('xf_volkdev_qnc_log', function (Create $table) {
            $table->addColumn('log_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int')->comment('User who performed the action');
            $table->addColumn('node_id', 'int')->comment('Node ID affected by the action');
            $table->addColumn('action', 'varchar', 50)->comment('Action type: create, edit, perm_change, delete');
            $table->addColumn('old_data', 'mediumblob')->nullable()->comment('JSON of previous state');
            $table->addColumn('new_data', 'mediumblob')->nullable()->comment('JSON of new state');
            $table->addColumn('log_date', 'int')->comment('UNIX timestamp');
            $table->addColumn('ip_address', 'varbinary', 16)->nullable()->setDefault(null)->comment('IP address of the user');
            $table->addKey('user_id');
            $table->addKey('node_id');
            $table->addKey('log_date');
        });
    }

    public function installStep2()
    {
        $this->schemaManager()->alterTable('xf_user_group', function (Alter $table) {
            $table->addColumn('qnc_protected', 'tinyint')->setDefault(0)->comment('Protected from Quick Node Perms editing');
        });
    }

    public function installStep3()
    {
        $this->schemaManager()->alterTable('xf_node', function (Alter $table) {
            $table->addColumn('qnc_pending_delete', 'tinyint')->setDefault(0)->comment('QuickNode pending deletion flag');
        });
    }

    public function installStep4()
    {
        $this->schemaManager()->createTable('xf_volkdev_qnc_perm_template', function (Create $table) {
            $table->addColumn('template_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 100);
            $table->addColumn('description', 'text')->nullable();
            $table->addColumn('display_order', 'int')->setDefault(1);
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addColumn('node_scope', 'varchar', 25)->setDefault('all');
            $table->addColumn('node_ids', 'blob')->nullable();
            $table->addColumn('user_group_scope', 'varchar', 25)->setDefault('all');
            $table->addColumn('user_group_ids', 'blob')->nullable();
            $table->addColumn('permissions', 'mediumblob')->nullable();
            $table->addColumn('admin_only', 'tinyint')->setDefault(0);
            $table->addKey('display_order');
        });

        $this->seedDefaultTemplates();
    }

    protected function seedDefaultTemplates()
    {
        $modPerms = [
            'forum' => [
                'manageAnyThread' => 'content_allow',
                'deleteAnyThread' => 'content_allow',
                'deleteAnyPost' => 'content_allow',
                'lockUnlockThread' => 'content_allow',
                'stickUnstickThread' => 'content_allow',
                'inlineMod' => 'content_allow',
            ]
        ];

        $adminPerms = [
            'forum' => [
                'manageAnyThread' => 'content_allow',
                'deleteAnyThread' => 'content_allow',
                'deleteAnyPost' => 'content_allow',
                'lockUnlockThread' => 'content_allow',
                'stickUnstickThread' => 'content_allow',
                'inlineMod' => 'content_allow',
                'editAnyPost' => 'content_allow',
                'viewDeleted' => 'content_allow',
                'viewModerated' => 'content_allow',
                'undelete' => 'content_allow',
                'approveUnapprove' => 'content_allow',
            ]
        ];

        $this->db()->insert('xf_volkdev_qnc_perm_template', [
            'title' => 'Moderator permissions',
            'description' => 'Soft delete, move, stick, open/close threads.',
            'display_order' => 1,
            'active' => 1,
            'node_scope' => 'all',
            'node_ids' => json_encode([]),
            'user_group_scope' => 'all',
            'user_group_ids' => json_encode([]),
            'permissions' => json_encode($modPerms),
            'admin_only' => 0
        ]);

        $this->db()->insert('xf_volkdev_qnc_perm_template', [
            'title' => 'Administrator permissions',
            'description' => 'All moderator permissions, plus issuing warnings and editing others posts.',
            'display_order' => 2,
            'active' => 1,
            'node_scope' => 'all',
            'node_ids' => json_encode([]),
            'user_group_scope' => 'all',
            'user_group_ids' => json_encode([]),
            'permissions' => json_encode($adminPerms),
            'admin_only' => 1
        ]);
    }

    public function upgrade3000470Step1()
    {
        $this->installStep2();
    }

    public function upgrade3020070Step1()
    {
        $this->schemaManager()->alterTable('xf_volkdev_qnc_log', function (Alter $table) {
            $table->addColumn('ip_address', 'varbinary', 16)->nullable()->setDefault(null)->comment('IP address of the user');
        });
    }

    public function upgrade3020070Step2()
    {
        $this->schemaManager()->alterTable('xf_node', function (Alter $table) {
            $table->addColumn('qnc_pending_delete', 'tinyint')->setDefault(0)->comment('QuickNode pending deletion flag');
        });
    }

    public function upgrade3030570Step1()
    {
        $this->installStep4();
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_volkdev_qnc_log');
    }

    public function uninstallStep2()
    {
        $this->schemaManager()->alterTable('xf_user_group', function (Alter $table) {
            $table->dropColumns(['qnc_protected']);
        });
    }

    public function uninstallStep3()
    {
        $this->schemaManager()->alterTable('xf_node', function (Alter $table) {
            $table->dropColumns(['qnc_pending_delete']);
        });
    }

    public function uninstallStep4()
    {
        $this->schemaManager()->dropTable('xf_volkdev_qnc_perm_template');
    }
}
