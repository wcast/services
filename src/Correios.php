<?php

namespace WCast\Services;

class Correios
{
    public function calculaFrete($data = [])
    {
        $cep = str_replace('-', '', $data['cep_destino']);
        $url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?';
        $query = [
            'sCepOrigem'            => getenv('FRETE_CEP_ORIGEM'),
            'sCepDestino'           => $cep,
            'nVlPeso'               => 1,
            'nVlValorDeclarado'     => 100,
            'nCdServico'            => 40010,
            'StrRetorno'            => 'xml',
            'nIndicaCalculo'        => 3
        ];
        $url .= http_build_query($query);
        $xml = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml->cServico->Erro == 0) {
            $retorno['status'] = 200;
            $retorno['valor'] = $xml->cServico->Valor;
            $retorno['prazo'] = $xml->cServico->PrazoEntrega;
        } else {
            $retorno['status'] = 500;
            $retorno['erro'] = $xml->cServico->MsgErro;
        }
        return $retorno;
        die;
    }
}
