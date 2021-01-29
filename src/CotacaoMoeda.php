<?php


namespace WCast\Services;


class CotacaoMoeda
{
    public function dolar(){

        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, 'https://ptax.bcb.gov.br/ptax_internet/consultarUltimaCotacaoDolar.do');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        ob_start();
        curl_exec($ch);
        curl_close($ch);
        $file_contents = ob_get_contents();
        ob_end_clean();
        $html = explode(' ', strip_tags($file_contents));
        return "$".trim($html[340]);
    }
}
