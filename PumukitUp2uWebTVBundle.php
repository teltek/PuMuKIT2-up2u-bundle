<?php

namespace Pumukit\Up2u\WebTVBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitUp2uWebTVBundle extends Bundle
{
  const VERSION = '1.0.0-dev';
  public function getParent()
  {
    return 'PumukitWebTVBundle';
  }
}
