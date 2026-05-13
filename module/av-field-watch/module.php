<?php
namespace pineapple;

class AvFieldWatch extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'status':
                require(__DIR__ . '/api/status.php');
                break;

            default:
                $this->view = 'index';
                break;
        }
    }
}
