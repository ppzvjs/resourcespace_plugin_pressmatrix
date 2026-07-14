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

    private string $google = '';
    private string $apple = '';

    private string $duration;

    private string $object;

    private string $objecttitle;

    private array $categories;

    private string $free;

    private int $ref;

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getRef(): int
    {
        return $this->ref;
    }

    public function setRef(int $ref): void
    {
        $this->ref = $ref;
    }


    public function getFree(): string
    {
        return $this->free;
    }

    public function setFree(string $free): void
    {
        $this->free = $free;
    }


    public function getDuration(): string
    {
        return $this->duration;
    }

    public function getDurationFormatted(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d min', $minutes, $seconds);
    }

    public function setDuration(string $duration): void
    {
        $this->duration = $duration;
    }

    public function getObject(): string
    {
        return $this->object;
    }

    public function setObject(string $object): void
    {
        $this->object = $object;
    }

    public function getObjecttitle(): string
    {
        return $this->config['pressmatrix_longname_' . strtolower($this->object)];
    }

    public function setObjecttitle(string $objecttitle): void
    {
        $this->objecttitle = $objecttitle;
    }


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

    public function getGoogle(): string
    {
        return $this->google;
    }

    public function setGoogle(string $google): void
    {
        $this->google = $google;
    }

    public function getApple(): string
    {
        return $this->apple;
    }

    public function setApple(string $apple): void
    {
        $this->apple = $apple;
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

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }


    public function getEntry()
    {

        $video = '<video width="100%" controls>
  <source src="' . htmlspecialchars($this->getHls()) . '" type="application/x-mpegURL">
            Dein Browser unterstützt das Video-Tag nicht.
</video>';


        $data = '<item>';
        $data .= '<title>' . htmlspecialchars($this->getTitle()) . '</title>';
        $data .= '<description><![CDATA[' . '<h1>' . $this->getTitle() . '</h1><h4>' . $this->getObjecttitle() . ' | ' . $this->getDurationFormatted() . '</h4>' . $this->getDescription() . "<br><br>" . $video . ']]></description>';
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

        if ($this->getPrice() >= 1) {
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

    public function getPressmatrix()
    {
        return [
            'story' => [
                "name" => htmlspecialchars($this->getTitle()),
                "title" => htmlspecialchars($this->getTitle()),
                "preview" => $this->getDescription(),
                // Pressmatrix will laut Doku Markdown, HTML/Video sollte aber klappen
                "content" => $this->buildVideo(),
                "external_id" => $this->getFree() != 'frei' ? $this->getExternalId() : '',
                "apple_product_identifier" => $this->getFree() != 'frei' ? $this->getApple() : '',
                "google_product_identifier" => $this->getFree() != 'frei' ? $this->getGoogle() : '',
                "released_at" => $this->getEvt()->format(\DateTime::ATOM),
                "cents" => $this->getFree() != 'frei' ? 199 : 0,
                "currency" => "EUR",
                "language" => "de",
                "subscription_required" => false,
                "purchase_required" => false,
                "article_category_ids" => $this->getCategories()
            ]
        ];
    }

    private function buildVideo()
    {
        $content = '<h1>' . $this->getTitle() . '</h1>';
        $content .= '<h4>' . $this->getObjecttitle() . ' | ' . $this->getDurationFormatted() . '</h4>';
        $content .= '<p>' . $this->getDescription() . '</p>';
        $content .= '<div class="video-wrapper">
                      <video id="' . $this->getRef() . '" controls crossorigin="anonymous">
                            Dein Browser unterstützt das Video-Tag nicht.
                      </video>
                    </div>';
        $content .= '<script src = "https://cdn.jsdelivr.net/npm/hls.js@1" ></script >';

        $content .= '<script >
                        document . addEventListener("DOMContentLoaded", function () {
                            const video = document . getElementById(\'' . $this->getRef() . '\');
                            const videoSrc = \'' . htmlspecialchars($this->getHls()) .'\';
                            if (Hls . isSupported()) {
                                const hls = new Hls();
                                hls . loadSource(videoSrc);
                                hls . attachMedia(video);
                            } else if (video . canPlayType(\'application/vnd.apple.mpegurl\')) {
                                video . src = videoSrc;
                            }
                        });
                    </script >';
        return $content;
    }


    /*
    public function getEntry(){
        $data = '<item > ';
        $data .= '<title > ' . htmlspecialchars($this->getTitle()) . '</title > ';
        $data .= '<description ><![CDATA[' . $this->getDescription() . ']] ></description > ';
        $data .= '<link > ' . htmlspecialchars($this->getLink()) . '</link > ';
        $data .= '<pubDate > ' . $this->getEvt()->format('r') . ' </pubDate > ';
        $data .= '<guid isPermaLink = "false" > ' . $this->getGuid() . '</guid > ';
        //$data .= '<enclosure url = "' . $this->getImage() .'" />';
        $data .= '<enclosure url = "https://www.paulparey.de/wp-content/uploads/2018/07/header-logo.jpg" />';
        //$data .= '<addfields:image ><![CDATA[<img src = "https://www.paulparey.de/wp-content/uploads/2018/07/header-logo.jpg" />]]></addfields:limage > ';
        $data .= '</item > ';
        return $data;
    }*/
}