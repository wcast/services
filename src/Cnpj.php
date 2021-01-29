<?php
namespace WCast\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class Cnpj
{
    public $pasta_cookies = '';
    public $cookie_file = '';
    public $cookie_content = '';
    public $cookie = '';

    private $attributes = [
        'NOME EMPRESARIAL' => 'razao_social',
        'TÍTULO DO ESTABELECIMENTO (NOME DE FANTASIA)' => 'nome_fantasia',
        'CÓDIGO E DESCRIÇÃO DA ATIVIDADE ECONÔMICA PRINCIPAL' => 'cnae_principal',
        'CÓDIGO E DESCRIÇÃO DA NATUREZA JURÍDICA' => 'cnaes_secundario',
        'LOGRADOURO' => 'logradouro',
        'NÚMERO' => 'numero',
        'COMPLEMENTO' => 'complemento',
        'CEP' => 'cep',
        'BAIRRO/DISTRITO' => 'bairro',
        'MUNICÍPIO' => 'cidade',
        'UF' => 'uf',
        'SITUAÇÃO CADASTRAL' => 'situacao_cadastral',
        'DATA DA SITUAÇÃO CADASTRAL' => 'situacao_cadastral_data',
        'DATA DA SITUAÇÃO ESPECIAL' => 'situacao_especial',
        'TELEFONE' => 'telefone',
        'ENDEREÇO ELETRÔNICO' => 'email',
        'ENTE FEDERATIVO RESPONSÁVEL (EFR)' => 'responsavel',
        'DATA DE ABERTURA' => 'data_abertura'
    ];

    public function __construct()
    {
        session_start();
        $storage = Storage::get('APP');
        $this->pasta_cookies = storage_path('wcast/cookies/');
        $this->cookie_file = $this->pasta_cookies . 'cnpj_' . session_id();
        if (!file_exists($this->cookie_file)) {
            $file = fopen($this->cookie_file, 'a+');
            fclose($file);
            chmod($this->cookie_file, 0777);
        }
    }

    public function getCaptcha($id_session = 0)
    {
        $this->pasta_cookies = storage_path('wcast/cookies/');
        $this->cookie_file = $this->pasta_cookies . 'cnpj_' . session_id();
        $ch = curl_init('http://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/Cnpjreva_Solicitacao_CS.asp');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        $this->cookie_content = '';
        $file = fopen($this->cookie_file, 'r');
        while (!feof($file)) {
            $this->cookie_content .= fread($file, 1024);
        }
        fclose($file);
        $linha = explode("\n", $this->cookie_content);
        for ($contador = 4; $contador < count($linha) - 1; $contador++) {
            $explodir = explode(chr(9), $linha[$contador]);
            $this->cookie .= trim($explodir[count($explodir) - 2]) . "=" . trim($explodir[count($explodir) - 1]) . "; ";
        }
        $this->cookie = substr($this->cookie, 0, -2);
        $ch = curl_init('http://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/captcha/gerarCaptcha.asp');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($ch, CURLOPT_REFERER, 'http://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/Cnpjreva_Solicitacao_CS.asp');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return 'data:image/png;base64,' . base64_encode($result);
    }

    public function getHeader()
    {
        return [
            'servicos.receita.fazenda.gov.br',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:53.0) Gecko/20100101 Firefox/53.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ];
    }

    public function consultaCNPJ($post = [])
    {
        $this->cookie_content = '';

        if (!file_exists($this->cookie_file)) {
            return false;
        } else {
            $file = fopen($this->cookie_file, 'r');
            while (!feof($file)) {
                $this->cookie_content .= fread($file, 1024);
            }
            fclose($file);
            $linha = explode("\n", $this->cookie_content);
        }

        for ($contador = 4; $contador < count($linha) - 1; $contador++) {
            $explodir = explode(chr(9), $linha[$contador]);
            $this->cookie .= trim($explodir[count($explodir) - 2]) . "=" . trim($explodir[count($explodir) - 1]) . "; ";
        }

        $this->cookie = substr($this->cookie, 0, -2);
        if (!strstr($this->cookie_content, 'flag	1')) {
            $linha = chr(10) . chr(10) . 'servicos.receita.fazenda.gov.br	FALSE	/	FALSE	0	flag	1' . chr(10);
            $this->cookie = str_replace(chr(10) . chr(10), $linha, $this->cookie_content);
            unlink($this->cookie_file);
            $file = fopen($this->cookie_file, 'w');
            fwrite($file, $this->cookie);
            fclose($file);
            $this->cookie .= ';flag=1';
        }
        $data = [
            'origem' => 'comprovante',
            'cnpj' => $post['cnpj'],
            'txtTexto_captcha_serpro_gov_br' => $post['captcha'],
            'search_type' => 'cnpj'
        ];
        $data = http_build_query($data, NULL, '&');
        $headers = array(
            'Host: servicos.receita.fazenda.gov.br',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:53.0) Gecko/20100101 Firefox/53.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Accept-encoding: gzip',
            'Accept-Charset: utf-8'

        );
        $ch = curl_init('http://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/valida.asp');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_REFERER, 'http://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/Cnpjreva_Solicitacao_CS.asp');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($ch);
        curl_close($ch);
        return $this->parseCnpj($html);
    }

    public function parseCnpj($html = '')
    {
        $resultUTF8 = mb_convert_encoding($html, 'utf-8', 'ISO-8859-15');
        $result = [];
        $crawler = new Crawler($resultUTF8);
        foreach ($crawler->filter('td') as $td) {
            $td = new Crawler($td);
            $info = $td->filter('font:nth-child(1)');
            if ($info->count() > 0) {
                $key = utf8_decode(trim(strip_tags(preg_replace('/\s+/', ' ', $info->html()))));
                $attr = isset($this->attributes[$key]) ? $this->attributes[$key] : null;
                if ($attr === null) {
                    continue;
                }
                $bs = $td->filter('font > b');
                foreach ($bs as $b) {
                    $b = new Crawler($b);

                    $str = trim(preg_replace('/\s+/', ' ', $b->html()));
                    $attach = utf8_decode(htmlspecialchars_decode($str));

                    if ($bs->count() == 1)
                        $result[$attr] = $attach;
                    else
                        $result[$attr][] = $attach;
                }
            }
        }
        return $result;
    }

    public function pega_o_que_interessa($inicio, $fim, $total)
    {
        $interesse = str_replace($inicio, '', str_replace(strstr(strstr($total, $inicio), $fim), '', strstr($total, $inicio)));
        return ($interesse);
    }

}
