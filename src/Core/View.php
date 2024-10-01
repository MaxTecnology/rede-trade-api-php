<?php

namespace Core;

class View
{
    public static function load($template, $data)
    {
        if (!file_exists($template)) {
            throw new \Exception("O arquivo de template '{$template}' não foi encontrado.");
        }
        $output = file_get_contents($template);
        foreach ($data as $key => $value) {
            $output = str_replace('{' . $key . '}', $value, $output);
            $output = preg_replace('/%for ' . $key . ' in e%/', '<?php foreach($' . $key . ' as $' . $key . '): ?>', $output);
            $output = preg_replace('/%endfor ' . $key . '%/', '<?php endforeach; ?>', $output);
            $output = preg_replace('/#if ' . $key . ' = \'(.+?)\' #/', '<?php if($' . $key . ' == \'$1\'): ?>', $output);
            $output = preg_replace('/#endif ' . $key . ' #/', '<?php endif; ?>', $output);
            $output = preg_replace('/%include ' . $key . ' %/', '<?php include $' . $key . '; ?>', $output);
        }
        eval(' ?>' . $output . '<?php ');
    }
}
