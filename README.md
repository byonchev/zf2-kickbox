ZF2Kickbox is a module that provides **Zend Framework 2** validator for email verification using http://kickbox.io

# Installation
1. Add ```"byonchev/zf2-kickbox": "dev-master"``` to your ```composer.json``` and run ```php composer.phar update```
2. Add ```ZF2Kickbox``` to your ```application.config.php```:
```php
<?php
return [
    'modules' => [
        ...
        'ZF2Kickbox'
    ]
    ...
];
```

# Usage

*First, you will need to get an API key from https://kickbox.io/app/api/settings after creating an account (if you don't have one already)*

1. Programmatic way
```php
<?php

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use ZF2Kickbox\Validator\Kickbox;

class RegistrationForm extends Form
{
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);
        
        $this->add(new Element('email'));
        
        $inputFilter      = new InputFilter();
        $input            = new Input('email');
        
        $kickboxValidator = new Kickbox(['apiKey' => 'xxxxxxxxxxxxxxxxx']);
        
        $input->getValidatorChain()->attach($kickboxValidator);

        $inputFilter->add($input);
        $this->setInputFilter($inputFilter);
    }
}
```
