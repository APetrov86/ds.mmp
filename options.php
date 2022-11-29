<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @global CMain $APPLICATION */
/** @global string $mid */

global $APPLICATION, $REQUEST_METHOD;

use Bitrix\Main\{ Localization\Loc, Config\Option,
    Loader };
use GPN\MMP\CheckHash\Helpers\GeneralHelper;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule($mid)) {
    CAdminMessage::ShowMessage(Loc::getMessage('MODULE_NOT_INSTALLED'));
    return;
}

$module_id  = $mid; // required for group rights
$RIGHT      = $APPLICATION->GetGroupRight($mid);

if ($RIGHT < 'R') {
    return;
}

$save       = !empty($_REQUEST['save']) ? 'Y' : '';
$restore    = !empty($_REQUEST['restore']) ? 'Y' : '';
$Update     = $_REQUEST['save'] . $_REQUEST['apply'];

if (($RIGHT === 'W') && $REQUEST_METHOD == 'POST' && $save . $restore <> '' && check_bitrix_sessid()) {
    if($restore <> '') {
        Option::delete($mid);
    } else {
        Option::set($mid, 'SAVE_APPS_PATH', $_REQUEST['SAVE_APPS_PATH']);
        Option::set($mid, 'AGENT_INTERVAL', $_REQUEST['AGENT_INTERVAL']);
        Option::set($mid, 'EMAIL_TO', $_REQUEST['EMAIL_TO']);
    }
}

$settings = [
    'SAVE_APPS_PATH'    => Option::get($mid, 'SAVE_APPS_PATH'),
    'AGENT_INTERVAL'    => Option::get($mid, 'AGENT_INTERVAL'),
    'EMAIL_TO'          => Option::get($mid, 'EMAIL_TO'),
];

/**
 * POST
 */
if ($REQUEST_METHOD == 'POST')
{
    $request    = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
    $action     = $request->getPost('action');

    if ($action) {
        $method = key($action);

        try {

            $doAction = new GeneralHelper();
            $doAction->$method();

        } catch (Error $e) {
            $doAction->status['MESSAGE'] = $e->getMessage();
        }

        if ($doAction instanceof GeneralHelper) {
            CAdminMessage::ShowMessage([
                'TYPE'      => $doAction->status['TYPE'],
                'MESSAGE'   => $doAction->status['MESSAGE'],
            ]);
        }
    }
}

/**
 * Настройки
 */
$arTabs = [
    [
        'DIV'   => 'edit1',
        'TAB'   => Loc::getMessage('TAB_GENERAL_TAB'),
        'TITLE' => Loc::getMessage('TAB_GENERAL_TITLE'),
        'ICON'  => 'settings',
    ],
    [
        'DIV'       => 'edit3',
        'TAB'       => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE'     => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'),
        'ICON'      => 'support_settings',
    ],
];

$agentInfo  = GeneralHelper::getAgentInfo($mid);

/**
 * Форма
 */
$tabControl = new CAdminTabControl('tabControl', $arTabs);
$tabControl->Begin();
?>
<form method='POST' action='<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANGUAGE_ID ?>' id='main_form'>
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->BeginNextTab(); ?>
        <tr class='heading'>
            <td colspan='2'><?= Loc::getMessage('AGENT_TITLE') ?></td>
        </tr>
        <?php if ($agentInfo): ?>
            <tr>
                <td><?= Loc::getMessage('LAST_EXEC') ?></td>
                <td> <?= $agentInfo['LAST_EXEC'] ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td><?= Loc::getMessage('RUN_AGENT') ?></td>
            <td>
                <input type='submit' name='action[runAgent]' value='<?= Loc::getMessage('RUN_AGENT_BTN') ?>'
                       onclick='return confirm("<?= AddSlashes(Loc::getMessage('RUN_AGENT_BTN_ALERT')) ?>")'>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('AGENT_INTERVAL_TITLE') ?></td>
            <td>
                <input name='AGENT_INTERVAL' type='text' value='<?= $settings['AGENT_INTERVAL'] ?? '' ?>'
                       size='40' required>

                <input type='submit' name='action[addAgent]' value='<?= Loc::getMessage('RECREATE_AGENT')?>'
                       onclick='return confirm("<?= AddSlashes(Loc::getMessage('RECREATE_AGENT_ALERT')) ?>")'>
            </td>
        </tr>

        <tr class='heading'>
            <td colspan='2'><?= Loc::getMessage('SETTINGS_TITLE') ?></td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('EMAIL_TO_TITLE') ?></td>
            <td>
                <input name='EMAIL_TO' type='email' value="<?= $settings['EMAIL_TO'] ?? '' ?>"
                       placeholder='<?= Loc::getMessage('EMAIL_TO_PLACEHOLDER') ?>' size='40' required>

            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('SAVE_APPS_PATH_TITLE') ?></td>
            <td>
                <input name='SAVE_APPS_PATH' type='text' value="<?= $settings['SAVE_APPS_PATH'] ?? '' ?>"
                       placeholder='<?= Loc::getMessage('SAVE_APPS_PATH_PLACEHOLDER') ?>' size='40' required>
                <input type='submit' name='action[checkWritable]' value='<?= Loc::getMessage('CHECK_PERMISSIONS')?>'>
            </td>
        </tr>

    <?php $tabControl->BeginNextTab(); ?>

    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php') ?>

    <?php
        $tabControl->Buttons(['btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false, 'disabled' => ($RIGHT < 'W')]);
        $disabled = ($RIGHT < 'W') ? 'disabled' : '';
    ?>

    <input type='reset' name='reset' value='<?= Loc::getMessage('MAIN_RESET') ?>' <?= $disabled ?>>

    <input type='submit' name='restore' title='<?= Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>'
           onclick='return confirm("<?= AddSlashes(Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>")'
           value='<?= Loc::getMessage('RESTORE_DEFAULTS') ?>' <?= $disabled ?>>

    <?php $tabControl->End(); ?>
</form>