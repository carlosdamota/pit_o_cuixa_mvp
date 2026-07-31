<?php

/**
 * Pit o Cuixa — WebScraper API Controller
 *
 * Public read-only API endpoints for products and categories.
 * Every response uses the uniform JSON envelope.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types = 1); //Evitamos conversiones en el tipo de los datos

namespace Pit\Cuixa\Backend\Api;

class WebScraper{

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
     * @param string $url
     * @return array
     */

    private function parseHtml(string $url) : array{
        
        //Ignoramos los warnings generados por fallos en el HTML
        libxml_use_internal_errors(true);

        //Instanciamos la clase DOM
        $dom = new \DOMDocument();

        $dom->loadHTML($url);
        $xpath = new \DOMXPath($dom);

        $counter = 1;

        //Aislamos la sección donde esta toda la carta
        $carta = $xpath->query("//*[contains(@class, 'md:grid-cols-[repeat(auto-fit,minmax(14rem,1fr))]')]");

        //Bloque de control
        if($carta->length !== 1){
            throw new \RuntimeException("Error al obtener datos:\nDatos esperados: 1\n Datos encontrados: {carta->length}");
        }
        else{
            $carta = $carta->item(0); #Convierte de DOMNodeList a DOMElement
        }

        $data = [];
        $category = "";

        foreach($carta->childNodes as $c){
            
            //Ignoramos nodos no HTML
            if(!($c instanceof \DOMElement)){
                continue;
            }

            //Asignamos categoria normalizada
            if($c->tagName === "div"){
                $h2 = $xpath->query(".//h2", $c)->item(0);
                $category = $this->mapCategory($h2->textContent);
                continue;
            }

            if($c->tagName === "a"){
                //link
                $link = $c->getAttribute('href');
                $slug = basename($link);

                //datos
                $name = trim($xpath->query(".//h3", $c)->item(0)?->textContent ?? '');
                $price = trim($xpath->query(".//p[contains(text(),'€')]", $c)->item(0)->textContent ?? '');
                $descr = trim($xpath->query(".//p[not(contains(text(),'€'))]", $c)->item(0)->textContent ?? '');
                $urL_image = $xpath->query(".//img", $c)->item(0)?->getAttribute('src') ?? '';

                //limpiamos el link de cualquier formato
                $id_image = basename($urL_image);
                $image = "https://res.cloudinary.com/lastpos/image/upload/" . $id_image;

                $data[] = [
                    'slug' => $slug,
                    'name_es' => $name,
                    'price' => $price,
                    'description_es' => $descr,
                    'image_url' => $image,
                    'last_shop_url' => $link,
                    'category' => $category,
                    'sort_order' => $counter++,
                    // Scraped items are delivery-only; dine-in availability is
                    // managed manually from the admin panel.
                    'is_dine_in'  => false,
                    'is_delivery' => true
                ];
            }
        }

        return $data;
    }
    
    /**
     * Mapeador de Categorias.
     * @param string $category
     * @return void
     */
    private function mapCategory(string $category) : string{
        
        $category = mb_strtolower(trim($category));
    
        return match($category){
            'menu de pollo','menu diario llevar ( de lunes a viernes)','menu especial casal' => 'menus',
            'platos principales','arroces y fideua por encargo min 24h' => 'platos',
            'croquetas','ensaladas','patatas' => 'entrantes',
            'bebidas' => 'bebidas',
            'postre' => 'postres',
            'portes','portes-fuera' => 'portes',
            default => 'otros'
        };
    }
}
?>