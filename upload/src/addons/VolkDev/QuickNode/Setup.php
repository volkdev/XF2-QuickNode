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

    public function postInstall(array &$stateChanges)
    {
        $this->importRussianLanguage();
    }

    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        $this->importRussianLanguage();
    }

    protected function importRussianLanguage()
    {
        // First check if XenForo has a Russian language installed
        $language = \XF::em()->findOne('XF:Language', ['language_code' => 'ru-RU']);
        if (!$language) {
            $language = \XF::em()->findOne('XF:Language', ['language_code' => 'ru']);
        }
        
        if ($language) {
            $xmlFile = \XF::getAddOnDirectory() . '/VolkDev/QuickNode/_data/language-ru-volkdev_qnc.xml';
            if (file_exists($xmlFile)) {
                $document = simplexml_load_file($xmlFile);
                if ($document) {
                    /** @var \XF\Service\Language\Import $importService */
                    $importService = \XF::app()->service('XF:Language\Import');
                    $importService->setOverwriteLanguage($language);
                    $importService->importFromXml($document);
                }
            }
        }
    }

    public function installStep1()
    {
        $this->schemaManager()->createTable('xf_volkdev_qnc_log', function (Create $table) {
            $table->addColumn('log_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int')->comment('Пользователь, совершивший действие');
            $table->addColumn('node_id', 'int')->comment('ID раздела, с которым производилось действие');
            $table->addColumn('action', 'varchar', 50)->comment('Тип действия: create, edit, perm_change, delete');
            $table->addColumn('old_data', 'mediumblob')->nullable()->comment('JSON предыдущего состояния');
            $table->addColumn('new_data', 'mediumblob')->nullable()->comment('JSON нового состояния');
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

}
