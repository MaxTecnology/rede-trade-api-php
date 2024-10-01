<?php

namespace Core;

class Gem
{
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=';
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function generateContent($text)
    {
        $url = $this->api_url . $this->api_key;
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $text
                        )
                    )
                )
            )
        );
        $data_string = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            )
        );

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
}
