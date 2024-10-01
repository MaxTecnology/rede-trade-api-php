<?php

namespace Core;

class HttpDoc
{
    private $requests;

    public function __construct($file_path) {
        $this->requests = $this->extract_data_from_http_file($file_path);
    }

    private function extract_data_from_http_file($file_path) {
        $requests = [];
        $current_request = null;

        $file = fopen($file_path, "r");
        if ($file) {
            while (($line = fgets($file)) !== false) {
                if (preg_match('/^[A-Z]+ \S+/', $line)) {
                    if ($current_request !== null) {
                        $requests[] = $current_request;
                    }
                    list($method, $url) = explode(' ', trim($line), 2);
                    $current_request = ['method' => $method, 'url' => $url, 'headers' => [], 'body' => ''];
                } elseif ($current_request !== null) {
                    if (preg_match('/^\s+\S+:/', $line)) {
                        list($header_name, $header_value) = explode(':', trim($line), 2);
                        $current_request['headers'][trim($header_name)] = trim($header_value);
                    } elseif (trim($line) === '') {
                        continue;
                    } else {
                        $current_request['body'] = trim($line);
                    }
                }
            }

            fclose($file);
        }

        if ($current_request !== null) {
            $requests[] = $current_request;
        }

        return $requests;
    }

    public function render() {
        $output = '<div class="container mx-auto py-6">';
        $output .= '<h1 class="text-3xl font-bold mb-4">Documentação da API</h1>';

        foreach ($this->requests as $request) {
            $output .= '<div class="bg-gray-100 p-4 rounded mb-4">';
            $output .= '<h2 class="text-xl font-bold">' . $request['method'] . ' ' . $request['url'] . '</h2>';
            if (!empty($request['headers'])) {
                $output .= '<div class="mt-2">';
                $output .= '<h3 class="text-lg font-bold mb-1">Cabeçalhos</h3>';
                $output .= '<ul>';
                foreach ($request['headers'] as $header_name => $header_value) {
                    $output .= '<li><span class="font-semibold">' . $header_name . ':</span> ' . $header_value . '</li>';
                }
                $output .= '</ul>';
                $output .= '</div>';
            }
            if (!empty($request['body'])) {
                $output .= '<div class="mt-2">';
                $output .= '<h3 class="text-lg font-bold mb-1">Corpo da Solicitação</h3>';
                $output .= '<pre class="bg-gray-200 p-2">' . htmlentities($request['body']) . '</pre>';
                $output .= '</div>';
            }
            $output .= '</div>'; 
        }

        $output .= '</div>';
        return $output;
    }
}



