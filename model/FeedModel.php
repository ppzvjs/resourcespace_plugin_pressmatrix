<?php

namespace model;

class FeedModel
{

    private string $title;
    private string $feedlink;
    private string $link;
    private string $description;
    private \DateTime $buildDate;
    private string $updatePeriod = 'hourly';
    private int $updateFrequency = 1;
    private string $generator = 'https://paulparey.de';
    private string $imageUrl;
    private string $imageTitle;
    private int $imageWidth;
    private int $imageHeight;

    private array $items = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFeedlink(): string
    {
        return $this->feedlink;
    }

    public function setFeedlink(string $feedlink): void
    {
        $this->feedlink = $feedlink;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getBuildDate(): \DateTime
    {
        return $this->buildDate;
    }

    public function setBuildDate(\DateTime $buildDate): void
    {
        $this->buildDate = $buildDate;
    }

    public function getUpdatePeriod(): string
    {
        return $this->updatePeriod;
    }

    public function setUpdatePeriod(string $updatePeriod): void
    {
        $this->updatePeriod = $updatePeriod;
    }

    public function getUpdateFrequency(): int
    {
        return $this->updateFrequency;
    }

    public function setUpdateFrequency(int $updateFrequency): void
    {
        $this->updateFrequency = $updateFrequency;
    }

    public function getGenerator(): string
    {
        return $this->generator;
    }

    public function setGenerator(string $generator): void
    {
        $this->generator = $generator;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getImageTitle(): string
    {
        return $this->imageTitle;
    }

    public function setImageTitle(string $imageTitle): void
    {
        $this->imageTitle = $imageTitle;
    }

    public function getImageWidth(): int
    {
        return $this->imageWidth;
    }

    public function setImageWidth(int $imageWidth): void
    {
        $this->imageWidth = $imageWidth;
    }

    public function getImageHeight(): int
    {
        return $this->imageHeight;
    }

    public function setImageHeight(int $imageHeight): void
    {
        $this->imageHeight = $imageHeight;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(VideoModel $item)
    {
        $this->items[] = $item;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }


    public function getFeed()
    {
        $data = '<?xml version="1.0" encoding="UTF-8"?>';
        $data .= '<rss 
        xmlns:content="http://purl.org/rss/1.0/modules/content/" 
        xmlns:wfw="http://wellformedweb.org/CommentAPI/" 
        xmlns:dc="http://purl.org/dc/elements/1.1/" 
        xmlns:atom="http://www.w3.org/2005/Atom" 
        xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" 
        xmlns:media="http://search.yahoo.com/mrss/" 
        xmlns:slash="http://purl.org/rss/1.0/modules/slash/" version="2.0">';
        $data .= '<channel>';
        $data .= '<title>' . $this->getTitle() . '</title>';
        $data .= '<atom:link href="' . $this->getFeedLink() . '" rel="self" type="application/rss+xml"/>';
        $data .= '<link>' . $this->getLink() . '</link>';
        $data .= '<description>' . $this->getDescription() . '</description>';
        $data .= '<lastBuildDate>' . $this->getBuildDate()->format('r') . '</lastBuildDate>';
        $data .= '<language>de</language>';
        $data .= '<sy:updatePeriod>' . $this->getUpdatePeriod() . '</sy:updatePeriod>';
        $data .= '<sy:updateFrequency>' . $this->getUpdateFrequency() . '</sy:updateFrequency>';
        $data .= '<generator>' . $this->getGenerator() . '</generator>';
        $data .= '<image>';
        $data .= "\t" . '<url>' . $this->getImageUrl() . '</url>';
        $data .= "\t" . '<title>' . $this->getImageTitle() . '</title>';
        $data .= "\t" . '<link>' . $this->getImageUrl() . '</link>';
        $data .= "\t" . '<width>' . $this->getImageWidth() . '</width>';
        $data .= "\t" . '<height>' . $this->getImageHeight() . '</height>';
        $data .= '</image>';

        // VideoModel $item
        foreach($this->getItems() as $item){
            $data .= $item->getEntry();
        }

        $data .= '</channel>';
        $data .= '</rss>';
        return $data;
    }
}