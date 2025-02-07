<?php

namespace WahyuLingu\AutoWAFu\Mode;

use WahyuLingu\AutoWAFu\Driver\DatabaseDriver;
use WahyuLingu\AutoWAFu\Driver\WhatsappDriver;

class AutomaticMode
{
    public function __construct(
        protected readonly WhatsappDriver $whatsappDriver,
        protected readonly DatabaseDriver $databaseDriver) {}

    public function run() {}
}
