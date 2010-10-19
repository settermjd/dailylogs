<?php

class user_Form_UpdatePassword extends Zend_Form
{

    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
    }

    public function isValid($data)
    {
        $this->getElement('password')
            ->addValidator(new Zend_Validate_Identical($data['confirm_password']));
        return parent::isValid($data);
    }

}

