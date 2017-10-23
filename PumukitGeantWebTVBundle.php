<?php

namespace Pumukit\Geant\WebTVBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitGeantWebTVBundle extends Bundle
{
  const VERSION = '1.2.0-dev';
  public function getParent()
  {
    return 'PumukitWebTVBundle';
  }
}
