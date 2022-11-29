<?php
namespace GPN\MMP\Handler;

use Bitrix\Main\Loader;
use GPN\MMP\CheckHash\Helpers\GeneralHelper;

/**
 * Class Hash
 *
 * @package GPN\MMP\Handler
 */
class Hash
{
    public static $disableHandler = false;

    /**
     * @var bool
     */
    public static $iblockType = 'application_catalog';

    /**
     * @todo - рефакторинг, вынести в 1 метод
     *
     * @param $arFields
     * @return void
     * @throws \Bitrix\Main\LoaderException
     */
    public static function OnAfterIBlockElementAddHandler(&$arFields)
    {
        if ($arFields['RESULT'])
        {
            $iblockID   = self::getIblockID(self::$iblockType);

            if ($iblockID != false)
            {
                $arProps = \CIBlockElement::GetProperty($iblockID, $arFields['ID'], [], ['CODE' => 'FILE']);

                if( $row = $arProps->Fetch() )
                {
                    if ( $row['VALUE'] )
                    {
                        $rsFile     = \CFile::GetByID($row['VALUE']);
                        $filePath   = $_SERVER['DOCUMENT_ROOT'] . $rsFile->fetch()['SRC'];

                        $el         = new \CIBlockElement;
                        $res = $el->Update($arFields['ID'], [
                            "CODE"  => (new GeneralHelper())->getFileHash($filePath)
                        ]);

                        return;
                    }
                }
            }
        }
    }

    /**
     * @todo - рефакторинг, вынести в 1 метод
     *
     * @param $arFields
     * @return bool
     * @throws \Bitrix\Main\LoaderException

    public static function OnBeforeIBlockElementUpdateHandler(&$arFields)
    {
        $iblockID   = self::getIblockID(self::$iblockType);

        if ($iblockID != false)
        {
            $arProps = \CIBlockElement::GetProperty($iblockID, $arFields['ID'], [], ['CODE' => 'FILE']);

            if( $row = $arProps->Fetch() )
            {
                if ( $row['VALUE'] )
                {
                    $rsFile     = \CFile::GetByID($row['VALUE']);
                    $filePath   = $_SERVER['DOCUMENT_ROOT'] . $rsFile->fetch()['SRC'];

                    $arFields["CODE"]  = (new GeneralHelper())->getFileHash($filePath);

                    return true;
                }
            }
        }

        return false;

    }* */

    /**
     * @param $type
     * @return false|int
     * @throws \Bitrix\Main\LoaderException
     */
    protected static function getIblockID($type)
    {
        if (Loader::includeModule('iblock')) {
            $arRes = \CIBlock::GetList([], ['TYPE' => $type], true);

            while($row = $arRes->Fetch()) {
                return (int) $row['ID'];
            }
        }

        return false;
    }
}