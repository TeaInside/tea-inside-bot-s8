<?php

/**
 * @param string $filename
 * @return mixed
 */
function isolate_require(string $filename)
{
  return require $filename;
}
