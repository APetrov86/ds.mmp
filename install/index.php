<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\{ Localization\Loc, Loader };
use Bitrix\Iblock\{ TypeTable, ElementTable,
    PropertyTable };

Loc::loadMessages(__FILE__);

/**
 * Class ds_camk
 */
class ds_mmp extends CModule
{
    /** @var string */
    public $typeName = 'application_catalog';

    /** @var string */
    public $iblockName = 'application';

    /** @var string */
    public $MODULE_ID = 'ds.mmp';

    /** @var string */
    public $MODULE_VERSION;

    /** @var string */
    public $MODULE_VERSION_DATE;

    /** @var string */
    public $MODULE_NAME;

    /** @var string */
    public $MODULE_DESCRIPTION;

    /** @var integer */
    public $MODULE_SORT = 11;

    /** @var bool|string */
    protected $SITE_ID = false;

    /**
     * constructor.
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION       = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE  = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME          = Loc::getMessage('MMP_MODULE_NAME');
        $this->MODULE_DESCRIPTION   = Loc::getMessage('MMP_MODULE_DESCRIPTION');
        $this->PARTNER_NAME         = Loc::getMessage('MMP_PARTNER_NAME');
        $this->PARTNER_URI          = Loc::getMessage('MMP_PARTNER_URI');

        $this->SITE_ID              = \CSite::GetDefSite();
    }

    /**
     * @return bool
     * @throws \Bitrix\Main\LoaderException
     */
    public function doInstall()
    {
        $this->installAgent();
        $this->installIBlock();
        $this->installEvents();
        $this->installEventType();

        RegisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * @return bool
     */
    public function doUninstall()
    {
        $this->unInstallAgent();
        //$this->unInstallIBlock();
        $this->unInstallEvents();
        $this->unInstallEventType();

        /**
         * ?????????????? ??????????
         */
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);

        UnRegisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * @return void
     */
    public function installAgent()
    {
        \CAgent::AddAgent(
            '\\GPN\\MMP\\CheckHash\\CheckHashAgent::run();',
            'ds.mmp',
            'N',
            3600,
        );
    }

    /**
     * @return void
     */
    public function unInstallAgent()
    {
        \CAgent::RemoveModuleAgents('ds.mmp');
    }

    /**
     * @return void
     */
    public function installEvents()
    {
        RegisterModuleDependences(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            '\\GPN\\MMP\\Handler\\Hash',
            'OnAfterIBlockElementAddHandler'
        );

        /**
        RegisterModuleDependences(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\\GPN\\MMP\\Handler\\Hash',
            'OnAfterIBlockElementAddHandler'
        );*/
    }

    /**
     * @return void
     */
    public function unInstallEvents()
    {
        UnRegisterModuleDependences(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            '\\GPN\\MMP\\Handler\\Hash',
            'OnAfterIBlockElementAddHandler'
        );

        /**
        UnRegisterModuleDependences(
            'iblock',
            'OnBeforeIBlockElementUpdate',
            $this->MODULE_ID,
            '\\GPN\\MMP\\Handler\\Hash',
            'OnBeforeIBlockElementUpdateHandler'
        );*/
    }

    /**
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function installIBlock()
    {
        if (Loader::includeModule('iblock'))
        {
            $iblockType = TypeTable::getList([
                'select' => ['ID'],
                'filter' => ['=ID' => $this->typeName],
            ])->fetch();

            if ( $iblockType )
            {
                /**
                 * ???????? ?????? ?????? ????????
                 */
                CEventLog::Add([
                    'SEVERITY'      => 'WARNING',
                    'AUDIT_TYPE_ID' => 'HASH_APP',
                    'MODULE_ID'     => $this->MODULE_ID,
                    'ITEM_ID'       => 'IBLOCK Type',
                    'DESCRIPTION'   => Loc::getMessage('IBLOCK_TYPE_ERROR_ALREADY_EXIST'),
                ]);

            } else {
                /**
                 * @todo ???????????????????? ???? d7
                 * ?????????????? ?????? ??????????????????
                 */
                $obIBlockType   = new \CIBlockType;
                $arFields       = [
                    'ID'        => $this->typeName,
                    'SECTIONS'  => 'N',
                    'LANG'      => [
                        'ru'    => [
                            'NAME' => Loc::getMessage('IBLOCK_TYPE_NAME_RU'),
                        ],
                        'en'    => [
                            'NAME' => Loc::getMessage('IBLOCK_TYPE_NAME_EN'),
                        ]
                    ]
                ];

                $arIblockType = $obIBlockType->Add($arFields);

                if( !$arIblockType ) {
                    /**
                     * ???????? ?????? ???? ????????????????
                     */
                    CEventLog::Add([
                        'SEVERITY'      => 'WARNING',
                        'AUDIT_TYPE_ID' => 'HASH_APP',
                        'MODULE_ID'     => $this->MODULE_ID,
                        'ITEM_ID'       => 'IBLOCK Type',
                        'DESCRIPTION'   => $obIBlockType->LAST_ERROR,
                    ]);
                } else {
                    /**
                     * @todo ???????????????????? ???? d7
                     * ?????????????? IBlock
                     */
                    $arFields = [
                        'ACTIVE'                => 'Y',
                        'NAME'                  => Loc::getMessage('IBLOCK_NAME_RU'),
                        'IBLOCK_TYPE_ID'        => $this->typeName,
                        'CODE'                  => $this->iblockName,
                        'SITE_ID'               => ['s1'],
                        'INDEX_ELEMENT'         => 'N',
                        'VERSION'               => 2,
                        'FIELDS'    => [
                            'LOG_SECTION_ADD'       => ['IS_REQUIRED' => 'Y'],
                            'LOG_SECTION_EDIT'      => ['IS_REQUIRED' => 'Y'],
                            'LOG_SECTION_DELETE'    => ['IS_REQUIRED' => 'Y'],
                            'LOG_ELEMENT_ADD'       => ['IS_REQUIRED' => 'Y'],
                            'LOG_ELEMENT_EDIT'      => ['IS_REQUIRED' => 'Y'],
                            'LOG_ELEMENT_DELETE'    => ['IS_REQUIRED' => 'Y'],
                        ]
                    ];

                    $iblock     = new \CIBlock;
                    $arIBlockID = $iblock->Add($arFields);

                    if ( !$arIBlockID )
                    {
                        /**
                         * ???????? iblock ???? ????????????????
                         */
                        CEventLog::Add([
                            'SEVERITY'      => 'WARNING',
                            'AUDIT_TYPE_ID' => 'HASH_APP',
                            'MODULE_ID'     => $this->MODULE_ID,
                            'ITEM_ID'       => 'IBLOCK Type',
                            'DESCRIPTION'   => $arIBlockID->LAST_ERROR,
                        ]);
                    } else {
                        /**
                         * @todo ???????????????????? ???? d7
                         * ?????????????????? ???????????????? ??????????????????
                         */
                        $properties = [
                            // ????????
                            [
                                'NAME'          => Loc::getMessage('PROPERTY_NAME_FILE'),
                                'ACTIVE'        => 'Y',
                                'SORT'          => '100',
                                'MULTIPLE'      => 'N',
                                'CODE'          => 'FILE',
                                'PROPERTY_TYPE' => 'F',
                                'IS_REQUIRED'   => 'Y',
                                'IBLOCK_ID'     => $arIBlockID,
                                'FEATURES'      => [
                                    [
                                        'IS_ENABLED'   => 'Y',
                                        'MODULE_ID'    => 'iblock',
                                        'FEATURE_ID'   => 'DETAIL_PAGE_SHOW'
                                    ]
                                ]
                            ],
                            [
                                'NAME'          => Loc::getMessage('PROPERTY_NAME_URL'),
                                'ACTIVE'        => 'Y',
                                'SORT'          => '101',
                                'MULTIPLE'      => 'N',
                                'CODE'          => 'URL',
                                'PROPERTY_TYPE' => 'S',
                                'IS_REQUIRED'   => 'Y',
                                'IBLOCK_ID'     => $arIBlockID,
                                'HINT'          => Loc::getMessage('PROPERTY_NAME_URL_HINT'),
                                'COL_COUNT'     => 60,
                                'FEATURES'      => [
                                    [
                                        'IS_ENABLED'   => 'Y',
                                        'MODULE_ID'    => 'iblock',
                                        'FEATURE_ID'   => 'DETAIL_PAGE_SHOW'
                                    ]
                                ]
                            ],
                        ];

                        $objProperty = new \CIBlockProperty;

                        foreach ($properties as $property)
                        {
                            $propertyID = $objProperty->Add($property);

                            if (!$propertyID)
                            {
                                /**
                                 * ???????? ???????????????? ???? ??????????????????
                                 */
                                CEventLog::Add([
                                    'SEVERITY'      => 'WARNING',
                                    'AUDIT_TYPE_ID' => 'HASH_APP',
                                    'MODULE_ID'     => $this->MODULE_ID,
                                    'ITEM_ID'       => 'IBLOCK Type',
                                    'DESCRIPTION'   => $propertyID->LAST_ERROR,
                                ]);
                            }
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * ?????? ?????????????????????????? ?????????? ???????????????? ?????? ?????? ???????????????? ???????? ??????????????????, ?????????????????? ?? ????-???? ??????????????????, ?? ????????????????????????????
     * ?????????? ???? ???????????? (?????????? ?????????? ??????????????)
     *
     * @return void
     */
    public function unInstallIBlock() {}

    /**
     * ?????????????????????????? ???????????????? ?????????????? ?? ??????????????
     *
     * @todo: ???????????????????? ???? d7, ?????????? ?????????????? ?? LANG ??????????
     *
     * @return void
     */
    public function installEventType()
    {
        $obEventType    = new \CEventType;
        $arEventTypeID  = $obEventType->Add([
            'EVENT_NAME'  => 'HASH_ERROR',
            'NAME'        => 'HASH ERROR',
            'LID'         => 'ru',
            'DESCRIPTION' => "#ID# - ID ???????????? \r\n" .
                "#NAME# - ?????? ???????????????????? \r\n" .
                "#URL# - ???????????? ???? ?????????????? ???????????????????? \r\n" .
                "#EMAIL# - ???????????????????? ??????????????????, ?????????????????????? ?? ???????????????????? ????????????"
        ]);

        if ($arEventTypeID)
        {
            $obTemplate = new \CEventMessage;
            $arTemplate = $obTemplate->Add([
                'ID'            => $arEventTypeID,
                'ACTIVE'        => 'Y',
                'EVENT_NAME'    => 'HASH_ERROR',
                'LID'           => ['s1'],
                'EMAIL_FROM'    => '#DEFAULT_EMAIL_FROM#',
                'EMAIL_TO'      => '#EMAIL#',
                'SUBJECT'       => '?????? ?????? ???????????????????? "#NAME#" ???? ??????????????????',
                'BODY_TYPE'     => 'text',
                'MESSAGE'       => "?????? ?????? ???????????????????? '#NAME#' ???? ?????????????????? ?? '??????????????????' ?? ??????????????\r\n" .
                    "ID ???????????????????? ?????????????? - #ID#\r\n" .
                    "???????????? ???? ?????????????? ???????????????????? - #URL#",
            ]);
        }
    }

    /**
     * ?????? ?????????????????????????? ?????????? ?????????????????????? ?????? ???????????????? ???????????????? ?????????????? ?? ????????????????, ?? ????????????????????????????
     * ?????????? ???? ???????????? (?????????? ?????????? ??????????????)
     *
     * @return void
     */
    public function unInstallEventType() {}
}