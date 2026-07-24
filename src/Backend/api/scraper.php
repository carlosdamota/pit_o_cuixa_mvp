<?php

/**
 * Pit o Cuixa — Products API Controller
 *
 * Public read-only API endpoints for products and categories.
 * Every response uses the uniform JSON envelope.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types = 1); //Evitamos conversiones en el tipo de los datos

namespace Pit\Cuixa\Backend\Api;

class PodructScraper{

    /**
     * Descarga y procesamiento de la carta del restaurante, devuelve los productos encontrados
     * 
     * 
     * @return array
     */


    public function scraper(): array{

        //Iniciamos conexion HTML
        $url = "https://pitocuixa.last.shop/l/pit-o-cuixa";
        $curl_handler = curl_init($url);

        //Config Handler
        curl_setopt_array($curl_handler, [
            CURLOPT_RETURNTRANSFER => true, #Devolver HTML como texto
            //CURLOPT_FOLLOWLOCATION => true, #Habilitar auto-redireccionamiento
            CURLOPT_USERAGENT => "Mozilla/5.0" #Agente Mozilla por mayor compabilidad
        ]);

        //Ejecutamos Handler
        $url_html = curl_exec($curl_handler);

        if (!$url_html){
            throw new \RuntimeException("Error en la obtencion de la carta: ".curl_error($curl_handler));
        }

        return $this->parseHtml($url_html);


    }

    /**
     * Procesador de datos html para convertirlos en JSON
     * @param string $html
     * @return array
     */

    private function parseHtml(string $html) : array{
        
        //Ignoramos los warnings generados por fallos en el HTML
        #libxml_use_internal_errors(true);

        //Instanciamos la clase DOM
        #$dom = new \DOMDocument();

        #$dom->loadHTML($html);

        #$xpath = new \DOMXPath($dom);

        #return [];
        
        echo $html;
        exit;
    }
}

?>