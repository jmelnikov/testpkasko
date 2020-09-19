<?php


class GetBooksClass
{
    private $curlHandle, $booksList;

    public function __construct()
    {
        $this->curlHandle = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->curlHandle);
    }

    private function getCurlData(string $url): string
    {
        $userAgents = [
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:70.0) Gecko/20100101 Firefox/70.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 11; Pixel 3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.101 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; arm; Android 10; Redmi 8A) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 YaBrowser/20.4.4.76.00 SA/1 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4143.2 Safari/537.36'
        ];

        curl_setopt($this->curlHandle, CURLOPT_URL, $url);
        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, array_rand($userAgents, 1));
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);

        return curl_exec($this->curlHandle);
    }

    protected function getBooksList(string $url): bool
    {
        $response = $this->getCurlData($url);
        $matches = [];
        preg_match_all('/<script data-skip-moving="true">window.digitalData\s=\s(.*);<\/script>/i', $response, $matches);

        $listing = json_decode($matches[1][0], true);

        if(!$listing) {
            return false;
        }

        foreach ($listing['listing']['items'] as $book) {
            if(!$this->getBookInfo($book)) {
                return false;
            }
        }

        return true;
    }

    private function getBookInfo(array $response): bool
    {
        $book['id'] = $response['id'] ?? null;
        $book['url'] = $response['url'] ?? null;
        $book['author'] = $response['author'] ?? null;
        $book['name'] = $response['name'] ?? null;
        $book['price'] = $response['unitSalePrice'] ?? null;
        $book['image'] = $response['imageUrl'] ?? null;

        if(in_array(null, $book)) {
            return false;
        }

        if(!$this->getBookAdditionalInfo($book)) {
            return false;
        }

        $this->booksList[] = $book;

        return true;
    }

    private function getBookAdditionalInfo(array &$book): bool
    {
        $response = $this->getCurlData($book['url']);

        if(!$response) {
            return false;
        }

        $matches = [];

        preg_match('/Год издания:<\/span><span class="item-tab__chars-value">([0-9]+)<\/span>/iu', $response, $matches);
        $book['year'] = $matches[1];

        $response = $this->getCurlData('https://book24.ru/local/components/book24/widget.product.card/ajax/delivery_info.php?product_id='.$book['id']);
        $data = json_decode($response, true);
        preg_match_all('/от\s([0-9]+)\s/i', $data['DATA']['delivery_price_block'], $matches);

        sort($matches[1], SORT_ASC);
        $book['deliveryPrice'] = $matches[1][0];

        return true;
    }

    public function getBooks()
    {
        return $this->booksList;
    }

    public function run(): bool
    {
        if(!$this->getBooksList('https://book24.ru/catalog/programmirovanie-1361/')) {
            return false;
        }

        return true;
    }
}