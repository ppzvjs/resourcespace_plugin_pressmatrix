<?php

namespace model;

use Gettext\Languages\Exporter\Xml;

class VideoModel
{
    private string $title;
    private string $description;
    private string $link;

    private \DateTime $evt;
    private int $guid;
    private string $image;

    private string $hls;

    private int $price = 0;

    private string $external_id = '';

    public function getExternalId(): string
    {
        return $this->external_id;
    }

    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }



    public function getPrice(): int
    {
        return $this->price;
    }



    public function setPrice(int $price): void
    {
        $this->price = $price;
    }



    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getEvt(): \DateTime
    {
        return $this->evt;
    }

    public function setEvt(\DateTime $evt): void
    {
        $this->evt = $evt;
    }

    public function getGuid(): int
    {
        return $this->guid;
    }

    public function setGuid(int $guid): void
    {
        $this->guid = $guid;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function getHls(): string
    {
        return $this->hls;
    }

    public function setHls(string $hls): void
    {
        $this->hls = $hls;
    }

    public function getEntry(){

        $video = '<video width="640" height="360" controls>
  <source src="' . htmlspecialchars($this->getHls()) .'" type="application/x-mpegURL">
            Dein Browser unterstützt das Video-Tag nicht.
</video>';


        $data = '<item>';
        $data .= '<title>' . htmlspecialchars($this->getTitle()) . '</title>';
        $data .= '<description><![CDATA[' . $this->getDescription() . "<br>" . $video .']]></description>';
        $data .= '<link>' . htmlspecialchars($this->getLink()) . '</link>';
        $data .= '<pubDate>' . $this->getEvt()->format('r') . '</pubDate>';

        // Fix: Guid uniqueness. Adding a prefix can help if IDs repeat across feeds
        $data .= '<guid isPermaLink="false">pm-' . $this->getGuid() . '</guid>';

        // Fix: Added 'length="0"' which is mandatory for RSS enclosures
        $data .= '<enclosure url="' . htmlspecialchars($this->getImage()) . '" length="0" type="image/jpeg" />';

        // Fix: Valid character in URI error
        // If $this->getHls() contains a date string instead of a URL,
        // ensure you are passing the correct field value in feed.php
        /*if (!empty($this->getHls()) && strpos($this->getHls(), 'http') === 0) {
            $data .= '<media:content url="' . htmlspecialchars($this->getHls()) . '" type="application/x-mpegURL" />';
        }*/

        if($this->getPrice() >= 1){
            $data .= '<price>' . $this->getPrice() . '</price>';
            $data .= '<product_external_id>' . $this->getExternalId() . '</product_external_id>';
        }

        $data .= '<media:group>';
        $data .= '<media:content url="' . htmlspecialchars($this->getHls()) . '" type="application/x-mpegURL" medium="video" />';
        $data .= '<media:thumbnail url="' . htmlspecialchars($this->getImage()) . '" width="1280" height="720" />';
        $data .= '<media:title>' . htmlspecialchars($this->getTitle()) . '</media:title>';
        $data .= '</media:group>';

        $data .= '</item>';
        return $data;
    }


    /*
    public function getEntry(){
        $data = '<item>';
        $data .= '<title>' . htmlspecialchars($this->getTitle()) . '</title>';
        $data .= '<description><![CDATA[' . $this->getDescription() . ']]></description>';
        $data .= '<link>' . htmlspecialchars($this->getLink()) . '</link>';
        $data .= '<pubDate>' . $this->getEvt()->format('r') . '</pubDate>';
        $data .= '<guid isPermaLink="false">' . $this->getGuid() . '</guid>';
        //$data .= '<enclosure url="' . $this->getImage() .'"/>';
        $data .= '<enclosure url="https://www.paulparey.de/wp-content/uploads/2018/07/header-logo.jpg" />';
        //$data .= '<addfields:image><![CDATA[<img src="https://www.paulparey.de/wp-content/uploads/2018/07/header-logo.jpg" />]]></addfields:limage>';
        $data .= '</item>';
        return $data;
    }*/
}