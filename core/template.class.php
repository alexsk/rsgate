<?php

class SimpleTemplate {

    var $variables = array();

    function assign($key, $value = '') {
        $this->variables['{'.$key.'}'] = $value;
    }

    function fetch($fileName) {
        $content = implode('',file($fileName));

        $keys = array_keys($this->variables);
        $values = array_values($this->variables);

        $content = str_replace($keys, $values, $content);
        $content = preg_replace('/\{\S+\}/', '', $content);

        return $content;
    }

    function display($fileName) {
        print $this->fetch($fileName);
    }

}
