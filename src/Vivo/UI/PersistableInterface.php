<?php
namespace Vivo\UI;

interface PersistableInterface
{
    public function saveState();
    public function loadState($state);

    public function getPath();
}
