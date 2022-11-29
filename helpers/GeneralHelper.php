<?php
namespace GPN\MMP\CheckHash\Helpers;

use Bitrix\Main\{ Config\Option, Localization\Loc,
    IO };

Loc::loadMessages(__FILE__);

/**
 * Class GeneralHelper
 *
 * @package GPN\MMP\CheckHash\Helpers
 */
class GeneralHelper
{
    /**
     * @var array
     */
    public array $status = [];

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * __construct
     */
    public function __construct()
    {
        $this->options  = $this->getOptions();
        $this->status   = $this->getDefaultStatus();
    }

    /**
     * @return void
     */
    public function addAgent(): void
    {
        $this->removeAgent();

        $result = \CAgent::AddAgent(
            '\\GPN\\MMP\\CheckHash\\CheckHashAgent::run();',
            'ds.mmp',
            'N',
            $this->options['AGENT_INTERVAL'],
        );

        if ($result)
        {
            $this->setDefaultStatus([
                'TYPE'      => 'OK',
                'MESSAGE'   => Loc::getMessage('AGENT_ADD_SUCCESSFULLY')
            ]);
        }
    }

    /**
     * @return void
     */
    public function removeAgent(): void
    {
        \CAgent::RemoveModuleAgents('ds.mmp');
    }

    /**
     * @param string $moduleID
     *
     * @return array|false
     */
    public static function getAgentInfo(string  $moduleID)
    {
        $arResult = \CAgent::GetList(['ID' => 'DESC'], [
            'MODULE_ID' => $moduleID
        ]);

        while ($row = $arResult->GetNext()) {
            return $row;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkWritable()
    {
        $this->setDefaultStatus([
            'MESSAGE'   => Loc::getMessage('PATH_ACCESS_DENIED')
        ]);

        if (!IO\Directory::isDirectoryExists($this->getFolderPath())) {
            IO\Directory::createDirectory($this->getFolderPath());
        }

        if ($this->isWritable($this->getFolderPath()))
        {
            $this->setDefaultStatus([
                'TYPE'      => 'OK',
                'MESSAGE'   => Loc::getMessage('PATH_ACCESS_ALLOWED')
            ]);

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function runAgent()
    {
        $this->setDefaultStatus([
            'MESSAGE'   => Loc::getMessage('COMPLETED_WITH_ERROR')
        ]);

        $checkHash = new \GPN\MMP\CheckHash\General();

        if ( $checkHash->run() )
        {
            $this->setDefaultStatus([
                'TYPE'      => 'OK',
                'MESSAGE'   => Loc::getMessage('COMPLETED_SUCCESSFULLY')
            ]);

            return true;
        }

        return false;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function setDefaultStatus(array $fields): array
    {
        return $this->status = $fields;
    }

    /**
     * @return array
     */
    protected function getDefaultStatus(): array
    {
        return [
            'TYPE'      => 'ERROR',
            'MESSAGE'   => Loc::getMessage('SCRIPT_ERROR')
        ];
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function getOptions(): array
    {
        return Option::getForModule('ds.mmp');
    }

    /**
     * @param $path
     *
     * @return bool
     */
    protected function isWritable($path): bool
    {
        return is_writable($path);
    }

    /**
     * @return string
     */
    protected function getFolderPath(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->options['SAVE_APPS_PATH'];
    }

    /**
     * @param $filePath
     * @return false|string
     */
    public function getFileHash($filePath)
    {
        return hash_file('sha256', $filePath );
    }
}