<?php

namespace Hcode;

class Model
{
    private $values = [];

    //__call metodo magico
    public function __call($name, $args)
    {
        //pegar as três primeira letra da variavel
        $method = substr($name, 0, 3);
        //pegar todas as letra aparte do terceiro indece
        $fieldName = substr($name, 3, strlen($name));

        switch ($method) {
            case 'get':
                return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : null;
                break;

            case 'set':
                $this->values[$fieldName] = $args[0];
                break;
        }
    }

    public function setData($data = array())
    {
        foreach ($data as $key => $value) {
            $this->{"set" . $key}($value);
        }
    }

    public function getValues()
    {
       
        return $this->values;
    }
}
