<?php

namespace JustBetter\ProductGridExport\Model\Export;

use JustBetter\ProductGridExport\Model\LazySearchResultIterator;
use Magento\Framework\Exception\LocalizedException;

class ConvertToCsv extends \Magento\Ui\Model\Export\ConvertToCsv
{
    /**
     * @return \Generator<array>
     */
    public function getGenerator(): \Generator
    {
        $component = $this->filter->getComponent();

        $this->filter->applySelectionOnTargetProvider();
        /** @var \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider $dataProvider */
        $dataProvider = $component->getContext()->getDataProvider();
        $fields = $this->metadataProvider->getFields($component);

        $columnsWithType= $this->metadataProvider->getColumnsWithDataType($component);
        $page = 1;
        $searchResult = $dataProvider->getSearchResult()
            ->setCurPage($page)
            ->setPageSize($this?->pageSize ?? 200);

        yield $this->metadataProvider->getHeaders($component);
        $items = LazySearchResultIterator::getGenerator($searchResult);
        foreach ($items as $item) {
            $this->metadataProvider->convertDate($item, $component->getName());
            yield $this->metadataProvider->getRowDataBasedOnColumnType($item, $fields, $columnsWithType, []);
        }
    }

    /**
     * Returns CSV file
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCsvFile()
    {
        $component = $this->filter->getComponent();

        $name = md5(microtime());
        $file = 'export/'. $component->getName() . $name . '.csv';

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $data = $this->getGenerator();
        foreach ($data as $line) {
             $stream->writeCsv($line);
        }

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }
}
