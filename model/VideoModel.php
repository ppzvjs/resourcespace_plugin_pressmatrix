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
        $data = '<item>';
        $data .= '<title>' . $this->getTitle() . '</title>';
        $data .= '<description><![CDATA[' . $this->getDescription() . ']]></description>';
        $data .= '<link>' . $this->getLink() . '</link>';
        $data .= '<pubDate>' . $this->getEvt()->format('r') . '</pubDate>';
        $data .= '<guid>' . $this->getGuid() . '</guid>';
        $data .= '<enclosure url="' . $this->getImage() .'"/>';
        $data .= '</item>';
        return $data;
    }
}