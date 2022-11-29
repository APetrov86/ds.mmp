<?php
namespace GPN\MMP\CheckHash;

/**
 * Class CheckHashAgent
 *
 * @package GPN\MMP\Agents
 */
class CheckHashAgent
{
    /**
     * @return string
     */
    public static function run()
    {
        $checkHash = new \GPN\MMP\CheckHash\General();
        $checkHash->run();

        return '\\GPN\\MMP\\CheckHash\\CheckHashAgent::run();';
    }
}