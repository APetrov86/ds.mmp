<?php

/**
 * registerAutoLoadClasses
 */
\Bitrix\Main\Loader::registerAutoLoadClasses('ds.mmp', [
    /**
     * helpers
     */
    \GPN\MMP\CheckHash\Helpers\GeneralHelper::class => 'helpers/GeneralHelper.php',

    /**
     * agent
     */
    \GPN\MMP\CheckHash\CheckHashAgent::class        => 'lib/agents/CheckHashAgent.php',

    /**
     * general class
     */
    \GPN\MMP\CheckHash\General::class               => 'lib/General.php',
    \GPN\MMP\Handler\Hash::class                    => 'lib/handler/Hash.php',
]);