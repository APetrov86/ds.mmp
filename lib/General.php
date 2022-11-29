<?php
namespace GPN\MMP\CheckHash;

use Bitrix\Main\Loader;
use GPN\MMP\CheckHash\Helpers\GeneralHelper;

/**
 * Class General
 *
 * @package GPN\MMP\CheckHash
 */
class General
{
    /**
     * @var string
     */
    private $_iblockType = 'application_catalog';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    public function __construct()
    {
        $this->items = $this->getApps();
    }

    /**
     * @todo Перенести сообщения в LANG
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function run()
    {
        if (!empty($this->items))
        {
            $helper     = new GeneralHelper();
            $options    = $helper->getOptions();

            foreach ($this->items as $item)
            {
                /**
                 * получаем имя файла из ссылки
                 */
                $fileAppUrl     = $item['PROPERTY_URL_VALUE'];
                $fileAppUrlArr  = explode('/', $fileAppUrl);

                /**
                 * получаем путь куда сохраняем на сервере
                 */
                $tempDir        = $options['SAVE_APPS_PATH'] ?? '/upload/apps';
                $toPath         = $_SERVER['DOCUMENT_ROOT'] . $tempDir . '/' . array_pop($fileAppUrlArr);

                /**
                 * копируем
                 */
                if (!copy($item['PROPERTY_URL_VALUE'],  $toPath) )
                {
                    \CEventLog::Add([
                        'SEVERITY'      => \CEventLog::SEVERITY_INFO,
                        'AUDIT_TYPE_ID' => 'HASH_APP',
                        'MODULE_ID'     => 'ds.mpp',
                        'ITEM_ID'       => 'HASH',
                        'DESCRIPTION'   => 'Файл не скопирован',
                    ]);

                    return false;
                }

                $tempAppHash = $helper->getFileHash($toPath);

                if ($item['CODE'] !== $tempAppHash)
                {
                    /**
                     * Хеш не совпадает
                     */
                    \CEventLog::Add([
                        'SEVERITY'      => \CEventLog::SEVERITY_WARNING,
                        'AUDIT_TYPE_ID' => 'HASH_APP',
                        'MODULE_ID'     => 'ds.mpp',
                        'ITEM_ID'       => 'HASH ERROR',
                        'DESCRIPTION'   => 'Ошибка, хеш не совпадает',
                    ]);

                    $sendEvent = \Bitrix\Main\Mail\Event::send([
                        'EVENT_NAME' => 'HASH_ERROR',
                        'LID'        => 's1',
                        'C_FIELDS'   => [
                            'EMAIL'                 => $options['EMAIL_TO'],
                            'NAME'                  => $item['NAME'],
                            'ID'                    => $item['ID'],
                            'URL'                   => $item['PROPERTY_URL_VALUE'],
                        ]
                    ]);

                    if (!$sendEvent)
                    {
                        //...
                    }
                }

                @unlink($toPath);
            }

            return true;
        }

        \CEventLog::Add([
            'SEVERITY'      => \CEventLog::SEVERITY_INFO,
            'AUDIT_TYPE_ID' => 'HASH_APP',
            'MODULE_ID'     => 'ds.mpp',
            'ITEM_ID'       => 'HASH',
            'DESCRIPTION'   => 'Нет записей для выборки',
        ]);

        return false;
    }

    /**
     * @todo Перенести сообщения в LANG
     *
     * @return array|false
     * @throws \Bitrix\Main\LoaderException
     */
    protected function getApps()
    {
        if ( Loader::includeModule('iblock') )
        {
            $items      = [];

            $arSelect   = ['ID', 'NAME', 'CODE', 'PROPERTY_URL', 'PROPERTY_FILE'];
            $arFilter   = [
                'IBLOCK_TYPE' => $this->_iblockType, 'ACTIVE' => 'Y'
            ];

            $arResult = \CIBlockElement::GetList([], $arFilter, false, [], $arSelect);

            while ($ob = $arResult->GetNextElement()) {
                $items[] = $ob->GetFields();
            }

            return $items;
        }

        \CEventLog::Add([
            'SEVERITY'      => \CEventLog::SEVERITY_ERROR,
            'AUDIT_TYPE_ID' => 'HASH_APP',
            'MODULE_ID'     => 'ds.mpp',
            'ITEM_ID'       => 'HASH ERROR',
            'DESCRIPTION'   => 'Модуль iblock не загружен',
        ]);

        return false;
    }
}