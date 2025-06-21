<?php

class Sj4webSavformSavRequestModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'form_action' => $this->context->link->getModuleLink('sj4websavform', 'savrequest'),
        ]);

        $this->setTemplate('module:sj4websavform/views/templates/front/sav_request.tpl');
    }
}
