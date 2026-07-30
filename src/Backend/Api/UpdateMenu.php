<?

/**
 * Pit o Cuixa — UpdateMenu API Controller
 *
 * Public read-only API endpoints for products and categories.
 * Every response uses the uniform JSON envelope.
 *
 * @package Pit\Cuixa\Backend\Api
 */


declare(strict_types = 1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Api\WebScraper;
use Pit\Cuixa\Backend\Db\Repositories\Product;

class UpdateMenu {
    
    /**
     * Inserta los datos en las bases de datos
     * @return array
     */

    public function update() : array{

        $scraper = new WebScraper();
        $repo = new Product();

        $products = $scraper->scraper();
        $repo->sync($products);

        return [
            'status' =>'ok'
        ];
    }

}

?>

