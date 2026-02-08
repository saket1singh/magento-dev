<?php
namespace MageMonk\SearchDesign\Model;

use Magento\Search\Model\AutocompleteInterface;

class SuggestionProvider
{
    /** @var AutocompleteInterface */
    private $autocomplete;

    /**
     * @param AutocompleteInterface $autocomplete
     */
    public function __construct(AutocompleteInterface $autocomplete)
    {
        $this->autocomplete = $autocomplete;
    }

    /**
     * Collect suggested search terms.
     *
     * @param string $query
     *
     * @return array
     */
    public function getSuggestions(string $query): array
    {
        $items = [];

        foreach ($this->autocomplete->getItems() as $item) {
            $data = $item->toArray();
            if (!empty($data['title'])) {
                $items[] = [
                    'text' => $data['title'],
                    'num_results' => $data['num_results'] ?? null
                ];
            }
        }

        if ($query !== '') {
            $exists = false;
            foreach ($items as $item) {
                if (mb_strtolower($item['text']) === mb_strtolower($query)) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                array_unshift(
                    $items,
                    [
                        'text' => $query,
                        'num_results' => null
                    ]
                );
            }
        }

        return $items;
    }
}
