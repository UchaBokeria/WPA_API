<?php

    function TemplateBuild($object, $template)
    {
        foreach ($object as $key => $value) 
            $template = str_replace("{$key}", $value, $template);
        var_dump($template);die();
        return $template;
    }
