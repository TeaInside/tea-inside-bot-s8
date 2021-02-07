<?php

/**
 * @param string $configName
 * @return bool
 */
function loadConfig(string $configName): void
{
  require BASEPATH."/config/".$configName.".php";
}

/**
 * @param string $str
 * @return string
 */
function e(string $str): string
{
  return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
}

/**
 * @param float $seconds
 * @return void
 */
function co_sleep(float $seconds = -1)
{
  \Co\System::sleep($seconds);
}
