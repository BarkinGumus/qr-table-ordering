<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

class QrCode
{
    /**
     * An inline <svg> element for the given text. SVG scales to any print
     * size without blurring. Error correction M tolerates a scuffed or
     * laminated print while keeping the pattern coarse enough to scan fast.
     */
    public static function svg(string $text): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'drawLightModules' => false,
            'connectPaths' => true,
        ]);

        return (new Generator($options))->render($text);
    }
}
